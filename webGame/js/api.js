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

export class ApiError extends Error {}

export async function api(op, payload = {}) {
  const res = await fetch(API_URL, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ op, ...payload }),
  });
  let data;
  try { data = await res.json(); }
  catch { throw new ApiError('Server returned an invalid response'); }
  if (!res.ok || data.error) throw new ApiError(data.error || `HTTP ${res.status}`);
  return data;
}

export function gameCall(op, payload = {}) {
  return api(op, { room: session.room, token: session.token, ...payload });
}
