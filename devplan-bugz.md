# Development Plan — Modul Penjadwalan Tes PMB
## devplan-bugz.md
### Vibe Coding & Venture SEVIMA | Pengembangan Lanjutan Aplikasi PMB
### Author: Bugz | Tema: Digitalisasi Jadwal Tes & Wawancara + Smart Check-in

---

> **Ringkasan konsep out-of-the-box:** Modul ini tidak hanya "buat jadwal + tampilkan jadwal". Modul **JadwalHub** menambahkan penjadwalan dua fase (tes seleksi untuk pendaftar `Menunggu`, wawancara untuk pendaftar `Lolos Seleksi`), auto-assign batch per prodi, tiket QR untuk check-in operator lapangan, export kalender `.ics` ke HP peserta, dan alur reschedule ber-approval — semuanya **additive** di atas sistem Fase 3 yang sudah berjalan.

---

## BAGIAN 1 — Analisa Teknis

### 1.1 Identifikasi Pengguna

| Pengguna | Peran dalam Modul Penjadwalan |
|----------|-------------------------------|
| **Admin PMB** *(sudah ada di sistem)* | Membuat slot jadwal tes/wawancara, menetapkan peserta (manual atau auto-batch per prodi), menyetujui/menolak permintaan reschedule, memantau kapasitas ruang, dan mengekspor daftar kehadiran. |
| **Calon Mahasiswa** *(sudah ada di sistem)* | Melihat jadwal tes/wawancara miliknya via tab Cek Status (input nomor pendaftaran), mengunduh tiket QR + file `.ics` ke kalender HP, serta mengajukan permintaan reschedule jika berhalangan. |
| **Operator Lapangan** *(pengguna baru, khusus modul ini)* | Memindai QR tiket peserta di hari-H untuk verifikasi kehadiran real-time, melihat daftar peserta per sesi, dan menandai status hadir/tidak hadir tanpa akses penuh ke dashboard admin. |
| **Sistem Otomatis (Scheduler)** *(aktor non-manusia)* | Menjalankan reminder H-1 via log notifikasi, mendeteksi konflik jadwal (dua slot overlap untuk satu pendaftar), dan menghasilkan token QR unik per penugasan. |

### 1.2 Fitur Utama per Pengguna

#### Admin PMB
1. **CRUD Slot Jadwal** — buat/edit/nonaktifkan slot dengan jenis (`tes_seleksi` / `wawancara`), tanggal, waktu, lokasi, kapasitas maks, dan catatan panitia.
2. **Penugasan Peserta** — assign pendaftar eligible ke slot (filter otomatis: `Menunggu` untuk tes, `Lolos Seleksi` + belum `heregistrasi_at` untuk wawancara).
3. **Auto-Batch per Prodi** — satu klik assign semua pendaftar prodi tertentu ke slot tersedia dengan round-robin kapasitas.
4. **Kelola Reschedule** — inbox permintaan reschedule dengan approve/reject + alasan.
5. **Laporan Kehadiran** — tabel kehadiran per sesi + export CSV (endpoint terpisah dari export pendaftar existing).

#### Calon Mahasiswa
1. **Lihat Jadwal Saya** — panel jadwal di `CekStatus.jsx` setelah lookup nomor (tanpa login).
2. **Tiket Digital + QR** — kartu tiket berisi nomor, nama, lokasi, waktu, dan QR code untuk check-in.
3. **Download Kalender (.ics)** — tambahkan jadwal ke Google Calendar / Apple Calendar.
4. **Ajukan Reschedule** — form alasan + pilihan slot alternatif yang masih ada kuota.
5. **Status Reschedule** — badge `Menunggu Persetujuan` / `Disetujui` / `Ditolak`.

#### Operator Lapangan
1. **Mode Scanner** — halaman ringan `/operator` (tanpa full admin dashboard) untuk scan QR.
2. **Verifikasi Tiket** — decode token QR → tampilkan data peserta + validasi sesi hari ini.
3. **Tandai Kehadiran** — tombol Hadir / Tidak Hadir dengan timestamp.
4. **Daftar Peserta Sesi** — filter per slot jadwal hari ini, real-time counter hadir vs total.
5. **Cari Manual** — fallback input nomor pendaftaran jika QR rusak/tidak terbaca.

#### Sistem Otomatis
1. **Conflict Guard** — tolak assign jika pendaftar sudah punya slot aktif di rentang waktu overlap.
2. **QR Token Generator** — generate `checkin_token` UUID per `penugasan_jadwal`.
3. **Reminder H-1** — Laravel Scheduler menulis ke `notifikasi_log` (prototype: tampil di admin, tanpa SMTP).
4. **Kapasitas Guard** — tolak assign jika `jumlah_peserta >= kapasitas`.
5. **Eligibility Guard** — validasi status pendaftar sesuai jenis jadwal sebelum assign.

### 1.3 Tech Stack yang Dipilih

| Komponen Tambahan | Teknologi | Alasan Pemilihan |
|-------------------|-----------|------------------|
| Kalender admin (form tanggal-waktu) | Native HTML `<input type="datetime-local">` | Zero dependency, konsisten dengan filosofi prototype ringan; cukup untuk slot jadwal tanpa full calendar view. |
| QR Code tiket | `qrcode.react` (npm) | Generate QR di browser dari `checkin_token`; tidak butuh backend image processing. |
| Export kalender peserta | Generator `.ics` custom di `utils/generateIcs.js` | File teks standar RFC 5545, bisa dibuka semua kalender HP tanpa integrasi Google API. |
| QR Scanner operator | `html5-qrcode` (npm) | Akses kamera browser di `/operator`, tidak perlu native app. |
| Reminder & background job | Laravel Scheduler + tabel `notifikasi_log` | Prototype tidak butuh SMTP; log in-app membuktikan alur reminder tanpa setup email server. |
| Validasi & auth | Pola existing: Sanctum + FormRequest | Konsisten dengan `StorePendaftarRequest`, `UpdateStatusRequest`, dan middleware `auth:sanctum`. |
| HTTP client | Fetch API via `api.js` | Ikuti konvensi `pendaftarApi` / `statistikApi` — tambah `jadwalApi`, `rescheduleApi`, `kehadiranApi`. |

