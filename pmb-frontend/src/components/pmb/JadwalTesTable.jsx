import { useState } from 'react';
import Button from '../ui/Button';
import { penugasanApi, jadwalApi } from '../../utils/api';
import { PRODI_LIST } from '../../constants';
import { formatJadwalDatetime } from '../../utils/generateIcs';
import KehadiranTable from './KehadiranTable';

/**
 * JadwalTesTable — tabel slot jadwal dengan assign & auto-batch (admin)
 */
const JadwalTesTable = ({ jadwalList, pendaftarList, onRefresh }) => {
  const [selectedId, setSelectedId] = useState(null);
  const [assignPendaftarId, setAssignPendaftarId] = useState('');
  const [batchProdi, setBatchProdi] = useState(PRODI_LIST[0]);
  const [loading, setLoading] = useState(false);
  const [message, setMessage] = useState('');
  const [error, setError] = useState('');

  const selected = jadwalList.find((j) => j.id === selectedId);

  const eligiblePendaftar = (jenis) => {
    if (jenis === 'wawancara') {
      return pendaftarList.filter(
        (p) => p.status === 'Lolos Seleksi' && !p.heregistrasi_at
      );
    }
    return pendaftarList.filter((p) => p.status === 'Menunggu');
  };

  const handleAssign = async () => {
    if (!selectedId || !assignPendaftarId) return;
    setLoading(true);
    setError('');
    setMessage('');
    try {
      await penugasanApi.store({
        jadwal_tes_id: selectedId,
        pendaftar_id: Number(assignPendaftarId),
      });
      setMessage('Peserta berhasil ditugaskan');
      setAssignPendaftarId('');
      onRefresh?.();
    } catch (err) {
      setError(err.message || 'Gagal assign peserta');
    } finally {
      setLoading(false);
    }
  };

  const handleAutoBatch = async () => {
    if (!selected) return;
    setLoading(true);
    setError('');
    setMessage('');
    try {
      const res = await penugasanApi.autoBatch({
        jadwal_tes_id: selected.id,
        prodi: batchProdi,
        jenis: selected.jenis,
      });
      setMessage(res.message);
      onRefresh?.();
    } catch (err) {
      setError(err.message || 'Gagal auto-batch');
    } finally {
      setLoading(false);
    }
  };

  const handleDeactivate = async (id) => {
    if (!confirm('Nonaktifkan slot jadwal ini?')) return;
    try {
      await jadwalApi.destroy(id);
      onRefresh?.();
    } catch (err) {
      setError(err.message || 'Gagal menonaktifkan');
    }
  };

  return (
    <div className="space-y-4">
      <div className="bg-white border border-slate-200 rounded-xl overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full text-sm">
            <thead className="bg-slate-50 border-b border-slate-200">
              <tr>
                <th className="text-left px-4 py-3 text-xs font-semibold text-slate-600">Judul</th>
                <th className="text-left px-4 py-3 text-xs font-semibold text-slate-600">Jenis</th>
                <th className="text-left px-4 py-3 text-xs font-semibold text-slate-600">Waktu</th>
                <th className="text-left px-4 py-3 text-xs font-semibold text-slate-600">Kapasitas</th>
                <th className="text-left px-4 py-3 text-xs font-semibold text-slate-600">Status</th>
                <th className="px-4 py-3"></th>
              </tr>
            </thead>
            <tbody>
              {jadwalList.length === 0 && (
                <tr>
                  <td colSpan={6} className="px-4 py-8 text-center text-slate-400">
                    Belum ada jadwal. Buat slot baru di form atas.
                  </td>
                </tr>
              )}
              {jadwalList.map((j) => (
                <tr
                  key={j.id}
                  className={`border-b border-slate-100 cursor-pointer hover:bg-blue-50/50 ${
                    selectedId === j.id ? 'bg-blue-50' : ''
                  }`}
                  onClick={() => setSelectedId(j.id)}
                >
                  <td className="px-4 py-3 font-medium text-slate-800">{j.judul}</td>
                  <td className="px-4 py-3">
                    <span
                      className={`text-xs px-2 py-0.5 rounded-full ${
                        j.jenis === 'wawancara'
                          ? 'bg-green-100 text-green-800'
                          : 'bg-amber-100 text-amber-800'
                      }`}
                    >
                      {j.jenis === 'wawancara' ? 'Wawancara' : 'Tes Seleksi'}
                    </span>
                  </td>
                  <td className="px-4 py-3 text-xs text-slate-600">
                    {formatJadwalDatetime(j.tanggal_mulai)}
                  </td>
                  <td className="px-4 py-3 text-slate-700">
                    {j.terisi ?? 0}/{j.kapasitas}
                  </td>
                  <td className="px-4 py-3">
                    <span
                      className={`text-xs px-2 py-0.5 rounded-full ${
                        j.status === 'aktif'
                          ? 'bg-green-100 text-green-800'
                          : 'bg-slate-100 text-slate-600'
                      }`}
                    >
                      {j.status}
                    </span>
                  </td>
                  <td className="px-4 py-3">
                    {j.status === 'aktif' && (
                      <button
                        onClick={(e) => {
                          e.stopPropagation();
                          handleDeactivate(j.id);
                        }}
                        className="text-xs text-red-600 hover:underline"
                      >
                        Nonaktifkan
                      </button>
                    )}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>

      {selected && (
        <div className="bg-white border border-slate-200 rounded-xl p-4 space-y-3">
          <h4 className="text-sm font-semibold text-slate-700">
            Kelola: {selected.judul}
          </h4>

          <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label className="block text-xs text-slate-600 mb-1">Assign Manual</label>
              <div className="flex gap-2">
                <select
                  value={assignPendaftarId}
                  onChange={(e) => setAssignPendaftarId(e.target.value)}
                  className="flex-1 px-3 py-2 border border-slate-200 rounded-lg text-sm min-h-[44px]"
                >
                  <option value="">-- Pilih pendaftar --</option>
                  {eligiblePendaftar(selected.jenis).map((p) => (
                    <option key={p.id} value={p.id}>
                      {p.nama} ({p.nomor_pendaftaran})
                    </option>
                  ))}
                </select>
                <Button variant="primary" onClick={handleAssign} disabled={loading || !assignPendaftarId}>
                  Assign
                </Button>
              </div>
            </div>
            <div>
              <label className="block text-xs text-slate-600 mb-1">Auto-Batch per Prodi</label>
              <div className="flex gap-2">
                <select
                  value={batchProdi}
                  onChange={(e) => setBatchProdi(e.target.value)}
                  className="flex-1 px-3 py-2 border border-slate-200 rounded-lg text-sm min-h-[44px]"
                >
                  {PRODI_LIST.map((p) => (
                    <option key={p} value={p}>{p}</option>
                  ))}
                </select>
                <Button variant="secondary" onClick={handleAutoBatch} disabled={loading}>
                  Auto-Batch
                </Button>
              </div>
            </div>
          </div>

          <KehadiranTable jadwalTesId={selected.id} />
        </div>
      )}

      {message && (
        <p className="text-xs text-green-700 bg-green-50 border border-green-200 rounded-lg px-3 py-2">
          {message}
        </p>
      )}
      {error && (
        <p className="text-xs text-red-600 bg-red-50 border border-red-200 rounded-lg px-3 py-2">
          {error}
        </p>
      )}
    </div>
  );
};

export default JadwalTesTable;
