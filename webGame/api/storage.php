<?php
// Game state persistence. Prefers SQLite (pdo_sqlite); falls back to plain
// JSON files with flock() if SQLite is unavailable on the host.

// A room's lock could not be acquired within the deadline. The API layer maps
// this to a 503 so the client retries instead of the host killing a request
// that blocked forever (an HTML error page the client cannot parse).
class SarStorageBusy extends RuntimeException {}

class SarStorage {
    private ?PDO $db = null;
    private string $dir;
    /** @var resource|null */
    private $lockHandle = null;
    private bool $inTxn = false;

    public function __construct() {
        $this->dir = __DIR__ . '/data';
        if (!is_dir($this->dir)) @mkdir($this->dir, 0775, true);
        if (!is_dir($this->dir) || !is_writable($this->dir)) {
            throw new RuntimeException(
                'The storage folder api/data does not exist or PHP cannot write to it. ' .
                'Create the folder "data" inside "api" on the server and set its permissions to 755 (or 775).');
        }
        // Set SAR_FORCE_JSON=1 (env or constant) to skip SQLite on hosts where it misbehaves.
        $forceJson = getenv('SAR_FORCE_JSON') || (defined('SAR_FORCE_JSON') && SAR_FORCE_JSON);
        if (!$forceJson && class_exists('PDO') && in_array('sqlite', PDO::getAvailableDrivers(), true)) {
            try {
                $this->db = new PDO('sqlite:' . $this->dir . '/games.db');
                $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $this->db->exec('PRAGMA journal_mode=WAL');
                $this->db->exec('PRAGMA busy_timeout=5000');
                $this->db->exec('CREATE TABLE IF NOT EXISTS games (
                    room TEXT PRIMARY KEY, state TEXT NOT NULL, version INTEGER NOT NULL, updated INTEGER NOT NULL)');
            } catch (Throwable $e) {
                $this->db = null; // fall back to JSON files
            }
        }
    }

    // Serialize the whole request handling for one room.
    public function lock(string $room): void {
        if ($this->db) {
            // beginTransaction() opens a *deferred* SQLite transaction — no
            // lock is actually taken until the first write, so two
            // concurrent requests can both read, both mutate, and the last
            // save() wins. BEGIN IMMEDIATE takes the write lock up front
            // (bounded by PRAGMA busy_timeout, set in the constructor).
            try {
                $this->db->exec('BEGIN IMMEDIATE');
            } catch (PDOException $e) {
                if (stripos($e->getMessage(), 'locked') !== false
                    || stripos($e->getMessage(), 'busy') !== false) {
                    throw new SarStorageBusy('SQLite write lock timed out', 0, $e);
                }
                throw $e;
            }
            $this->inTxn = true;
        } else {
            if (!preg_match('/^[A-Z0-9]{1,10}$/', $room)) {
                throw new RuntimeException('Invalid room code');
            }
            $h = fopen($this->dir . '/' . $room . '.lock', 'c');
            if ($h === false) {
                throw new RuntimeException(
                    'Cannot create files in api/data — check that the folder exists on the server ' .
                    'and that its permissions allow PHP to write (chmod 755 or 775).');
            }
            $this->lockHandle = $h;
            // Never block indefinitely: on Linux max_execution_time counts CPU
            // time, so a request stuck in a blocking flock() is only ever
            // stopped by the web server killing it — which surfaces to the
            // player as an unparseable HTML error page. Poll with LOCK_NB and
            // give up cleanly after ~5s instead.
            $deadline = microtime(true) + 5.0;
            while (!flock($this->lockHandle, LOCK_EX | LOCK_NB)) {
                if (microtime(true) >= $deadline) {
                    fclose($this->lockHandle);
                    $this->lockHandle = null;
                    throw new SarStorageBusy("Lock on room $room timed out");
                }
                usleep(50_000);
            }
        }
    }

    public function unlock(): void {
        if ($this->db) {
            if ($this->inTxn) {
                $this->db->exec('COMMIT');
                $this->inTxn = false;
            }
        } elseif ($this->lockHandle) {
            flock($this->lockHandle, LOCK_UN);
            fclose($this->lockHandle);
            $this->lockHandle = null;
        }
    }

    // Decode a stored state blob, distinguishing "no row" (null input) from
    // a corrupted one (invalid JSON, which json_decode also returns as null).
    private function decodeState(?string $json): ?array {
        if ($json === null) return null;
        $data = json_decode($json, true);
        if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException('Corrupted game state in storage: ' . json_last_error_msg());
        }
        return $data;
    }

    public function load(string $room): ?array {
        if ($this->db) {
            $st = $this->db->prepare('SELECT state FROM games WHERE room = ?');
            $st->execute([$room]);
            $row = $st->fetchColumn();
            return $this->decodeState($row === false ? null : $row);
        }
        $file = $this->dir . '/' . $room . '.json';
        if (!file_exists($file)) return null;
        return $this->decodeState(file_get_contents($file));
    }

    public function save(array $state): void {
        $room = $state['room'];
        // JSON_INVALID_UTF8_SUBSTITUTE: never let one bad byte persist an
        // empty blob (json_encode() === false) that bricks the room forever.
        $json = json_encode($state, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        if ($json === false) {
            throw new RuntimeException('Could not encode game state: ' . json_last_error_msg());
        }
        if ($this->db) {
            $st = $this->db->prepare('INSERT INTO games (room, state, version, updated) VALUES (?,?,?,?)
                ON CONFLICT(room) DO UPDATE SET state=excluded.state, version=excluded.version, updated=excluded.updated');
            $st->execute([$room, $json, $state['version'], time()]);
        } else {
            // Write to a per-save tmp file and rename() into place — rename is
            // atomic on POSIX filesystems, so a crash mid-write can never leave
            // a half-written ROOM.json for a later load() to trip over.
            $file = $this->dir . '/' . $room . '.json';
            $tmp = $file . '.tmp' . bin2hex(random_bytes(4));
            file_put_contents($tmp, $json, LOCK_EX);
            rename($tmp, $file);
        }
    }

    public function version(string $room): ?int {
        if ($this->db) {
            $st = $this->db->prepare('SELECT version FROM games WHERE room = ?');
            $st->execute([$room]);
            $v = $st->fetchColumn();
            return $v === false ? null : (int)$v;
        }
        $s = $this->load($room);
        return $s ? $s['version'] : null;
    }

    // Housekeeping: drop rooms idle for 3+ days. Runs on a random 1-in-50
    // sample of requests so it stays cheap without a cron job.
    public function cleanup(): void {
        if (random_int(1, 50) !== 1) return;
        if ($this->db) {
            $this->db->exec('DELETE FROM games WHERE updated < ' . (time() - 3 * 86400));
            return;
        }
        $cutoff = time() - 3 * 86400;
        foreach (glob($this->dir . '/*.json') ?: [] as $file) {
            $mtime = @filemtime($file);
            if ($mtime !== false && $mtime < $cutoff) {
                $room = basename($file, '.json');
                @unlink($file);
                @unlink($this->dir . '/' . $room . '.lock');
            }
        }
    }
}