### 1.4 Batasan & Asumsi

| # | Batasan / Asumsi | Dampak ke Integrasi |
|---|------------------|---------------------|
| 1 | **Tidak mengubah skema `pendaftars`** — relasi hanya via FK di tabel baru `penugasan_jadwal.pendaftar_id`. | Fitur pendaftaran, cek status, update status, heregistrasi, statistik, dan export CSV existing **tidak terpengaruh**. |
| 2 | **Tidak mengubah 9 route API existing** — semua endpoint jadwal adalah route baru di `api.php`. | Route regex `GET /pendaftar/{nomor}` dan `GET /pendaftar/export/csv` tidak bentrok karena path baru menggunakan prefix `/jadwal-tes`, `/penugasan`, `/reschedule`. |
| 3 | **Eligibility rule:** tes seleksi hanya untuk `status = 'Menunggu'`; wawancara hanya untuk `status = 'Lolos Seleksi'` AND `heregistrasi_at IS NULL`. | Menjaga konsistensi bisnis: tes → seleksi → wawancara → heregistrasi (fitur Fase 3). |
| 4 | **Prototype single-kampus, single-timezone (WIB/Asia/Jakarta)** — semua datetime disimpan UTC di DB, ditampilkan WIB di UI. | Menghindari kompleksitas multi-timezone yang out-of-scope PRD. |
| 5 | **Notifikasi H-1 in-app only** (tabel `notifikasi_log`), tanpa integrasi WhatsApp/email/SMS. | Memecahkan pain point "informasi tidak terpusat" di admin dashboard tanpa dependency eksternal. |
| 6 | **Operator auth: PIN sederahan** disimpan di `.env` (`OPERATOR_PIN=pmblapangan2025`) — bukan user Sanctum terpisah. | Cukup untuk demo UAT; tidak menambah tabel `users` atau mengubah flow login admin existing. |

---

## BAGIAN 2 — Bisnis Proses & Flow

### 2.1 Flow Utama: Penjadwalan Tes Seleksi

```
[ADMIN PMB] → Login via Sanctum (flow existing) → [Dashboard Admin terbuka]
       ↓
[ADMIN PMB] → Buka tab baru "Jadwal Tes" di Admin.jsx → [Panel JadwalHub tampil]
       ↓
[ADMIN PMB] → Buat slot jadwal: jenis=tes_seleksi, tanggal, waktu, lokasi, kapasitas=30
       → [SISTEM] → Validasi FormRequest → INSERT ke tabel jadwal_tes → [Slot tersimpan, status=aktif]
       ↓
[ADMIN PMB] → Klik "Auto-Batch" → pilih prodi=Teknik Informatika
       → [SISTEM] → SELECT pendaftars WHERE status='Menunggu' AND prodi='Teknik Informatika'
                    AND id NOT IN (penugasan aktif tes_seleksi)
       → [SISTEM] → Round-robin assign ke slot tersedia (cek kapasitas + conflict guard)
       → INSERT batch ke penugasan_jadwal + generate checkin_token UUID
       → INSERT notifikasi_log "Jadwal tes ditetapkan" per pendaftar
       → [X peserta berhasil ditugaskan, Y gagal (kapasitas penuh)]
       ↓ jika [pendaftar status berubah menjadi 'Lolos Seleksi' setelah tes]
[ADMIN PMB] → Buat slot jadwal: jenis=wawancara
       → [SISTEM] → Eligible: status='Lolos Seleksi' AND heregistrasi_at IS NULL
       → [ADMIN PMB] → Assign manual atau auto-batch pendaftar lolos
       → [SISTEM] → INSERT penugasan_jadwal (jenis wawancara) + notifikasi_log
       ↓
[CALON MAHASISWA] → Buka Home → tab "Cek Status" → input nomor PMB-2025-XXXX (flow existing)
       → [SISTEM] → GET /api/pendaftar/{nomor} (endpoint existing, tidak diubah)
       → GET /api/pendaftar/{nomor}/jadwal (endpoint baru, read-only)
       → JOIN penugasan_jadwal + jadwal_tes WHERE pendaftar_id match
       → [Panel Status existing + Panel Jadwal Saya baru tampil di CekStatus.jsx]
       ↓
[CALON MAHASISWA] → Download .ics / tampilkan QR tiket
       → [SISTEM] → Render QR dari checkin_token (frontend qrcode.react)
       ↓
[OPERATOR LAPANGAN] → Buka /operator → scan QR peserta
       → [SISTEM] → POST /api/kehadiran/checkin { token }
       → Validasi: token valid, sesi hari ini, belum check-in
       → UPSERT kehadiran_tes status=hadir, checked_in_at=now()
       → [Tampil konfirmasi hijau: "Budi Santoso — Hadir ✓"]
       ↓
[ADMIN PMB] → Lihat laporan kehadiran sesi → export CSV kehadiran (endpoint baru, terpisah)
       → [HASIL: digitalisasi penuh — tidak perlu WhatsApp manual]
```

**Titik integrasi dengan sistem lama (eksplisit):**
- Membaca `pendaftars.id`, `status`, `prodi`, `nomor_pendaftaran`, `heregistrasi_at` — **READ ONLY**.
- UI jadwal ditambahkan di `CekStatus.jsx` **setelah** blok status existing.
- Admin tab jadwal ditambahkan di `Admin.jsx` **di bawah** `TabelPendaftar` existing — statistik & export pendaftar tidak diubah.

### 2.2 Flow Alternatif: Peserta Minta Reschedule

