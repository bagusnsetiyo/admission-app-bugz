import { useState, useEffect, useRef } from 'react';
import { Html5QrcodeScanner } from 'html5-qrcode';
import Button from '../components/ui/Button';
import { kehadiranApi } from '../utils/api';

/**
 * Operator — halaman scan QR check-in untuk panitia lapangan
 */
const Operator = () => {
  const [pin, setPin] = useState('');
  const [pinOk, setPinOk] = useState(false);
  const [manualToken, setManualToken] = useState('');
  const [result, setResult] = useState(null);
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);
  const scannerRef = useRef(null);

  const doCheckin = async (token) => {
    if (!pinOk) {
      setError('Masukkan PIN operator terlebih dahulu');
      return;
    }
    setLoading(true);
    setError('');
    setResult(null);
    try {
      const res = await kehadiranApi.checkin(token.trim(), pin);
      setResult(res.data);
    } catch (err) {
      setError(err.message || 'Check-in gagal');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    if (!pinOk) return;

    const scanner = new Html5QrcodeScanner(
      'qr-reader',
      { fps: 10, qrbox: { width: 250, height: 250 } },
      false
    );

    scanner.render(
      (decodedText) => {
        doCheckin(decodedText);
      },
      () => {}
    );

    scannerRef.current = scanner;

    return () => {
      scanner.clear().catch(() => {});
    };
  }, [pinOk]);

  const handlePinSubmit = (e) => {
    e.preventDefault();
    if (pin.length < 4) {
      setError('PIN minimal 4 karakter');
      return;
    }
    setPinOk(true);
    setError('');
  };

  if (!pinOk) {
    return (
      <div className="min-h-screen bg-slate-50 flex items-center justify-center p-4">
        <div className="bg-white rounded-xl border border-slate-200 p-6 w-full max-w-sm space-y-4 shadow-sm">
          <div className="text-center">
            <div className="w-12 h-12 bg-blue-600 rounded-xl flex items-center justify-center mx-auto mb-3">
              <svg className="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
              </svg>
            </div>
            <h1 className="font-bold text-slate-800">Mode Operator</h1>
            <p className="text-xs text-slate-500 mt-1">Masukkan PIN untuk scan tiket peserta</p>
          </div>
          <form onSubmit={handlePinSubmit} className="space-y-3">
            <input
              type="password"
              value={pin}
              onChange={(e) => setPin(e.target.value)}
              placeholder="PIN Operator"
              className="w-full px-3 py-3 border border-slate-200 rounded-lg text-center text-lg tracking-widest min-h-[44px]"
            />
            {error && <p className="text-xs text-red-600 text-center">{error}</p>}
            <Button type="submit" variant="primary" className="w-full">
              Masuk
            </Button>
          </form>
          <a href="/" className="block text-center text-xs text-blue-600 hover:underline">
            ← Kembali ke Beranda
          </a>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-slate-50">
      <header className="bg-white border-b border-slate-200 px-4 py-3">
        <div className="max-w-lg mx-auto flex items-center justify-between">
          <h1 className="font-bold text-slate-800 text-sm">Scanner Check-in</h1>
          <a href="/" className="text-xs text-slate-500">Beranda</a>
        </div>
      </header>

      <div className="max-w-lg mx-auto p-4 space-y-4">
        <div id="qr-reader" className="rounded-xl overflow-hidden" />

        {loading && (
          <div className="text-center text-sm text-slate-500">Memproses check-in...</div>
        )}

        {result && (
          <div className="bg-green-50 border border-green-200 rounded-xl p-4 text-center space-y-1">
            <p className="text-green-700 font-bold text-lg">✓ Hadir</p>
            <p className="font-semibold text-slate-800">{result.nama}</p>
            <p className="text-sm text-slate-600 font-mono">{result.nomor_pendaftaran}</p>
            <p className="text-xs text-slate-500">{result.prodi} — {result.jadwal}</p>
            <p className="text-xs text-slate-400">{result.lokasi}</p>
          </div>
        )}

        {error && (
          <div className="bg-red-50 border border-red-200 rounded-xl p-4 text-center">
            <p className="text-red-700 font-medium text-sm">{error}</p>
          </div>
        )}

        <div className="bg-white border border-slate-200 rounded-xl p-4 space-y-2">
          <p className="text-xs text-slate-500 font-medium">Fallback: Input Token Manual</p>
          <div className="flex gap-2">
            <input
              type="text"
              value={manualToken}
              onChange={(e) => setManualToken(e.target.value)}
              placeholder="Paste token QR di sini"
              className="flex-1 px-3 py-2 border border-slate-200 rounded-lg text-xs min-h-[44px]"
            />
            <Button
              variant="secondary"
              className="text-xs"
              onClick={() => doCheckin(manualToken)}
              disabled={loading || !manualToken}
            >
              Check-in
            </Button>
          </div>
        </div>
      </div>
    </div>
  );
};

export default Operator;
