import { useState, useEffect } from 'react';
import { kehadiranApi } from '../../utils/api';

/**
 * KehadiranTable — laporan kehadiran per sesi jadwal (admin)
 */
const KehadiranTable = ({ jadwalTesId }) => {
  const [data, setData] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const load = async () => {
      setLoading(true);
      try {
        const res = await kehadiranApi.getSesi(jadwalTesId);
        setData(res.data);
      } catch {
        setData(null);
      } finally {
        setLoading(false);
      }
    };
    if (jadwalTesId) load();
  }, [jadwalTesId]);

  if (loading) return <p className="text-xs text-slate-400">Memuat kehadiran...</p>;
  if (!data) return null;

  return (
    <div className="mt-3 border-t border-slate-100 pt-3">
      <div className="flex items-center justify-between mb-2">
        <h5 className="text-xs font-semibold text-slate-600">Laporan Kehadiran</h5>
        <span className="text-xs text-slate-500">
          {data.hadir}/{data.total} hadir
        </span>
      </div>
      {data.peserta.length === 0 ? (
        <p className="text-xs text-slate-400">Belum ada peserta ditugaskan.</p>
      ) : (
        <div className="overflow-x-auto">
          <table className="w-full text-xs">
            <thead>
              <tr className="text-slate-500">
                <th className="text-left py-1">Nama</th>
                <th className="text-left py-1">Nomor</th>
                <th className="text-left py-1">Status</th>
              </tr>
            </thead>
            <tbody>
              {data.peserta.map((p) => (
                <tr key={p.id} className="border-t border-slate-50">
                  <td className="py-1.5 text-slate-700">{p.nama}</td>
                  <td className="py-1.5 font-mono text-slate-500">{p.nomor_pendaftaran}</td>
                  <td className="py-1.5">
                    <span
                      className={`px-1.5 py-0.5 rounded-full ${
                        p.kehadiran
                          ? 'bg-green-100 text-green-800'
                          : 'bg-slate-100 text-slate-500'
                      }`}
                    >
                      {p.kehadiran ? 'Hadir' : 'Belum'}
                    </span>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </div>
  );
};

export default KehadiranTable;
