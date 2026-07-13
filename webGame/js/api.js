// Thin API client. Session credentials persist in localStorage so a refresh
// (or a phone losing focus) rejoins the same game.

const API_URL = 'api/index.php';

export const session = {
  get room() { return localStorage.getItem('sar_room'); },
  get token() { return localStorage.getItem('sar_token'); },
  get mode() { return localStorage.getItem('sar_mode'); },
  save(room, token, mode) {
    localStorage.setItem('sar_room', room);
    localStorage.setItem('sar_token', token);
    localStorage.setItem('sar_mode', mode);
  },
  clear() {
    localStorage.removeItem('sar_room');
    localStorage.removeItem('sar_token');
    localStorage.removeItem('sar_mode');
  },
};

// status: HTTP status of the failed request, or 0 for network/timeout
// failures. Callers use it to tell definitive rejections (400/403/404 —
// give up) from transient server trouble (0/5xx — keep the session, retry).
export class ApiError extends Error {
  constructor(message, status = 0) {
    super(message);
    this.status = status;
  }
}

const API_TIMEOUT_MS = 20000;

export async function api(op, payload = {}) {
  const ctrl = new AbortController();
  const timer = setTimeout(() => ctrl.abort(), API_TIMEOUT_MS);
  let res;
  try {
    res = await fetch(API_URL, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ op, ...payload }),
      signal: ctrl.signal,
    });
  } catch (e) {
    throw new ApiError(e.name === 'AbortError'
      ? 'The server is not answering (timed out)'
      : 'Could not reach the server — check your connection', 0);
  } finally {
    clearTimeout(timer);
  }
  let data;
  try { data = await res.json(); }
  catch {
    // A non-JSON body never comes from the game API itself — it is the
    // hosting layer answering (overload/error page) or a killed request.
    throw new ApiError(res.ok
      ? 'Server returned an invalid response'
      : `Server error (HTTP ${res.status}) — the host looks busy or misconfigured`, res.status);
  }
  if (!res.ok || data.error) throw new ApiError(data.error || `HTTP ${res.status}`, res.status);
  return data;
}

export function gameCall(op, payload = {}) {
  return api(op, { room: session.room, token: session.token, ...payload });
}
