// Thin API client. Session credentials persist in localStorage so a refresh
// (or a phone losing focus) rejoins the same game.

const API_URL = 'api/index.php';
const REQUEST_TIMEOUT_MS = 15000;

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

// status is the HTTP status code (0 for network failures/timeouts, where
// there was no response at all) — callers use it to tell "you're not
// actually in this game" (403/404) apart from a transient server hiccup.
export class ApiError extends Error {
  constructor(message, status = 0) {
    super(message);
    this.status = status;
  }
}

export async function api(op, payload = {}) {
  const controller = new AbortController();
  const timer = setTimeout(() => controller.abort(), REQUEST_TIMEOUT_MS);
  let res;
  try {
    res = await fetch(API_URL, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ op, ...payload }),
      signal: controller.signal,
    });
  } catch (e) {
    throw new ApiError(e.name === 'AbortError' ? 'Request timed out' : 'Network error', 0);
  } finally {
    clearTimeout(timer);
  }
  let data;
  try { data = await res.json(); }
  catch { throw new ApiError('Server returned an invalid response', res.status); }
  if (!res.ok || data.error) throw new ApiError(data.error || `HTTP ${res.status}`, res.status);
  return data;
}

export function gameCall(op, payload = {}) {
  return api(op, { room: session.room, token: session.token, ...payload });
}
