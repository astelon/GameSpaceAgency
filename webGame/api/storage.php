<?php
// Game state persistence. Prefers SQLite (pdo_sqlite); falls back to plain
// JSON files with flock() if SQLite is unavailable on the host.

class SarStorage {
    private ?PDO $db = null;
    private string $dir;
    /** @var resource|null */
    private $lockHandle = null;

    public function __construct() {
        $this->dir = __DIR__ . '/data';
        if (!is_dir($this->dir)) @mkdir($this->dir, 0775, true);
        if (in_array('sqlite', PDO::getAvailableDrivers(), true)) {
            $this->db = new PDO('sqlite:' . $this->dir . '/games.db');
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->db->exec('PRAGMA journal_mode=WAL');
            $this->db->exec('CREATE TABLE IF NOT EXISTS games (
                room TEXT PRIMARY KEY, state TEXT NOT NULL, version INTEGER NOT NULL, updated INTEGER NOT NULL)');
        }
    }

    // Serialize the whole request handling for one room.
    public function lock(string $room): void {
        if ($this->db) {
            $this->db->beginTransaction();
        } else {
            $this->lockHandle = fopen($this->dir . '/' . $room . '.lock', 'c');
            flock($this->lockHandle, LOCK_EX);
        }
    }

    public function unlock(): void {
        if ($this->db) {
            if ($this->db->inTransaction()) $this->db->commit();
        } elseif ($this->lockHandle) {
            flock($this->lockHandle, LOCK_UN);
            fclose($this->lockHandle);
            $this->lockHandle = null;
        }
    }

    public function load(string $room): ?array {
        if ($this->db) {
            $st = $this->db->prepare('SELECT state FROM games WHERE room = ?');
            $st->execute([$room]);
            $row = $st->fetchColumn();
            return $row === false ? null : json_decode($row, true);
        }
        $file = $this->dir . '/' . $room . '.json';
        if (!file_exists($file)) return null;
        return json_decode(file_get_contents($file), true);
    }

    public function save(array $state): void {
        $room = $state['room'];
        $json = json_encode($state, JSON_UNESCAPED_UNICODE);
        if ($this->db) {
            $st = $this->db->prepare('INSERT INTO games (room, state, version, updated) VALUES (?,?,?,?)
                ON CONFLICT(room) DO UPDATE SET state=excluded.state, version=excluded.version, updated=excluded.updated');
            $st->execute([$room, $json, $state['version'], time()]);
        } else {
            file_put_contents($this->dir . '/' . $room . '.json', $json, LOCK_EX);
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

    // Housekeeping: drop rooms idle for 3+ days.
    public function cleanup(): void {
        if ($this->db) {
            if (random_int(1, 50) === 1) {
                $this->db->exec('DELETE FROM games WHERE updated < ' . (time() - 3 * 86400));
            }
        }
    }
}