```
[CALON MAHASISWA] → Di panel Jadwal Saya → klik "Ajukan Reschedule"
       → [SISTEM] → Tampilkan slot alternatif (GET /api/jadwal-tes/tersedia?jenis=...)
       ↓
[CALON MAHASISWA] → Pilih slot baru + isi alasan (min 20 karakter) → Submit
       → [SISTEM] → Validasi: punya penugasan aktif, belum ada permintaan pending
       → INSERT permintaan_reschedule status=menunggu
       → INSERT notifikasi_log ke admin
       → [Badge "Menunggu Persetujuan" tampil di CekStatus]
       ↓
[ADMIN PMB] → Buka inbox Reschedule di tab Jadwal Tes
       → [SISTEM] → GET /api/reschedule?status=menunggu (Sanctum)
       ↓ jika [APPROVE]
[ADMIN PMB] → Klik "Setujui"
       → [SISTEM] → BEGIN TRANSACTION
                    → UPDATE penugasan_jadwal: jadwal_tes_id = slot baru
                    → UPDATE permintaan_reschedule status=disetujui
                    → INSERT notifikasi_log ke peserta
                    → COMMIT
       → [Panel jadwal peserta otomatis menampilkan slot baru]
       ↓ jika [REJECT]
[ADMIN PMB] → Klik "Tolak" + isi alasan penolakan
       → [SISTEM] → UPDATE permintaan_reschedule status=ditolak, alasan_penolakan
       → [Peserta tetap di jadwal lama, badge merah "Ditolak" + alasan]
```

### 2.3 Happy Path vs Error Path

#### Happy Path (Flow 2.1)
1. Admin membuat slot → validasi lolos → slot aktif.
2. Auto-batch assign 25 pendaftar TI ke slot kapasitas 30 → 25 `penugasan_jadwal` tercipta + QR token.
3. Peserta cek status → jadwal tampil lengkap dengan lokasi & waktu.
4. Hari-H: operator scan QR → kehadiran tercatat.
5. Admin export laporan kehadiran → data akurat 25/25.

#### Error Path 1 — Kapasitas Penuh saat Assign
```
[ADMIN PMB] → Assign pendaftar ke slot kapasitas 30 yang sudah terisi 30
       → [SISTEM] → Kapasitas Guard → HTTP 422
       → Response: { success: false, message: "Slot sudah penuh (30/30)" }
       → [UI Admin] → Toast error merah, tidak ada data partial yang corrupt
```

#### Error Path 2 — Peserta Tidak Eligible
```
[ADMIN PMB] → Coba assign pendaftar berstatus 'Tidak Lolos' ke slot wawancara
       → [SISTEM] → Eligibility Guard → HTTP 422
       → Response: { success: false, message: "Pendaftar tidak memenuhi syarat jadwal wawancara" }
       → [UI Admin] → Pendaftar tidak masuk daftar assign, slot tidak berubah
```

#### Error Path 3 — QR Invalid / Sudah Check-in (tambahan)
```
[OPERATOR] → Scan QR token tidak valid atau sesi beda hari
       → [SISTEM] → HTTP 404 / 409
       → Response: { success: false, message: "Tiket tidak valid untuk sesi hari ini" }
       → [UI Operator] → Banner merah, tidak mengubah data kehadiran existing
```

#### Error Path 4 — Konflik Jadwal (pendaftar sudah punya slot overlap)
```
[ADMIN PMB] → Assign pendaftar yang sudah punya tes_seleksi di jam yang sama
       → [SISTEM] → Conflict Guard → HTTP 409
       → Response: { success: false, message: "Pendaftar sudah memiliki jadwal di rentang waktu tersebut" }
```

---

## BAGIAN 3 — Alur Data

### 3.1 Alur Data: Proses Penjadwalan

```
[Sumber: Form Admin di JadwalTesForm.jsx]
    → [Proses: validasi client-side + POST /api/jadwal-tes]
    → [Penyimpanan: JadwalTesController@store → StoreJadwalTesRequest → INSERT jadwal_tes]
    → [Proses: Admin klik Auto-Batch → POST /api/penugasan/auto-batch { prodi, jenis, jadwal_tes_id }]
    → [Baca: SELECT pendaftars WHERE status & prodi eligible (READ dari tabel existing)]
    → [Proses: PenugasanJadwalController → cek kapasitas + conflict + eligibility]
    → [Penyimpanan: INSERT batch penugasan_jadwal + UUID checkin_token]
    → [Penyimpanan: INSERT notifikasi_log per pendaftar]
    → [Output ke Admin: JadwalTesTable.jsx menampilkan slot + counter peserta/kapasitas]
```

### 3.2 Alur Data: Peserta Cek Jadwal

```
[Sumber: Input nomor pendaftaran di CekStatus.jsx]
    → [Proses: pendaftarApi.getByNomor(nomor) → GET /api/pendaftar/{nomor} (existing)]
    → [Penyimpanan READ: SELECT pendaftars WHERE nomor_pendaftaran = ?]
    → [Output: Panel status existing (nama, prodi, jalur, StatusBadge)]
    → [Proses: jadwalApi.getByNomor(nomor) → GET /api/pendaftar/{nomor}/jadwal (baru)]
    → [Penyimpanan READ: SELECT penugasan_jadwal JOIN jadwal_tes JOIN pendaftars
                          WHERE pendaftars.nomor_pendaftaran = ? AND penugasan.status != 'batal']
    → [Proses: JadwalPanel.jsx render kartu jadwal + qrcode.react(checkin_token)]
    → [Proses: generateIcs.js → blob download .ics]
    → [Output ke Peserta: kartu jadwal + QR + tombol reschedule di layar Cek Status]
```

### 3.3 Data Apa yang Sensitif?

