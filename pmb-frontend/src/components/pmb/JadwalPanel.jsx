import { useState, useEffect } from 'react';
import { QRCodeSVG } from 'qrcode.react';
import Button from '../ui/Button';
import { jadwalApi } from '../../utils/api';
import { downloadIcs, formatJadwalDatetime, daysUntil } from '../../utils/generateIcs';
import RescheduleForm from './RescheduleForm';

/**
 * JadwalPanel — kartu jadwal tes/wawancara peserta dengan QR tiket
 */
const JadwalPanel = ({ nomor }) => {
  const [jadwalList, setJadwalList] = useState([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [rescheduleTarget, setRescheduleTarget] = useState(null);

  const fetchJadwal = async () => {
    setLoading(true);
    setError('');
    try {
      const res = await jadwalApi.getByNomor(nomor);
      setJadwalList(res.data);
    } catch (err) {
      setError(err.message || 'Gagal memuat jadwal');
      setJadwalList([]);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    if (nomor) fetchJadwal();
  }, [nomor]);

  if (!nomor) return null;

  if (loading) {
    return <div className="text-sm text-slate-500 text-center py-4">Memuat jadwal...</div>;
  }

  if (error) {
    return (
      <div className="p-3 bg-yellow-50 border border-yellow-200 rounded-lg text-yellow-700 text-xs">
        {error}
      </div>
    );
  }

  if (jadwalList.length === 0) {
    return (
      <div className="p-4 bg-slate-50 border border-slate-200 rounded-xl">
        <p className="text-sm text-slate-500">Belum ada jadwal tes/wawancara untuk nomor ini.</p>
      </div>
    );
  }

  return (
    <div className="space-y-3">
      <h4 className="text-sm font-semibold text-slate-700 flex items-center gap-2">
        <svg className="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
        </svg>
        Jadwal Saya
      </h4>

      {jadwalList.map((item) => (
        <div key={item.id} className="p-4 bg-white border border-slate-200 rounded-xl space-y-3">
          <div className="flex items-start justify-between gap-2">
            <div>
              <span
                className={`inline-block text-xs font-medium px-2 py-0.5 rounded-full mb-1 ${
                  item.jadwal.jenis === 'wawancara'
                    ? 'bg-green-100 text-green-800'
                    : 'bg-amber-100 text-amber-800'
                }`}
              >
                {item.jadwal.jenis === 'wawancara' ? 'Wawancara' : 'Tes Seleksi'}
              </span>
              <h5 className="font-semibold text-slate-800">{item.jadwal.judul}</h5>
              <p className="text-xs text-blue-600 font-medium mt-0.5">{daysUntil(item.jadwal.tanggal_mulai)}</p>
            </div>
            <span
              className={`text-xs px-2 py-0.5 rounded-full ${
                item.status === 'hadir'
                  ? 'bg-green-100 text-green-800'
                  : 'bg-blue-100 text-blue-800'
              }`}
            >
              {item.status === 'hadir' ? 'Sudah Hadir' : 'Terjadwal'}
            </span>
          </div>

          <div className="text-sm space-y-1">
            <p className="text-slate-600">
              <span className="text-slate-400">Waktu: </span>
              {formatJadwalDatetime(item.jadwal.tanggal_mulai)}
            </p>
            <p className="text-slate-600">
              <span className="text-slate-400">Lokasi: </span>
              {item.jadwal.lokasi}
            </p>
            {item.jadwal.catatan && (
              <p className="text-xs text-slate-500 bg-slate-50 rounded-lg px-2 py-1.5 mt-1">
                {item.jadwal.catatan}
              </p>
            )}
          </div>

          {item.reschedule && (
            <div
              className={`text-xs px-3 py-2 rounded-lg ${
                item.reschedule.status === 'menunggu'
                  ? 'bg-yellow-50 text-yellow-700 border border-yellow-200'
                  : item.reschedule.status === 'disetujui'
                  ? 'bg-green-50 text-green-700 border border-green-200'
                  : 'bg-red-50 text-red-700 border border-red-200'
              }`}
            >
              Reschedule: {item.reschedule.status}
              {item.reschedule.alasan_penolakan && ` — ${item.reschedule.alasan_penolakan}`}
            </div>
          )}

          <div className="flex flex-col items-center py-3 border border-dashed border-slate-200 rounded-lg bg-slate-50">
            <QRCodeSVG value={item.checkin_token} size={160} level="M" />
            <p className="text-xs text-slate-500 mt-2 text-center">
              Tunjukkan QR ini ke panitia saat check-in
            </p>
          </div>

          <div className="flex gap-2">
            <Button
              variant="secondary"
              className="flex-1 text-xs"
              onClick={() => downloadIcs(item.jadwal, item.id)}
            >
              Download .ics
            </Button>
            {item.status === 'terjadwal' &&
              (!item.reschedule || item.reschedule.status !== 'menunggu') && (
                <Button
                  variant="primary"
                  className="flex-1 text-xs"
                  onClick={() => setRescheduleTarget(item)}
                >
                  Reschedule
                </Button>
              )}
          </div>
        </div>
      ))}

      {rescheduleTarget && (
        <RescheduleForm
          nomor={nomor}
          penugasan={rescheduleTarget}
          onClose={() => setRescheduleTarget(null)}
          onSuccess={() => {
            setRescheduleTarget(null);
            fetchJadwal();
          }}
        />
      )}
    </div>
  );
};

export default JadwalPanel;
