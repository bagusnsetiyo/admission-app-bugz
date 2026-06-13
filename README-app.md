# PMB App — Modul JadwalHub

## Menjalankan

```bash
# Backend
cd pmb-backend
composer install
cp .env.example .env   # jika belum ada
php artisan key:generate
touch database/database.sqlite   # jika belum ada
php artisan migrate --seed
php artisan serve
# → http://localhost:8000

# Frontend
cd pmb-frontend
npm install
npm run dev
# → http://localhost:5173
```

## Fitur Baru (JadwalHub)

| Fitur | Status |
|-------|--------|
| CRUD slot jadwal (tes seleksi / wawancara) | ✅ |
| Assign manual & auto-batch per prodi | ✅ |
| Panel jadwal + QR tiket di Cek Status | ✅ |
| Download kalender .ics | ✅ |
| Ajukan & kelola reschedule | ✅ |
| Halaman operator scan QR (`/operator`) | ✅ |
| Laporan kehadiran per sesi | ✅ |
| Log notifikasi in-app | ✅ |

## Data Demo (Seeder)

| Nomor | Nama | Jadwal |
|-------|------|--------|
| PMB-2025-1001 | Andi Pratama | Tes Seleksi TI Gelombang 1 |
| PMB-2025-1002 | Siti Rahayu | Wawancara PMB Gelombang 1 |

**Admin:** `admin` / `pmb2025`  
**Operator PIN:** `pmblapangan2025` (env `OPERATOR_PIN`)

## Fitur Lama — Konfirmasi Normal

- Pendaftaran online → ✅ tidak diubah
- Cek status by nomor → ✅ tidak diubah
- Heregistrasi → ✅ tidak diubah
- Admin login/logout Sanctum → ✅ tidak diubah
- Tabel pendaftar + filter → ✅ tidak diubah
- Statistik per prodi/jalur → ✅ tidak diubah
- Export CSV pendaftar → ✅ tidak diubah

## Halaman Baru

- **Admin** → tab "Jadwal Tes" (CRUD, assign, inbox reschedule)
- **Cek Status** → panel "Jadwal Saya" + QR + .ics
- **/operator** → scan QR check-in panitia lapangan
