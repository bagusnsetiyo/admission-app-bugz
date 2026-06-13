import { useState } from 'react';
import Button from '../ui/Button';
import { jadwalApi } from '../../utils/api';
import { JENIS_JADWAL_LIST } from '../../constants';

/**
 * JadwalTesForm — form buat slot jadwal baru (admin)
 */
const JadwalTesForm = ({ onSuccess }) => {
  const [form, setForm] = useState({
    jenis: 'tes_seleksi',
    judul: '',
    tanggal_mulai: '',
    tanggal_selesai: '',
    lokasi: '',
    kapasitas: 30,
    catatan: '',
  });
  const [errors, setErrors] = useState({});
  const [loading, setLoading] = useState(false);
  const [success, setSuccess] = useState('');

  const handleChange = (field, value) => {
    setForm((prev) => ({ ...prev, [field]: value }));
    setErrors((prev) => ({ ...prev, [field]: '' }));
  };

  const validate = () => {
    const e = {};
    if (!form.judul.trim()) e.judul = 'Judul wajib diisi';
    if (!form.tanggal_mulai) e.tanggal_mulai = 'Tanggal mulai wajib diisi';
    if (!form.tanggal_selesai) e.tanggal_selesai = 'Tanggal selesai wajib diisi';
    if (form.tanggal_mulai && form.tanggal_selesai && form.tanggal_selesai <= form.tanggal_mulai) {
      e.tanggal_selesai = 'Tanggal selesai harus setelah mulai';
    }
    if (!form.lokasi.trim()) e.lokasi = 'Lokasi wajib diisi';
    if (!form.kapasitas || form.kapasitas < 1) e.kapasitas = 'Kapasitas minimal 1';
    return e;
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    const e2 = validate();
    if (Object.keys(e2).length) {
      setErrors(e2);
      return;
    }
    setLoading(true);
    setSuccess('');
    try {
      await jadwalApi.store(form);
      setSuccess('Jadwal berhasil dibuat');
      setForm({
        jenis: 'tes_seleksi',
        judul: '',
        tanggal_mulai: '',
        tanggal_selesai: '',
        lokasi: '',
        kapasitas: 30,
        catatan: '',
      });
      onSuccess?.();
    } catch (err) {
      setErrors({ form: err.message || 'Gagal menyimpan jadwal' });
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="bg-white border border-slate-200 rounded-xl p-4">
      <h3 className="text-sm font-semibold text-slate-700 mb-3">Buat Slot Jadwal Baru</h3>
      <form onSubmit={handleSubmit} className="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div>
          <label className="block text-xs text-slate-600 mb-1">Jenis</label>
          <select
            value={form.jenis}
            onChange={(e) => handleChange('jenis', e.target.value)}
            className="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm min-h-[44px]"
          >
            {JENIS_JADWAL_LIST.map((j) => (
              <option key={j.value} value={j.value}>{j.label}</option>
            ))}
          </select>
        </div>
        <div>
          <label className="block text-xs text-slate-600 mb-1">Judul</label>
          <input
            type="text"
            value={form.judul}
            onChange={(e) => handleChange('judul', e.target.value)}
            className="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm min-h-[44px]"
            placeholder="Tes Seleksi TI Gelombang 1"
          />
          {errors.judul && <p className="text-xs text-red-600 mt-0.5">{errors.judul}</p>}
        </div>
        <div>
          <label className="block text-xs text-slate-600 mb-1">Mulai</label>
          <input
            type="datetime-local"
            value={form.tanggal_mulai}
            onChange={(e) => handleChange('tanggal_mulai', e.target.value)}
            className="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm min-h-[44px]"
          />
          {errors.tanggal_mulai && <p className="text-xs text-red-600 mt-0.5">{errors.tanggal_mulai}</p>}
        </div>
        <div>
          <label className="block text-xs text-slate-600 mb-1">Selesai</label>
          <input
            type="datetime-local"
            value={form.tanggal_selesai}
            onChange={(e) => handleChange('tanggal_selesai', e.target.value)}
            className="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm min-h-[44px]"
          />
          {errors.tanggal_selesai && <p className="text-xs text-red-600 mt-0.5">{errors.tanggal_selesai}</p>}
        </div>
        <div>
          <label className="block text-xs text-slate-600 mb-1">Lokasi</label>
          <input
            type="text"
            value={form.lokasi}
            onChange={(e) => handleChange('lokasi', e.target.value)}
            className="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm min-h-[44px]"
            placeholder="Gedung A Lantai 2"
          />
          {errors.lokasi && <p className="text-xs text-red-600 mt-0.5">{errors.lokasi}</p>}
        </div>
        <div>
          <label className="block text-xs text-slate-600 mb-1">Kapasitas</label>
          <input
            type="number"
            value={form.kapasitas}
            onChange={(e) => handleChange('kapasitas', Number(e.target.value))}
            className="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm min-h-[44px]"
            min={1}
          />
          {errors.kapasitas && <p className="text-xs text-red-600 mt-0.5">{errors.kapasitas}</p>}
        </div>
        <div className="sm:col-span-2">
          <label className="block text-xs text-slate-600 mb-1">Catatan (opsional)</label>
          <textarea
            value={form.catatan}
            onChange={(e) => handleChange('catatan', e.target.value)}
            rows={2}
            className="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm resize-none"
          />
        </div>
        {errors.form && (
          <p className="sm:col-span-2 text-xs text-red-600 bg-red-50 border border-red-200 rounded-lg px-3 py-2">
            {errors.form}
          </p>
        )}
        {success && (
          <p className="sm:col-span-2 text-xs text-green-700 bg-green-50 border border-green-200 rounded-lg px-3 py-2">
            {success}
          </p>
        )}
        <div className="sm:col-span-2">
          <Button type="submit" variant="primary" disabled={loading}>
            {loading ? 'Menyimpan...' : 'Simpan Jadwal'}
          </Button>
        </div>
      </form>
    </div>
  );
};

export default JadwalTesForm;
