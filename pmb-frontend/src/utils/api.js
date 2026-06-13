/**
 * api.js — helper untuk fetch ke Laravel API backend
 * Base URL diambil dari env variable atau default ke localhost:8000
 */
const BASE_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api';
const TOKEN_KEY = 'pmb_admin_token';

/** Ambil token yang tersimpan di sessionStorage */
export const getToken = () => sessionStorage.getItem(TOKEN_KEY);
/** Simpan token ke sessionStorage */
export const setToken = (token) => sessionStorage.setItem(TOKEN_KEY, token);
/** Hapus token dari sessionStorage */
export const removeToken = () => sessionStorage.removeItem(TOKEN_KEY);

/**
 * Fetch wrapper dengan format response standar dari backend PMB
 * Menyertakan Bearer token jika tersedia
 */
const apiFetch = async (path, options = {}) => {
  const token = getToken();
  const headers = { 'Content-Type': 'application/json', ...options.headers };
  if (token) headers['Authorization'] = `Bearer ${token}`;

  const res = await fetch(`${BASE_URL}${path}`, { ...options, headers });
  const json = await res.json();
  if (!res.ok || !json.success) {
    const err = new Error(json.message || 'Terjadi kesalahan pada server');
    err.errors = json.errors || null;
    err.status = res.status;
    throw err;
  }
  return json;
};

export const authApi = {
  /** POST /api/auth/login */
  login: (username, password) =>
    apiFetch('/auth/login', {
      method: 'POST',
      body: JSON.stringify({ username, password }),
    }),

  /** POST /api/auth/logout */
  logout: () => apiFetch('/auth/logout', { method: 'POST' }),
};

export const pendaftarApi = {
  /** GET /api/pendaftar — ambil semua pendaftar (perlu token) */
  getAll: () => apiFetch('/pendaftar'),

  /** GET /api/pendaftar/{nomor} — cari berdasarkan nomor pendaftaran */
  getByNomor: (nomor) => apiFetch(`/pendaftar/${encodeURIComponent(nomor)}`),

  /** POST /api/pendaftar — daftar baru */
  store: (data) =>
    apiFetch('/pendaftar', {
      method: 'POST',
      body: JSON.stringify(data),
    }),

  /** PATCH /api/pendaftar/{id}/status — ubah status (perlu token) */
  updateStatus: (id, status) =>
    apiFetch(`/pendaftar/${id}/status`, {
      method: 'PATCH',
      body: JSON.stringify({ status }),
    }),

  /** POST /api/pendaftar/{nomor}/heregistrasi — heregistrasi mahasiswa lolos */
  heregistrasi: (nomor) =>
    apiFetch(`/pendaftar/${encodeURIComponent(nomor)}/heregistrasi`, {
      method: 'POST',
    }),
};

export const statistikApi = {
  /** GET /api/statistik — statistik per prodi, jalur, status (perlu token) */
  get: () => apiFetch('/statistik'),
};

/** URL langsung untuk download CSV (buka di tab baru dengan token di header tidak bisa — gunakan query param workaround) */
export const getExportCsvUrl = () =>
  `${BASE_URL}/pendaftar/export/csv`;

export const jadwalApi = {
  /** GET /api/jadwal-tes — semua slot (admin) */
  getAll: () => apiFetch('/jadwal-tes'),

  /** GET /api/jadwal-tes/tersedia?jenis= — slot tersedia (publik) */
  getTersedia: (jenis) => apiFetch(`/jadwal-tes/tersedia?jenis=${encodeURIComponent(jenis)}`),

  /** GET /api/pendaftar/{nomor}/jadwal — jadwal peserta (publik) */
  getByNomor: (nomor) => apiFetch(`/pendaftar/${encodeURIComponent(nomor)}/jadwal`),

  /** POST /api/jadwal-tes — buat slot (admin) */
  store: (data) =>
    apiFetch('/jadwal-tes', { method: 'POST', body: JSON.stringify(data) }),

  /** PATCH /api/jadwal-tes/{id} — update slot (admin) */
  update: (id, data) =>
    apiFetch(`/jadwal-tes/${id}`, { method: 'PATCH', body: JSON.stringify(data) }),

  /** DELETE /api/jadwal-tes/{id} — nonaktifkan (admin) */
  destroy: (id) => apiFetch(`/jadwal-tes/${id}`, { method: 'DELETE' }),

  /** GET /api/jadwal-tes/{id}/peserta — peserta per slot (admin) */
  getPeserta: (id) => apiFetch(`/jadwal-tes/${id}/peserta`),
};

export const penugasanApi = {
  /** POST /api/penugasan — assign manual (admin) */
  store: (data) =>
    apiFetch('/penugasan', { method: 'POST', body: JSON.stringify(data) }),

  /** POST /api/penugasan/auto-batch — auto batch (admin) */
  autoBatch: (data) =>
    apiFetch('/penugasan/auto-batch', { method: 'POST', body: JSON.stringify(data) }),

  /** DELETE /api/penugasan/{id} — batalkan (admin) */
  destroy: (id) => apiFetch(`/penugasan/${id}`, { method: 'DELETE' }),
};

export const rescheduleApi = {
  /** POST /api/reschedule — ajukan (publik) */
  store: (data) =>
    apiFetch('/reschedule', { method: 'POST', body: JSON.stringify(data) }),

  /** GET /api/reschedule — list (admin) */
  getAll: (status) =>
    apiFetch(`/reschedule${status ? `?status=${status}` : ''}`),

  /** PATCH /api/reschedule/{id} — approve/reject (admin) */
  process: (id, data) =>
    apiFetch(`/reschedule/${id}`, { method: 'PATCH', body: JSON.stringify(data) }),
};

export const kehadiranApi = {
  /** POST /api/kehadiran/checkin — check-in QR (publik) */
  checkin: (token, operatorPin) =>
    apiFetch('/kehadiran/checkin', {
      method: 'POST',
      body: JSON.stringify({ token, operator_pin: operatorPin }),
    }),

  /** GET /api/kehadiran/sesi/{id} — laporan sesi (admin) */
  getSesi: (jadwalTesId) => apiFetch(`/kehadiran/sesi/${jadwalTesId}`),
};

export const notifikasiApi = {
  /** GET /api/notifikasi — log notifikasi (admin) */
  getAll: (unreadOnly = false) =>
    apiFetch(`/notifikasi${unreadOnly ? '?unread=true' : ''}`),

  /** PATCH /api/notifikasi/{id}/baca */
  markRead: (id) => apiFetch(`/notifikasi/${id}/baca`, { method: 'PATCH' }),
};