| Field / Data | Perlakuan Khusus | Alasan |
|--------------|------------------|--------|
| `pendaftars.nomor_hp`, `pendaftars.email` | **Tidak ditampilkan** di halaman operator; hanya tampil di admin (Sanctum) dan panel pribadi peserta (setelah verifikasi nomor). | PII — mencegah kebocoran data saat layar operator terlihat publik di lapangan. |
| `checkin_token` (UUID) | **Hanya** ditampilkan sebagai QR, bukan plain text di UI peserta. Di-log backend hanya 8 karakter pertama untuk debug. | Token = tiket masuk; jika bocor bisa dipakai orang lain check-in. |
| `permintaan_reschedule.alasan` | Hanya visible untuk admin (Sanctum) dan pemilik permintaan (via nomor pendaftaran). | Data pribadi alasan ketidakhadiran (kesehatan, dll.). |
| `OPERATOR_PIN` | Disimpan di `.env`, tidak di-commit ke repo, divalidasi server-side. | Mencegah akses check-in tidak sah. |
| Data pendaftar `Tidak Lolos` | Endpoint `/pendaftar/{nomor}/jadwal` mengembalikan array kosong (bukan error), tanpa expose alasan status. | Minimalkan informasi yang tidak perlu untuk status gagal. |

---

## BAGIAN 4 — ERD / Desain Database

### 4.1 Daftar Tabel

| Nama Tabel | Deskripsi | Status |
|------------|-----------|--------|
| `pendaftars` | Data pendaftar PMB | ✅ **Sudah ada** — tidak diubah |
| `users` | Admin auth Sanctum | ✅ **Sudah ada** — tidak diubah |
| `jadwal_tes` | Master slot jadwal (tes seleksi & wawancara) | 🆕 Baru |
| `penugasan_jadwal` | Pivot: pendaftar ↔ slot jadwal + token QR | 🆕 Baru |
| `permintaan_reschedule` | Antrian permintaan ubah jadwal dari peserta | 🆕 Baru |
| `kehadiran_tes` | Record check-in operator per penugasan | 🆕 Baru |
| `notifikasi_log` | Log reminder & event in-app (prototype tanpa email) | 🆕 Baru |

### 4.2 Struktur Tiap Tabel

#### Tabel: `jadwal_tes` (baru)

| Nama Kolom | Tipe Data | Constraint | Keterangan |
|------------|-----------|------------|------------|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | — |
| `jenis` | VARCHAR(20) | NOT NULL | `tes_seleksi` atau `wawancara` |
| `judul` | VARCHAR(100) | NOT NULL | Contoh: "Tes Seleksi TI Gelombang 1" |
| `tanggal_mulai` | DATETIME | NOT NULL | Waktu mulai sesi (UTC) |
| `tanggal_selesai` | DATETIME | NOT NULL | Waktu selesai sesi (UTC) |
| `lokasi` | VARCHAR(150) | NOT NULL | Ruang/lokasi fisik |
| `kapasitas` | SMALLINT UNSIGNED | NOT NULL, DEFAULT 30 | Maks peserta per sesi |
| `status` | VARCHAR(20) | NOT NULL, DEFAULT 'aktif' | `aktif`, `nonaktif`, `selesai` |
| `catatan` | TEXT | NULLABLE | Instruksi untuk peserta |
| `created_by` | BIGINT UNSIGNED | FK → `users.id`, NULLABLE | Admin pembuat |
| `created_at` | TIMESTAMP | NOT NULL | Laravel timestamps |
| `updated_at` | TIMESTAMP | NOT NULL | Laravel timestamps |

#### Tabel: `penugasan_jadwal` (baru)

| Nama Kolom | Tipe Data | Constraint | Keterangan |
|------------|-----------|------------|------------|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | — |
| `jadwal_tes_id` | BIGINT UNSIGNED | FK → `jadwal_tes.id`, NOT NULL, ON DELETE RESTRICT | Slot yang ditugaskan |
| `pendaftar_id` | BIGINT UNSIGNED | FK → `pendaftars.id`, NOT NULL, ON DELETE RESTRICT | Peserta |
| `checkin_token` | CHAR(36) | NOT NULL, UNIQUE | UUID v4 untuk QR tiket |
| `status` | VARCHAR(20) | NOT NULL, DEFAULT 'terjadwal' | `terjadwal`, `hadir`, `tidak_hadir`, `batal` |
| `ditugaskan_oleh` | BIGINT UNSIGNED | FK → `users.id`, NULLABLE | Admin yang assign |
| `ditugaskan_at` | TIMESTAMP | NOT NULL | Waktu penugasan |
| `created_at` | TIMESTAMP | NOT NULL | — |
| `updated_at` | TIMESTAMP | NOT NULL | — |

> **UNIQUE constraint bisnis:** `UNIQUE(pendaftar_id, jadwal_tes_id)` — cegah double assign ke slot sama.

#### Tabel: `permintaan_reschedule` (baru)

| Nama Kolom | Tipe Data | Constraint | Keterangan |
|------------|-----------|------------|------------|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | — |
| `penugasan_jadwal_id` | BIGINT UNSIGNED | FK → `penugasan_jadwal.id`, NOT NULL | Penugasan yang ingin diubah |
| `jadwal_tes_baru_id` | BIGINT UNSIGNED | FK → `jadwal_tes.id`, NOT NULL | Slot tujuan |
| `alasan` | TEXT | NOT NULL | Alasan peserta (min 20 char) |
| `status` | VARCHAR(20) | NOT NULL, DEFAULT 'menunggu' | `menunggu`, `disetujui`, `ditolak` |
| `alasan_penolakan` | TEXT | NULLABLE | Diisi admin jika ditolak |
| `diproses_oleh` | BIGINT UNSIGNED | FK → `users.id`, NULLABLE | Admin reviewer |
| `diproses_at` | TIMESTAMP | NULLABLE | Waktu keputusan |
| `created_at` | TIMESTAMP | NOT NULL | — |
| `updated_at` | TIMESTAMP | NOT NULL | — |

