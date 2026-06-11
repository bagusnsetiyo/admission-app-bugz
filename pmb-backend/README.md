# PMB Backend API — Laravel 12

RESTful API server for university admission management system (PMB 2025).

## 🚀 Quick Start

```bash
# Install dependencies
composer install

# Setup environment
cp .env.example .env
php artisan key:generate

# Setup database (SQLite for dev)
touch database/database.sqlite
php artisan migrate --seed

# Start dev server
php artisan serve --port=8000
# → http://localhost:8000/api
```

## 📚 API Endpoints

### Auth (Sanctum)
```bash
POST   /api/auth/login          # Get token: {username, password}
POST   /api/auth/logout         # Revoke token (requires Bearer token)
```

### Applicants
```bash
POST   /api/pendaftar           # Create (public): {nama, nomor_hp, email, asal_sekolah, prodi, jalur}
GET    /api/pendaftar           # List all (admin only)
GET    /api/pendaftar/{nomor}   # Get by number (public): e.g. PMB-2025-1001
PATCH  /api/pendaftar/{id}/status  # Update status (admin only): {status}
POST   /api/pendaftar/{nomor}/heregistrasi  # Confirm enrollment (public)
```

### Statistics & Export
```bash
GET    /api/statistik           # Stats (admin only): per_prodi, per_jalur, per_status
GET    /api/pendaftar/export/csv  # Download CSV (admin only)
```

## 🔐 Admin Credentials

| Field | Value |
|-------|-------|
| Username | `admin` |
| Email | `admin@pmb.local` |
| Password | `pmb2025` |

## 📊 Database

**Dev**: SQLite (`database/database.sqlite`)  
**Prod**: PostgreSQL 17+

### Tables
- `users` — Admin accounts
- `pendaftars` — Student applications
- `personal_access_tokens` — Sanctum tokens

### Status Values
- `Menunggu` (pending)
- `Lolos Seleksi` (accepted)
- `Tidak Lolos` (rejected)

## 📁 Structure

```
app/
├── Http/
│   ├── Controllers/Api/
│   │   ├── PendaftarController.php
│   │   └── AdminAuthController.php
│   └── Requests/
│       ├── StorePendaftarRequest.php
│       └── UpdateStatusRequest.php
├── Models/
│   ├── User.php (HasApiTokens)
│   └── Pendaftar.php
│
database/
├── migrations/
│   ├── ...create_users_table
│   ├── ...create_pendaftars_table
│   └── ...add_heregistrasi_to_pendaftars
└── seeders/
    ├── AdminSeeder.php
    └── PendaftarSeeder.php

routes/
└── api.php (9 endpoints)

config/
└── cors.php (allow localhost:5173)
```

## 🔧 Commands

```bash
# Database
php artisan migrate              # Run migrations
php artisan migrate:fresh        # Reset database
php artisan db:seed              # Seed dummy data
php artisan db:seed --class=AdminSeeder  # Seed admin only

# Development
php artisan serve                # Start server (port 8000)
php artisan serve --port=8001    # Custom port
php artisan tinker               # Interactive shell

# Generate
php artisan make:model Model
php artisan make:controller Api/ControllerName
php artisan make:request StoreRequest
```

## 🧪 Testing Endpoints

### Login
```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"pmb2025"}'
```

### Get Stats (with token)
```bash
TOKEN="your_token_from_login"
curl -X GET http://localhost:8000/api/statistik \
  -H "Authorization: Bearer $TOKEN"
```

### Register Student (public)
```bash
curl -X POST http://localhost:8000/api/pendaftar \
  -H "Content-Type: application/json" \
  -d '{
    "nama": "John Doe",
    "nomor_hp": "081234567890",
    "email": "john@example.com",
    "asal_sekolah": "SMA Negeri 1",
    "prodi": "Teknik Informatika",
    "jalur": "SNBT"
  }'
```

## 🌍 CORS Configuration

File: `config/cors.php`

```php
'allowed_origins' => ['http://localhost:5173'],
```

Update for production:
```php
'allowed_origins' => [
    'https://yourdomain.com',
    'https://www.yourdomain.com',
],
```

## 📦 Dependencies

- Laravel 12.x
- PHP 8.3+
- Laravel Sanctum (auth)
- SQLite or PostgreSQL

See `composer.json` for full list.

## 🚢 Production Deployment

1. Update `.env` with PostgreSQL credentials
2. Run migrations: `php artisan migrate --env=production`
3. Seed admin: `php artisan db:seed --class=AdminSeeder`
4. Set `APP_ENV=production` and `APP_DEBUG=false`
5. Deploy with web server (Apache/Nginx) + PHP-FPM

## 🐛 Troubleshooting

**Port already in use:**
```bash
lsof -i :8000 && kill -9 <PID>
```

**Migration errors:**
```bash
php artisan migrate:fresh --seed  # Reset everything
```

**Token not working:**
- Check `Authorization: Bearer {token}` header
- Verify token hasn't expired
- Reseed admin if needed

---

*Part of PMB 2025 admission system — Built with GitHub Copilot AI agent*
