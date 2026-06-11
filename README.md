# 📚 Sistem Penerimaan Mahasiswa Baru (PMB) — Full Stack Prototype

> **Built entirely with AI Agent** — This project was designed, architected, and implemented using GitHub Copilot as an autonomous AI coding agent.

A modern, full-stack web application for university admission management. Built with **React 18 + Vite** (frontend) and **Laravel 12** (backend), featuring real-time statistics, secure admin authentication, and student registration tracking.

---

## 🎯 Project Overview

| Aspect | Details |
|--------|---------|
| **Purpose** | Prototype system for managing university admissions (PMB 2025) |
| **Event** | Vibe Coding & Venture — SEVIMA |
| **Build Status** | ✅ Complete (3 phases) |
| **Development** | 100% AI-assisted (GitHub Copilot agent) |

---

## 🏗️ Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                      React 18 Frontend                      │
│              (http://localhost:5173)                        │
│  • Vite dev server                                          │
│  • Tailwind CSS styling                                     │
│  • localStorage + Fetch API                                │
└──────────────────────┬──────────────────────────────────────┘
                       │ (CORS-enabled)
                       │ HTTP/JSON
                       ▼
┌─────────────────────────────────────────────────────────────┐
│                    Laravel 12 API                           │
│              (http://localhost:8000/api)                    │
│  • RESTful endpoints                                        │
│  • Sanctum token-based auth                                │
│  • SQLite dev / PostgreSQL prod                            │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       ▼
         ┌─────────────────────────────┐
         │   Database (SQLite/PgSQL)   │
         │  • Pendaftar (students)     │
         │  • User (admin)             │
         │  • Personal access tokens   │
         └─────────────────────────────┘
```

---

## ✨ Features

### 📋 Public Pages
- **Home** — Landing page with navigation tabs
- **Form Pendaftaran** — Student registration form
  - Auto-generates unique registration number (PMB-2025-XXXX)
  - Validates required fields + format
  - Backend validation + error feedback
  
- **Cek Status** — Check application status by registration number
  - Displays student info (name, prodi, jalur, status)
  - **Heregistrasi button** (conditional) — For accepted students to confirm enrollment
  
### 🔐 Admin Dashboard
- **Login** — Sanctum token-based authentication
  - Credentials: `admin` / `pmb2025`
  
- **Statistics Cards**
  - Total applicants
  - Status breakdown (Menunggu, Lolos Seleksi, Tidak Lolos)
  
- **Progress Bars**
  - Applicants per program (Prodi) — blue bars
  - Applicants per track (Jalur) — amber bars
  
- **Applicant Table**
  - Real-time filtering + inline status updates
  - Shows registration date + current status
  
- **Export CSV** — Download all applicant data
  - UTF-8 encoded with BOM for Excel compatibility
  - Includes registration number, name, email, phone, school, prodi, jalur, status, heregistrasi date

---

## 🚀 Quick Start

### Prerequisites
- **Node.js 18+** + npm
- **PHP 8.3+** + Composer
- **SQLite** (dev) or **PostgreSQL 17+** (prod)

### Installation

#### 1️⃣ Clone & Navigate
```bash
cd /Users/sevima/Vibe-Coding/admission-app
```

#### 2️⃣ Setup Frontend
```bash
cd pmb-frontend
npm install
npm run dev
# → http://localhost:5173
```

#### 3️⃣ Setup Backend
```bash
cd ../pmb-backend
composer install
cp .env.example .env
php artisan key:generate

# Setup SQLite database
touch database/database.sqlite
php artisan migrate --seed

# Start dev server
php artisan serve --port=8000
# → http://localhost:8000
```

#### 4️⃣ Verify
- Frontend: Open `http://localhost:5173` → should see landing page
- Admin: Go to `/admin` → login with `admin`/`pmb2025`
- API: `curl http://localhost:8000/api/statistik` (should fail without token, as expected)

---

## 📁 Project Structure

```
admission-app/
├── .gitignore                          # Root-level git ignore
├── claude.md                           # AI context + session log
├── agent.md                            # AI agent instructions
├── prd.md                              # Product requirements
├── skill.md                            # Technical conventions
│
├── pmb-frontend/                       # React + Vite
│   ├── src/
│   │   ├── components/
│   │   │   ├── ui/                    # Button, Input reusables
│   │   │   └── pmb/
│   │   │       ├── FormPendaftaran.jsx
│   │   │       ├── CekStatus.jsx       # ← Heregistrasi UI
│   │   │       ├── AdminLogin.jsx      # ← Sanctum login
│   │   │       ├── TabelPendaftar.jsx
│   │   │       └── StatusBadge.jsx
│   │   ├── pages/
│   │   │   ├── Home.jsx
│   │   │   └── Admin.jsx               # ← Dashboard + stats
│   │   ├── hooks/
│   │   │   ├── useLocalStorage.js
│   │   │   └── usePendaftar.js
│   │   ├── utils/
│   │   │   ├── api.js                  # ← Fetch wrapper + token mgmt
│   │   │   └── generateNomor.js
│   │   ├── constants/index.js
│   │   └── App.jsx
│   ├── .env                            # VITE_API_URL=http://localhost:8000/api
│   ├── .gitignore
│   ├── vite.config.js
│   ├── tailwind.config.js
│   └── package.json
│
└── pmb-backend/                        # Laravel 12
    ├── app/
    │   ├── Http/
    │   │   ├── Controllers/Api/
    │   │   │   ├── PendaftarController.php     # ← All CRUD + stats + export + heregistrasi
    │   │   │   └── AdminAuthController.php     # ← Sanctum login/logout
    │   │   └── Requests/
    │   │       ├── StorePendaftarRequest.php   # ← Validation
    │   │       └── UpdateStatusRequest.php
    │   ├── Models/
    │   │   ├── User.php                        # ← HasApiTokens
    │   │   └── Pendaftar.php                   # ← Main model + constants
    │   └── ...
    ├── database/
    │   ├── migrations/
    │   │   ├── ...create_users_table
    │   │   ├── ...create_pendaftars_table
    │   │   └── ...add_heregistrasi_to_pendaftars
    │   ├── seeders/
    │   │   ├── DatabaseSeeder.php
    │   │   ├── AdminSeeder.php                 # ← Create admin user
    │   │   └── PendaftarSeeder.php             # ← 3 dummy records
    │   └── database.sqlite
    ├── routes/
    │   └── api.php                             # ← 9 endpoints (see below)
    ├── config/
    │   └── cors.php                            # ← Allow localhost:5173
    ├── .env
    ├── .gitignore
    └── ...
```

---

## 🔌 API Endpoints

All endpoints return JSON responses. Admin endpoints require `Authorization: Bearer {token}` header.

### Authentication
| Method | Endpoint | Auth | Purpose |
|--------|----------|------|---------|
| `POST` | `/api/auth/login` | ✗ | Get Sanctum token (admin) |
| `POST` | `/api/auth/logout` | ✓ | Revoke token |

### Applicants
| Method | Endpoint | Auth | Purpose |
|--------|----------|------|---------|
| `POST` | `/api/pendaftar` | ✗ | Register new student |
| `GET` | `/api/pendaftar` | ✓ | Get all applicants (admin) |
| `GET` | `/api/pendaftar/{nomor}` | ✗ | Check status by registration number |
| `PATCH` | `/api/pendaftar/{id}/status` | ✓ | Update status (admin) |
| `POST` | `/api/pendaftar/{nomor}/heregistrasi` | ✗ | Confirm enrollment |

### Statistics & Export
| Method | Endpoint | Auth | Purpose |
|--------|----------|------|---------|
| `GET` | `/api/statistik` | ✓ | Get statistics (total, per_prodi, per_jalur) |
| `GET` | `/api/pendaftar/export/csv` | ✓ | Download CSV file |

---

## 🔐 Authentication (Sanctum)

### Login Flow
```bash
# 1. Get token
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"pmb2025"}'

# Response:
{
  "success": true,
  "data": {
    "token": "1|wy41KEYp3MmmJnbVVzY17lyOehi86pBrPkHy1Hkmc76...",
    "user": { "name": "admin", "email": "admin@pmb.local" }
  }
}

# 2. Use token in subsequent requests
curl -X GET http://localhost:8000/api/statistik \
  -H "Authorization: Bearer 1|wy41KEYp3MmmJnbVVzY17lyOehi86pBrPkHy1Hkmc76..."
```

### Token Storage (Frontend)
- Stored in `sessionStorage` (key: `pmb_admin_token`)
- Auto-attached to all API requests via `Authorization: Bearer {token}` header
- Cleared on logout

---

## 🧪 Testing

### Test Registration
```bash
# 1. Open frontend: http://localhost:5173
# 2. Tab "Daftar" → Fill form → Submit
# 3. Copy registration number from success screen

# 4. Check status
# Tab "Cek Status" → Paste number → View details
```

### Test Admin Features
```bash
# 1. Go to http://localhost:5173/admin
# 2. Login: admin / pmb2025
# 3. View statistics + applicants table
# 4. Change status in dropdown
# 5. Export CSV (downloads to Downloads folder)
```

### Test Heregistrasi
```bash
# 1. Cek Status tab
# 2. Enter: PMB-2025-1002 (Siti Rahayu, status=Lolos Seleksi)
# 3. Button "Lakukan Heregistrasi Sekarang" appears
# 4. Click → Success message + date stamp shown
```

---

## 🔨 Build & Production

### Frontend Build
```bash
cd pmb-frontend
npm run build
# → /dist folder (ready for deployment)
```

### Backend Deployment (PostgreSQL)
```bash
# Update .env
DB_CONNECTION=pgsql
DB_HOST=your_server
DB_DATABASE=pmb_prod
DB_USERNAME=...
DB_PASSWORD=...

# Run migrations on production
php artisan migrate --env=production
php artisan db:seed --class=AdminSeeder
```

---

## 🤖 AI Agent Development

**This entire project was built by an AI agent** (GitHub Copilot) working autonomously with the following approach:

### Agent Workflow
1. **Requirements** — Read from `prd.md` (features, personas, acceptance criteria)
2. **Design** — Follow conventions in `skill.md` (naming, structure, patterns)
3. **Implementation** — Build components + API + database iteratively
4. **Verification** — Test endpoints, UI, error handling
5. **Documentation** — Update `claude.md` (context + session log)

### Key AI Decisions
- ✅ React 18 (hooks) + Vite (fast dev server)
- ✅ Laravel 12 (modern PHP) + Sanctum (auth)
- ✅ SQLite for dev, PostgreSQL for prod
- ✅ Fetch API (no axios) + custom wrapper
- ✅ Tailwind CSS only (no custom CSS except directives)
- ✅ Window routing (no react-router, simpler)

### Files for AI Context
- **claude.md** — Session log + current phase status + checklist
- **agent.md** — Agent behavior + response style rules
- **prd.md** — Product requirements (features, user personas)
- **skill.md** — Technical conventions + code patterns

---

## 📊 Development Phases

### Phase 1 ✅ — Core Prototype
- React components + localStorage
- Form validation + registration
- Status checking + filtering
- Local storage persistence

### Phase 2 ✅ — Backend Integration
- Laravel API setup
- CORS configuration
- All CRUD endpoints
- Frontend API integration
- Admin dashboard basics

### Phase 3 ✅ — Full System
- Sanctum token-based auth
- Statistics per prodi & jalur
- CSV export (with UTF-8 BOM)
- Heregistrasi (enrollment confirmation)
- Protected admin routes

---

## 🛠️ Development Commands

### Frontend
```bash
npm run dev      # Start Vite dev server (http://localhost:5173)
npm run build    # Build for production (/dist)
npm run preview  # Preview production build
```

### Backend
```bash
php artisan serve              # Start dev server (http://localhost:8000)
php artisan migrate            # Run migrations
php artisan db:seed            # Seed dummy data
php artisan tinker             # Interactive shell
php artisan make:model ...     # Generate model
php artisan make:controller ... # Generate controller
```

### Database
```bash
php artisan migrate:fresh --seed    # Reset + seed
sqlite3 database/database.sqlite    # Open SQLite CLI
```

---

## 📝 Database Schema

### `users` table
```sql
id (PK)
name
email (unique)
password
email_verified_at
created_at, updated_at
```

### `pendaftars` table
```sql
id (PK)
nomor_pendaftaran (unique)
nama
nomor_hp
email
asal_sekolah
prodi (enum-like: TI, SI, Manajemen, Akuntansi)
jalur (enum-like: SNBT, Mandiri, Prestasi)
status (default: Menunggu)
heregistrasi_at (nullable)
created_at, updated_at
```

### `personal_access_tokens` table
*(auto-created by Sanctum)*

---

## 🐛 Troubleshooting

### Port Already in Use
```bash
# Find process on port 8000
lsof -i :8000
kill -9 <PID>

# Or use different port
php artisan serve --port=8001
```

### CORS Errors
- Check `config/cors.php` → `allowed_origins` includes `http://localhost:5173`
- Restart Laravel server after changing

### SQLite File Not Found
```bash
touch database/database.sqlite
php artisan migrate --seed
```

### Token Expired / Logout Not Working
- Check `sessionStorage` → `pmb_admin_token` is cleared
- Or use browser DevTools: Application → Session Storage → delete token

### Build Errors
```bash
# Frontend
npm install --force
npm run build

# Backend
composer install --no-dev
```

---

## 📚 Documentation Files

| File | Purpose |
|------|---------|
| `prd.md` | Product requirements, features, user personas |
| `skill.md` | Code conventions, structure, patterns |
| `agent.md` | AI agent behavior + response instructions |
| `claude.md` | Session log + phase status + progress tracking |

---

## 📄 License

This is a demonstration project created for **Vibe Coding & Venture — SEVIMA**.

---

## 🤝 Built With

- **Frontend**: [React 18](https://react.dev) + [Vite 5](https://vitejs.dev) + [Tailwind CSS 3](https://tailwindcss.com)
- **Backend**: [Laravel 12](https://laravel.com) + [Sanctum](https://laravel.com/docs/sanctum)
- **Database**: SQLite (dev) / PostgreSQL (prod)
- **Agent**: [GitHub Copilot](https://github.com/features/copilot) (autonomous AI coding)

---

## 🎓 Created By

**AI Agent + SEVIMA Team**  
*Built entirely using GitHub Copilot as an autonomous coding assistant*

**Event**: Vibe Coding & Venture — SEVIMA  
**Date**: June 2026

---

*Last updated: 2026-06-11*