#### Tabel: `kehadiran_tes` (baru)

| Nama Kolom | Tipe Data | Constraint | Keterangan |
|------------|-----------|------------|------------|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | — |
| `penugasan_jadwal_id` | BIGINT UNSIGNED | FK → `penugasan_jadwal.id`, NOT NULL, UNIQUE | Satu record per penugasan |
| `status` | VARCHAR(20) | NOT NULL | `hadir`, `tidak_hadir` |
| `checked_in_at` | TIMESTAMP | NULLABLE | Waktu scan QR |
| `operator_keterangan` | VARCHAR(100) | NULLABLE | Nama operator / "QR Scan" |
| `created_at` | TIMESTAMP | NOT NULL | — |
| `updated_at` | TIMESTAMP | NOT NULL | — |

#### Tabel: `notifikasi_log` (baru)

| Nama Kolom | Tipe Data | Constraint | Keterangan |
|------------|-----------|------------|------------|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | — |
| `pendaftar_id` | BIGINT UNSIGNED | FK → `pendaftars.id`, NULLABLE | Target peserta |
| `jenis` | VARCHAR(30) | NOT NULL | `jadwal_ditetapkan`, `reminder_h1`, `reschedule_disetujui`, dll. |
| `pesan` | TEXT | NOT NULL | Isi notifikasi human-readable |
| `dibaca` | BOOLEAN | NOT NULL, DEFAULT false | Untuk badge admin |
| `created_at` | TIMESTAMP | NOT NULL | — |
| `updated_at` | TIMESTAMP | NOT NULL | — |

### 4.3 Relasi Antar Tabel

```
pendaftars ---(1:N)--- penugasan_jadwal
Keterangan: Satu pendaftar bisa punya beberapa penugasan (tes seleksi + wawancara di fase berbeda),
            tapi tidak boleh overlap waktu untuk jenis yang sama.

jadwal_tes ---(1:N)--- penugasan_jadwal
Keterangan: Satu slot menampung banyak peserta hingga batas kapasitas.

penugasan_jadwal ---(1:1)--- kehadiran_tes
Keterangan: Setiap penugasan punya maksimal satu record kehadiran setelah check-in.

penugasan_jadwal ---(1:N)--- permintaan_reschedule
Keterangan: Riwayat permintaan reschedule; hanya satu yang boleh berstatus 'menunggu' (enforced di app layer).

pendaftars ---(1:N)--- notifikasi_log
Keterangan: Log event yang menyangkut pendaftar tertentu.

users ---(1:N)--- jadwal_tes (via created_by)
Keterangan: Audit trail siapa admin yang membuat slot.

users ---(1:N)--- penugasan_jadwal (via ditugaskan_oleh)
Keterangan: Audit trail siapa yang menugaskan peserta.

users ---(1:N)--- permintaan_reschedule (via diproses_oleh)
Keterangan: Audit trail admin yang approve/reject reschedule.
```

### 4.4 Indexing

| Tabel | Kolom | Jenis Index | Alasan |
|-------|-------|-------------|--------|
| `penugasan_jadwal` | `pendaftar_id` | INDEX | JOIN utama saat `GET /pendaftar/{nomor}/jadwal` — filter by pendaftar setiap kali cek status. |
| `penugasan_jadwal` | `jadwal_tes_id` | INDEX | COUNT peserta per slot (kapasitas guard) — query `WHERE jadwal_tes_id = ?` di setiap assign. |
| `penugasan_jadwal` | `checkin_token` | UNIQUE INDEX | Lookup O(1) saat operator scan QR — `WHERE checkin_token = ?`. |
| `jadwal_tes` | `tanggal_mulai` | INDEX | Filter slot hari ini di halaman operator dan reminder H-1 scheduler. |
| `jadwal_tes` | `(jenis, status)` | COMPOSITE INDEX | Filter slot aktif per jenis saat auto-batch dan tampilan slot tersedia. |
| `permintaan_reschedule` | `status` | INDEX | Inbox admin: `WHERE status = 'menunggu'` — hot path di dashboard. |
| `notifikasi_log` | `(pendaftar_id, dibaca)` | COMPOSITE INDEX | Badge unread di admin/notifikasi panel. |
| `pendaftars` | `nomor_pendaftaran` | UNIQUE (sudah ada) | Reuse index existing — endpoint jadwal lookup by nomor JOIN ke tabel ini. |

---

## BAGIAN 5 — Prompt Siap Pakai untuk AI

Copy prompt di bawah ini **utuh** ke Claude/Cursor untuk generate kode modul JadwalHub.

---

