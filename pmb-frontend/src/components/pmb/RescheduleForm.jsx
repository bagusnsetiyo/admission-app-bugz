import { useState, useEffect } from 'react';
import Button from '../ui/Button';
import { jadwalApi, rescheduleApi } from '../../utils/api';
import { formatJadwalDatetime } from '../../utils/generateIcs';

/**
 * RescheduleForm — form ajukan perubahan jadwal oleh peserta
 */
const RescheduleForm = ({ nomor, penugasan, onClose, onSuccess }) => {
  const [slots, setSlots] = useState([]);
  const [jadwalBaruId, setJadwalBaruId] = useState('');
  const [alasan, setAlasan] = useState('');
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    const load = async () => {
      try {
        const res = await jadwalApi.getTersedia(penugasan.jadwal.jenis);
        setSlots(res.data.filter((s) => s.id !== penugasan.jadwal.id));
      } catch {
        setError('Gagal memuat slot tersedia');
      }
    };
    load();
  }, [penugasan]);

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError('');
    if (alasan.length < 20) {
      setError('Alasan minimal 20 karakter');
      return;
    }
    setLoading(true);
    try {
      await rescheduleApi.store({
        nomor_pendaftaran: nomor,
        penugasan_jadwal_id: penugasan.id,
        jadwal_tes_baru_id: Number(jadwalBaruId),
        alasan,
      });
      onSuccess();
    } catch (err) {
      setError(err.message || 'Gagal mengajukan reschedule');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="fixed inset-0 bg-black/40 flex items-end sm:items-center justify-center z-50 p-4">
      <div className="bg-white rounded-xl w-full max-w-md p-5 space-y-4 shadow-xl">
        <div className="flex justify-between items-center">
          <h3 className="font-semibold text-slate-800">Ajukan Reschedule</h3>
          <button onClick={onClose} className="text-slate-400 hover:text-slate-600 text-xl leading-none">
            ×
          </button>
        </div>

        <p className="text-xs text-slate-500">
          Jadwal saat ini: <strong>{penugasan.jadwal.judul}</strong>
        </p>

        <form onSubmit={handleSubmit} className="space-y-3">
          <div>
            <label className="block text-xs text-slate-600 mb-1">Pilih Slot Baru</label>
            <select
              value={jadwalBaruId}
              onChange={(e) => setJadwalBaruId(e.target.value)}
              className="w-full px-3 py-2.5 border border-slate-200 rounded-lg text-sm min-h-[44px]"
              required
            >
              <option value="">-- Pilih jadwal --</option>
              {slots.map((s) => (
                <option key={s.id} value={s.id}>
                  {s.judul} — {formatJadwalDatetime(s.tanggal_mulai)} (sisa {s.sisa_kuota})
                </option>
              ))}
            </select>
          </div>

          <div>
            <label className="block text-xs text-slate-600 mb-1">Alasan (min. 20 karakter)</label>
            <textarea
              value={alasan}
              onChange={(e) => setAlasan(e.target.value)}
              rows={3}
              className="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm resize-none"
              placeholder="Jelaskan alasan Anda tidak bisa hadir di jadwal ini..."
            />
          </div>

          {error && (
            <p className="text-xs text-red-600 bg-red-50 border border-red-200 rounded-lg px-3 py-2">
              {error}
            </p>
          )}

          <div className="flex gap-2">
            <Button type="button" variant="secondary" className="flex-1" onClick={onClose}>
              Batal
            </Button>
            <Button type="submit" variant="primary" className="flex-1" disabled={loading}>
              {loading ? 'Mengirim...' : 'Ajukan'}
            </Button>
          </div>
        </form>
      </div>
    </div>
  );
};

export default RescheduleForm;
