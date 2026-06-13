import { useState, useEffect } from 'react';
import Button from '../ui/Button';
import { rescheduleApi } from '../../utils/api';
import { formatJadwalDatetime } from '../../utils/generateIcs';

/**
 * RescheduleInbox — inbox permintaan reschedule untuk admin
 */
const RescheduleInbox = () => {
  const [list, setList] = useState([]);
  const [loading, setLoading] = useState(true);
  const [rejectId, setRejectId] = useState(null);
  const [alasanPenolakan, setAlasanPenolakan] = useState('');
  const [error, setError] = useState('');

  const fetchList = async () => {
    setLoading(true);
    try {
      const res = await rescheduleApi.getAll('menunggu');
      setList(res.data);
    } catch (err) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchList();
  }, []);

  const handleApprove = async (id) => {
    try {
      await rescheduleApi.process(id, { action: 'approve' });
      fetchList();
    } catch (err) {
      setError(err.message || 'Gagal menyetujui');
    }
  };

  const handleReject = async () => {
    if (!rejectId || alasanPenolakan.length < 10) {
      setError('Alasan penolakan minimal 10 karakter');
      return;
    }
    try {
      await rescheduleApi.process(rejectId, {
        action: 'reject',
        alasan_penolakan: alasanPenolakan,
      });
      setRejectId(null);
      setAlasanPenolakan('');
      fetchList();
    } catch (err) {
      setError(err.message || 'Gagal menolak');
    }
  };

  if (loading) {
    return <div className="text-sm text-slate-500 py-4">Memuat permintaan reschedule...</div>;
  }

  return (
    <div className="bg-white border border-slate-200 rounded-xl p-4">
      <h3 className="text-sm font-semibold text-slate-700 mb-3">
        Inbox Reschedule
        {list.length > 0 && (
          <span className="ml-2 bg-amber-500 text-white text-xs px-2 py-0.5 rounded-full">
            {list.length}
          </span>
        )}
      </h3>

      {list.length === 0 ? (
        <p className="text-sm text-slate-400">Tidak ada permintaan menunggu.</p>
      ) : (
        <div className="space-y-3">
          {list.map((item) => (
            <div key={item.id} className="border border-slate-100 rounded-lg p-3 space-y-2">
              <div className="flex justify-between items-start">
                <div>
                  <p className="font-medium text-slate-800 text-sm">
                    {item.penugasan_jadwal?.pendaftar?.nama}
                  </p>
                  <p className="text-xs text-slate-500 font-mono">
                    {item.penugasan_jadwal?.pendaftar?.nomor_pendaftaran}
                  </p>
                </div>
                <span className="text-xs bg-yellow-100 text-yellow-800 px-2 py-0.5 rounded-full">
                  Menunggu
                </span>
              </div>
              <p className="text-xs text-slate-600">
                <span className="text-slate-400">Dari: </span>
                {item.penugasan_jadwal?.jadwal_tes?.judul} —{' '}
                {formatJadwalDatetime(item.penugasan_jadwal?.jadwal_tes?.tanggal_mulai)}
              </p>
              <p className="text-xs text-slate-600">
                <span className="text-slate-400">Ke: </span>
                {item.jadwal_tes_baru?.judul} —{' '}
                {formatJadwalDatetime(item.jadwal_tes_baru?.tanggal_mulai)}
              </p>
              <p className="text-xs text-slate-500 bg-slate-50 rounded px-2 py-1.5">
                {item.alasan}
              </p>
              <div className="flex gap-2">
                <Button
                  variant="success"
                  className="text-xs py-1.5"
                  onClick={() => handleApprove(item.id)}
                >
                  Setujui
                </Button>
                <Button
                  variant="secondary"
                  className="text-xs py-1.5"
                  onClick={() => setRejectId(item.id)}
                >
                  Tolak
                </Button>
              </div>
            </div>
          ))}
        </div>
      )}

      {rejectId && (
        <div className="mt-3 p-3 bg-red-50 border border-red-200 rounded-lg space-y-2">
          <label className="block text-xs text-slate-600">Alasan Penolakan</label>
          <textarea
            value={alasanPenolakan}
            onChange={(e) => setAlasanPenolakan(e.target.value)}
            rows={2}
            className="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm resize-none"
          />
          <div className="flex gap-2">
            <Button variant="secondary" className="text-xs" onClick={() => setRejectId(null)}>
              Batal
            </Button>
            <Button variant="primary" className="text-xs bg-red-600 hover:bg-red-700" onClick={handleReject}>
              Konfirmasi Tolak
            </Button>
          </div>
        </div>
      )}

      {error && (
        <p className="mt-2 text-xs text-red-600">{error}</p>
      )}
    </div>
  );
};

export default RescheduleInbox;