```
[KONTEKS]
Kamu mengembangkan modul BARU di atas prototype PMB yang SUDAH BERJALAN. Jangan buat project dari nol.

Stack existing:
- Frontend: React 18 + Vite + Tailwind CSS di folder pmb-frontend/
- Backend: Laravel 12 + SQLite di folder pmb-backend/
- Auth admin: Laravel Sanctum (token di sessionStorage key pmb_admin_token)
- API base: http://localhost:8000/api
- Format response API WAJIB: { success, message?, data?, meta?, errors? }

Fitur yang SUDAH ADA dan TIDAK BOLEH DIRUSAK:
- FormPendaftaran.jsx → POST /api/pendaftar
- CekStatus.jsx → GET /api/pendaftar/{nomor}, heregistrasi POST /api/pendaftar/{nomor}/heregistrasi
- Admin.jsx → login, statistik, TabelPendaftar, export CSV
- 9 route existing di routes/api.php (auth, pendaftar CRUD, statistik, export)

Tabel existing:
- pendaftars: id, nomor_pendaftaran, nama, nomor_hp, email, asal_sekolah, prodi, jalur, status, heregistrasi_at, timestamps
- users: admin Sanctum

Konvensi kode (skill.md):
- Model PascalCase singular (JadwalTes), tabel snake_case plural (jadwal_tes)
- Controller di app/Http/Controllers/Api/, FormRequest di app/Http/Requests/
- API field snake_case, route kebab-case
- React component di components/pmb/, API helper object di utils/api.js (jadwalApi, penugasanApi, rescheduleApi, kehadiranApi)
- Warna UI: biru [#1a56db], amber aksen, slate teks — konsisten dengan Button.jsx dan StatusBadge.jsx existing
- JANGAN ubah signature/route/response PendaftarController existing
- JANGAN alter tabel pendaftars — relasi via FK di tabel baru saja

[TUJUAN]
Tambahkan modul "JadwalHub" — penjadwalan tes seleksi & wawancara PMB yang terintegrasi dengan data pendaftar existing.

Scope terukur (MVP modul ini):
1. Admin bisa CRUD slot jadwal (tes_seleksi / wawancara) dengan kapasitas
2. Admin bisa assign pendaftar eligible + auto-batch per prodi
3. Peserta bisa lihat jadwal via Cek Status (by nomor, tanpa login)
4. Peserta bisa download .ics dan lihat QR tiket
5. Peserta bisa ajukan reschedule; admin approve/reject
6. Operator bisa check-in via scan QR di halaman /operator (PIN dari env)
7. Semua fitur existing (pendaftaran, cek status, heregistrasi, admin dashboard, statistik, export CSV) tetap berfungsi normal

[FITUR]

BACKEND — buat migration, model, controller, FormRequest, seeder untuk:

Tabel baru:
- jadwal_tes (jenis, judul, tanggal_mulai, tanggal_selesai, lokasi, kapasitas, status, catatan, created_by)
- penugasan_jadwal (jadwal_tes_id FK, pendaftar_id FK, checkin_token UUID unique, status, ditugaskan_oleh, ditugaskan_at)
- permintaan_reschedule (penugasan_jadwal_id FK, jadwal_tes_baru_id FK, alasan, status, alasan_penolakan, diproses_oleh, diproses_at)
- kehadiran_tes (penugasan_jadwal_id FK unique, status, checked_in_at, operator_keterangan)
- notifikasi_log (pendaftar_id FK nullable, jenis, pesan, dibaca)

Model dengan relasi Eloquent:
- JadwalTes hasMany PenugasanJadwal, belongsTo User (created_by)
- Pendaftar hasMany PenugasanJadwal (TAMBAH relasi di model existing, jangan ubah fillable/status logic)
- PenugasanJadwal belongsTo JadwalTes, belongsTo Pendaftar, hasOne KehadiranTes

Endpoint BARU (jangan ubah route existing):

Publik:
- GET /api/pendaftar/{nomorPendaftaran}/jadwal — jadwal milik peserta (regex nomor sama existing)
- GET /api/jadwal-tes/tersedia?jenis=tes_seleksi — slot aktif yang masih ada kuota (untuk reschedule)
- POST /api/reschedule — ajukan reschedule { nomor_pendaftaran, penugasan_jadwal_id, jadwal_tes_baru_id, alasan }
- POST /api/kehadiran/checkin — { token, operator_pin } validasi PIN dari env OPERATOR_PIN

Admin (auth:sanctum):
- GET/POST/PATCH/DELETE /api/jadwal-tes — CRUD slot
- GET /api/jadwal-tes/{id}/peserta — daftar peserta per slot
- POST /api/penugasan — assign manual { jadwal_tes_id, pendaftar_id }
- POST /api/penugasan/auto-batch — { jadwal_tes_id, prodi, jenis }
- DELETE /api/penugasan/{id} — batalkan penugasan
- GET /api/reschedule — list permintaan (filter status)
- PATCH /api/reschedule/{id} — approve/reject { action: approve|reject, alasan_penolakan? }
- GET /api/kehadiran/sesi/{jadwalTesId} — laporan kehadiran
- GET /api/notifikasi — unread log untuk admin

Business rules WAJIB di controller/service:
- tes_seleksi: hanya pendaftar status='Menunggu'
- wawancara: hanya status='Lolos Seleksi' AND heregistrasi_at IS NULL
- Kapasitas guard: tolak assign jika slot penuh
- Conflict guard: tolak assign jika pendaftar punya penugasan aktif overlap waktu
- Reschedule: hanya 1 permintaan 'menunggu' per penugasan
- Checkin: token harus valid, jadwal hari ini, belum check-in

Seeder: 2 slot jadwal dummy + 2-3 penugasan untuk pendaftar seeder existing

FRONTEND — tambahkan (jangan refactor file existing, hanya extend):

1. utils/api.js → tambah jadwalApi, penugasanApi, rescheduleApi, kehadiranApi
2. utils/generateIcs.js → generate file .ics dari data jadwal
3. constants/index.js → tambah JENIS_JADWAL_LIST, PENUGASAN_STATUS_LIST, RESCHEDULE_STATUS_LIST
4. components/pmb/JadwalPanel.jsx — kartu jadwal + QR (npm: qrcode.react) + download .ics + tombol reschedule
5. components/pmb/RescheduleForm.jsx — form ajukan reschedule
6. components/pmb/JadwalTesForm.jsx — form buat/edit slot (admin)
7. components/pmb/JadwalTesTable.jsx — tabel slot + counter kapasitas + tombol assign/auto-batch
8. components/pmb/RescheduleInbox.jsx — admin approve/reject
9. components/pmb/KehadiranTable.jsx — laporan kehadiran per sesi
10. pages/Operator.jsx — halaman scan QR (npm: html5-qrcode) + input PIN
11. CekStatus.jsx → SETELAH panel status existing, render <JadwalPanel nomor={nomor} /> jika data ada
12. Admin.jsx → tambah tab switcher "Pendaftar | Jadwal Tes" di bawah header; tab Jadwal render JadwalTesForm + JadwalTesTable + RescheduleInbox
13. App.jsx → tambah route pathname '/operator' → <Operator />

[CONSTRAINT]
- DILARANG mengubah/menghapus route, method, atau response shape di PendaftarController dan AdminAuthController
- DILARANG migration alter table pendaftars
- DILARANG mengganti React Router — tetap pakai pathname switch di App.jsx
- Route baru harus didaftarkan SETELAH route existing; jangan sisipkan route yang bentrok dengan /pendaftar/export/csv
- Semua endpoint baru wajib return format { success, message?, data? }
- Validasi via FormRequest, bukan inline di controller
- Error message bahasa Indonesia, inline di UI (bukan alert())
- npm packages baru HANYA: qrcode.react, html5-qrcode — jangan tambah axios/redux/router
- Setelah selesai, jalankan: php artisan migrate --seed di pmb-backend, npm install di pmb-frontend
- Test manual: pendaftaran baru, cek status, heregistrasi, admin login, statistik, export CSV harus tetap jalan

[TAMPILAN]
- Ikuti design system existing: bg slate-50, card putih rounded-xl shadow-sm border slate-200, heading text-slate-800
- Tombol utama: bg-[#1a56db] hover:bg-blue-700 text-white rounded-lg min-h-[44px]
- Tab admin "Pendaftar | Jadwal Tes": pill/tab style mirip tab di Home.jsx (Daftar | Cek Status)
- JadwalPanel: kartu dengan ikon kalender, badge jenis (tes=amber, wawancara=green), countdown "H-X hari"
- QR tiket: kotak putih border dashed, QR 180x180px, teks "Tunjukkan ke panitia saat check-in"
- Status badge reschedule: menunggu=yellow, disetujui=green, ditolak=red (konsisten StatusBadge.jsx)
- Operator page: mobile-first, full-width, tombol besar, feedback hijau/merah instant setelah scan
- Responsive min 375px, semua input punya label
```

