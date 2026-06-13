/**
 * generateIcs — buat file .ics (RFC 5545) untuk tambah ke kalender HP
 */
export const generateIcs = (jadwal, penugasanId) => {
  const pad = (n) => String(n).padStart(2, '0');

  const toIcsDate = (iso) => {
    const d = new Date(iso);
    return (
      d.getUTCFullYear() +
      pad(d.getUTCMonth() + 1) +
      pad(d.getUTCDate()) +
      'T' +
      pad(d.getUTCHours()) +
      pad(d.getUTCMinutes()) +
      pad(d.getUTCSeconds()) +
      'Z'
    );
  };

  const uid = `pmb-jadwal-${penugasanId}@sevima.local`;
  const dtStart = toIcsDate(jadwal.tanggal_mulai);
  const dtEnd = toIcsDate(jadwal.tanggal_selesai);
  const now = toIcsDate(new Date().toISOString());

  const description = [
    `Lokasi: ${jadwal.lokasi}`,
    jadwal.catatan ? `Catatan: ${jadwal.catatan}` : '',
  ]
    .filter(Boolean)
    .join('\\n');

  return [
    'BEGIN:VCALENDAR',
    'VERSION:2.0',
    'PRODID:-//PMB SEVIMA//JadwalHub//ID',
    'CALSCALE:GREGORIAN',
    'BEGIN:VEVENT',
    `UID:${uid}`,
    `DTSTAMP:${now}`,
    `DTSTART:${dtStart}`,
    `DTEND:${dtEnd}`,
    `SUMMARY:${jadwal.judul}`,
    `LOCATION:${jadwal.lokasi}`,
    `DESCRIPTION:${description}`,
    'END:VEVENT',
    'END:VCALENDAR',
  ].join('\r\n');
};

/**
 * downloadIcs — trigger download file .ics ke perangkat user
 */
export const downloadIcs = (jadwal, penugasanId) => {
  const content = generateIcs(jadwal, penugasanId);
  const blob = new Blob([content], { type: 'text/calendar;charset=utf-8' });
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = `jadwal-${jadwal.judul.replace(/\s+/g, '-').toLowerCase()}.ics`;
  a.click();
  URL.revokeObjectURL(url);
};

/**
 * formatJadwalDatetime — format tanggal jadwal ke locale Indonesia
 */
export const formatJadwalDatetime = (iso) => {
  return new Date(iso).toLocaleString('id-ID', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    timeZone: 'Asia/Jakarta',
  });
};

/**
 * daysUntil — hitung H-X hari menuju jadwal
 */
export const daysUntil = (iso) => {
  const target = new Date(iso);
  const now = new Date();
  const diff = Math.ceil((target - now) / (1000 * 60 * 60 * 24));
  if (diff < 0) return 'Sudah lewat';
  if (diff === 0) return 'Hari ini';
  return `H-${diff}`;
};