---

## BAGIAN 6 — Jalankan Prompt & Evaluasi Hasil

### 6.1 Review Kompatibilitas Pre-Implementation (Prompt Belum Dijalankan)

Bagian ini adalah **analisis keamanan integrasi** terhadap codebase aktual di repo `admission-app-bugz`, untuk memastikan plan + prompt di Bagian 5 **dapat dijalankan tanpa merusak aplikasi existing**.

#### Prompt Utama
Prompt Bagian 5 di atas — siap dikirim ke Cursor/Claude.

#### Iterasi yang Direncanakan (jika diperlukan saat implementasi)

**Iterasi 1 (antisipasi): Route ordering conflict**
- **Trigger:** Jika `GET /api/pendaftar/{nomor}/jadwal` tidak match karena urutan route.
- **Fix prompt:** "Pastikan route `/pendaftar/export/csv` dan `/pendaftar/{nomor}/jadwal` didefinisikan SEBELUM route parameter generic. Gunakan regex where yang sama: `PMB-[0-9]{4}-[0-9]{4}`."
- **Alasan:** Pola ini sudah dipakai di `routes/api.php` baris 17–20 untuk `show` dan `heregistrasi`.

**Iterasi 2 (antisipasi): Admin.jsx state regression**
- **Trigger:** Jika tab Jadwal Tes mengganggu fetch pendaftar/statistik existing.
- **Fix prompt:** "Tab switcher di Admin.jsx hanya conditional render — fetch pendaftarApi.getAll() dan statistikApi.get() tetap di useEffect existing tanpa dependensi tab aktif."
- **Alasan:** `Admin.jsx` baris 25–48 fetch parallel di mount; tab baru harus pure UI split.

**Iterasi 3 (antisipasi): CekStatus.jsx breaking change**
- **Trigger:** Jika jadwalApi error membuat seluruh Cek Status gagal.
- **Fix prompt:** "Fetch jadwal di CekStatus.jsx terpisah dalam try/catch sendiri — jika jadwalApi gagal, panel status existing tetap tampil, hanya panel jadwal yang show error."
- **Alasan:** Isolasi failure domain — prinsip QA defensive.

#### Checklist Keamanan Integrasi (Verified terhadap Codebase)

| Area | Risiko | Mitigasi di Plan | Status Review |
|------|--------|------------------|---------------|
| `routes/api.php` | Route baru bentrok dengan existing | Path prefix berbeda (`/jadwal-tes`, `/penugasan`, `/reschedule`, `/kehadiran`); nomor route mengikuti regex existing | ✅ Aman |
| `pendaftars` table | Migration alter breaking | Tidak ada ALTER — hanya FK read dari tabel baru | ✅ Aman |
| `PendaftarController` | Regression pada store/show/heregistrasi | Controller tidak disentuh; relasi `hasMany` saja di Model | ✅ Aman |
| `api.js` | Breaking change pada pendaftarApi | Hanya tambah export baru, tidak ubah fungsi existing | ✅ Aman |
| `Admin.jsx` | Statistik/export rusak | Tab additive; fetch existing tidak diubah | ✅ Aman |
| `CekStatus.jsx` | Heregistrasi flow rusak | JadwalPanel di-render setelah blok existing, kondisi heregistrasi tidak diubah | ✅ Aman |
| `App.jsx` routing | `/admin` broken | Hanya tambah case `/operator`, default tetap Home | ✅ Aman |
| Sanctum auth | Token existing invalid | Middleware group sama, tidak ubah auth config | ✅ Aman |
| SQLite dev | Migration fail | 5 tabel baru independen, no FK circular | ✅ Aman |
| npm deps | Build fail | Hanya 2 package baru, compatible Vite | ✅ Aman |

### 6.2 Evaluasi Kesesuaian dengan Development Plan

> **Status:** Pre-implementation review. Tabel di bawah menilai **kelayakan plan** terhadap codebase dan **prediksi kesesuaian** saat prompt dijalankan. Setelah implementasi, update kolom "Prediksi" menjadi hasil aktual.

#### Fitur Baru (Bagian 1.2)

| Fitur | Pengguna | Prediksi | Catatan |
|-------|----------|----------|---------|
| CRUD Slot Jadwal | Admin | ✅ | Endpoint & komponen terdefinisi lengkap |
| Auto-Batch per Prodi | Admin | ✅ | Eligibility rule selaras data seeder (prodi TI, SI, dll.) |
| Kelola Reschedule | Admin | ✅ | Inbox + PATCH approve/reject |
| Laporan Kehadiran + CSV | Admin | ⚠️ | CSV kehadiran direncanakan; export CSV pendaftar existing terpisah — pastikan endpoint berbeda saat implement |
| Lihat Jadwal Saya | Peserta | ✅ | Extend CekStatus.jsx, pattern sama heregistrasi |
| Tiket QR | Peserta | ✅ | qrcode.react + checkin_token UUID |
| Download .ics | Peserta | ✅ | generateIcs.js tanpa dependency eksternal |
| Ajukan Reschedule | Peserta | ✅ | Form + validasi 1 pending request |
| Mode Scanner Operator | Operator | ✅ | Halaman /operator terpisah dari admin |
| Verifikasi & Tandai Kehadiran | Operator | ✅ | POST checkin + PIN env |
| Conflict & Kapasitas Guard | Sistem | ✅ | Business rules di controller |
| Reminder H-1 log | Sistem | ⚠️ | Tabel notifikasi_log ada; Scheduler perlu `php artisan schedule:work` manual di dev — terdokumentasi di README |

#### Flow Bisnis (Bagian 2)

| Flow | Prediksi | Catatan |
|------|----------|---------|
| 2.1 Penjadwalan utama (tes → wawancara → cek → check-in) | ✅ | Titik integrasi eksplisit ke pendaftars.status & heregistrasi_at |
| 2.2 Reschedule alternatif | ✅ | State machine menunggu/disetujui/ditolak lengkap |
| 2.3 Happy path | ✅ | Semua aktor covered |
| 2.3 Error path (kapasitas, eligibility, QR invalid, konflik) | ✅ | 4 error path terdefinisi dengan HTTP code |

#### Database (Bagian 4)

| Tabel | Prediksi | Catatan |
|-------|----------|---------|
| jadwal_tes | ✅ | Proporsional, tidak over-engineer |
| penugasan_jadwal | ✅ | FK ke pendaftars & jadwal_tes valid |
| permintaan_reschedule | ✅ | Relasi ke penugasan + slot baru |
| kehadiran_tes | ✅ | 1:1 dengan penugasan |
| notifikasi_log | ✅ | Prototype-friendly tanpa SMTP |
| Tidak alter pendaftars/users | ✅ | Konfirmasi — integrasi via FK saja |

#### Regresi Fitur Lama (Eksplisit)

| Fitur Existing | Prediksi | Verifikasi yang Harus Dijalankan |
|----------------|----------|----------------------------------|
| Form pendaftaran → POST /api/pendaftar | ✅ Tidak terpengaruh | Submit form baru, dapat nomor PMB-2025-XXXX |
| Cek status by nomor | ✅ Tidak terpengaruh | Lookup nomor seeder, status badge tampil |
| Heregistrasi (Lolos Seleksi) | ✅ Tidak terpengaruh | Klik heregistrasi, heregistrasi_at terisi |
| Admin login/logout Sanctum | ✅ Tidak terpengaruh | Login admin/pmb2025, token tersimpan |
| Tabel pendaftar + filter | ✅ Tidak terpengaruh | Filter real-time, ubah status dropdown |
| Statistik per prodi/jalur | ✅ Tidak terpengaruh | Stat cards & progress bar akurat |
| Export CSV pendaftar | ✅ Tidak terpengaruh | Download CSV, data lengkap |

#### Kesimpulan Evaluasi

Plan ini **layak diimplementasikan tanpa regresi** karena:
1. Semua perubahan bersifat **additive** — tabel, route, controller, komponen baru.
2. Titik sentuh ke kode existing minimal: `+hasMany` di Model Pendaftar, append UI di CekStatus/Admin/App.jsx, extend api.js/constants.
3. Business rules menghormati state machine existing: `Menunggu` → seleksi → `Lolos Seleksi` → heregistrasi.
4. Dua area ⚠️ (CSV kehadiran terpisah, scheduler manual di dev) bukan blocker — hanya perlu dokumentasi di README-app.md saat Bagian 6.3 dikerjakan.

**Gap yang disengaja (out-of-scope prototype):** Email/WhatsApp reminder, multi-operator role management, integrasi SIAKAD — sesuai PRD section 7.

### 6.3 Instruksi Post-Implementasi

Setelah menjalankan prompt Bagian 5, buat `README-app.md` di root repo:

```markdown
# PMB App — Modul JadwalHub

## Menjalankan
cd pmb-backend && composer install && php artisan migrate --seed && php artisan serve
cd pmb-frontend && npm install && npm run dev

## Fitur Baru ✅
- [list dari evaluasi 6.2 yang berstatus ✅]

## Fitur Baru ⚠️ / Belum
- [list yang partial]

## Fitur Lama — Konfirmasi Normal
- Pendaftaran, Cek Status, Heregistrasi, Admin Dashboard, Statistik, Export CSV: SEMUA NORMAL

## Screenshot
[Lampirkan screenshot tab Jadwal Tes admin + panel jadwal Cek Status + halaman /operator]
```

---

## Lampiran: Diagram Relasi (Referensi Cepat)

```
users ──────< jadwal_tes >──────< penugasan_jadwal >────── pendaftars (existing)
                  │                      │
                  │                      ├──< permintaan_reschedule
                  │                      │
                  │                      └─── kehadiran_tes (1:1)
                  │
pendaftars ──────< notifikasi_log
```

---

*File: `devplan-bugz.md` | Vibe Coding & Venture SEVIMA | Bagus Setiyo N*
