# PMB Frontend — React 18 + Vite + Tailwind CSS

Modern, fast web interface for the PMB (University Admission) system.

## 🚀 Quick Start

```bash
# Install dependencies
npm install

# Start dev server
npm run dev
# → http://localhost:5173

# Build for production
npm run build
# → /dist folder
```

## 📖 Pages & Features

### Public Pages

#### 🏠 Home (`/`)
Landing page with two tabs:
- **Daftar** — Registration form
- **Cek Status** — Check application status

#### 📝 Form Pendaftaran
Register as a new student:
- Full name, phone number, email
- School name, chosen program (Prodi), track (Jalur)
- Auto-generates unique registration number (PMB-2025-XXXX)
- Backend validation with error feedback
- Success screen with registration number copy

#### 🔍 Cek Status
Check application status by registration number:
- Enter registration number (e.g., PMB-2025-1001)
- View applicant info (name, prodi, jalur, status)
- **Heregistrasi button** — Appears when status is "Lolos Seleksi" and not yet heregistered
- Click to confirm enrollment
- Shows confirmation timestamp after success

### Admin Pages

#### 🔐 Login (`/admin`)
Sanctum token-based authentication:
- Username: `admin`
- Password: `pmb2025`
- Token stored in `sessionStorage`

#### 📊 Dashboard (`/admin`)
Admin statistics & management:
- **Stat cards** — Total + per-status breakdown
- **Per-prodi progress bars** — Blue bars showing distribution
- **Per-jalur progress bars** — Amber bars showing distribution
- **Applicants table** — Real-time filtering + inline status updates
- **Export CSV button** — Download all applicant data
- **Logout button** — Clear token

---

## 🛠️ Project Structure

```
pmb-frontend/
├── src/
│   ├── components/
│   │   ├── ui/
│   │   │   ├── Button.jsx         # Reusable button component
│   │   │   └── Input.jsx          # Reusable input component
│   │   └── pmb/
│   │       ├── FormPendaftaran.jsx   # Registration form (public)
│   │       ├── CekStatus.jsx         # Status check + heregistrasi (public)
│   │       ├── AdminLogin.jsx        # Admin login form
│   │       ├── TabelPendaftar.jsx    # Applicants table (admin)
│   │       └── StatusBadge.jsx       # Status color badge
│   ├── pages/
│   │   ├── Home.jsx            # Landing page (public)
│   │   └── Admin.jsx           # Dashboard (admin)
│   ├── hooks/
│   │   ├── useLocalStorage.js  # localStorage CRUD
│   │   └── usePendaftar.js     # Pendaftar state (legacy)
│   ├── utils/
│   │   ├── api.js              # ← Fetch wrapper + token management
│   │   └── generateNomor.js    # Generate unique registration numbers
│   ├── constants/
│   │   └── index.js            # PRODI_LIST, JALUR_LIST, STATUS_LIST, etc.
│   ├── App.jsx                 # Main app + routing
│   ├── main.jsx                # Entry point
│   └── index.css               # Tailwind directives
├── .env                        # VITE_API_URL=http://localhost:8000/api
├── .gitignore
├── vite.config.js
├── tailwind.config.js
├── postcss.config.js
└── package.json
```

---

## 🔌 API Integration

### Token Management (`src/utils/api.js`)

```javascript
// Import
import { pendaftarApi, authApi, statistikApi, getToken, setToken, removeToken } from '../../utils/api';

// Set token after login
setToken(response.data.token);  // → Stored in sessionStorage

// Get token
const token = getToken();

// Clear token on logout
removeToken();

// All API calls auto-attach Bearer token:
// Authorization: Bearer {token}
```

### API Methods

```javascript
// Authentication
authApi.login(username, password)
authApi.logout()

// Applicants
pendaftarApi.store(data)           // Create
pendaftarApi.getAll()              // List (admin)
pendaftarApi.getByNomor(nomor)     // Get by number (public)
pendaftarApi.updateStatus(id, status)  // Update status (admin)
pendaftarApi.heregistrasi(nomor)   // Confirm enrollment

// Statistics
statistikApi.get()                 // Get stats (admin)

// Export
getExportCsvUrl()                  // Get CSV download URL
```

---

## 🎨 Styling

**Tailwind CSS 3** — All styling done with utility classes.

### Key Colors
- **Blue** — Primary (buttons, badges, progress bars)
- **Green** — Success, "Lolos Seleksi" status
- **Red** — Error, "Tidak Lolos" status
- **Yellow/Amber** — Warning, "Menunggu" status
- **Slate** — Neutral, text, borders

### Components
- `.btn` — Button base styles
- `.input` — Input field base styles
- `.card` — Card container styles
- `.badge` — Status badge styles

---

## 📊 Constants

File: `src/constants/index.js`

```javascript
export const PRODI_LIST = [
  'Teknik Informatika',
  'Sistem Informasi',
  'Manajemen Bisnis',
  'Akuntansi',
];

export const JALUR_LIST = ['SNBT', 'Mandiri', 'Prestasi'];

export const STATUS_LIST = ['Menunggu', 'Lolos Seleksi', 'Tidak Lolos'];

export const LOCALSTORAGE_KEY = 'pmb_pendaftar';

export const ADMIN_CREDENTIALS = {
  username: 'admin',
  password: 'pmb2025',
};
```

---

## 🚀 Commands

```bash
# Development
npm run dev                 # Start dev server (port 5173)
npm run build              # Build for production
npm run preview            # Preview production build locally

# Other
npm install                # Install dependencies
npm list                   # List installed packages
```

---

## 🧪 Testing

### Test Registration Flow
1. Go to `http://localhost:5173`
2. Tab **"Daftar"**
3. Fill form with sample data
4. Submit → See registration number
5. Copy number

### Test Status Check
1. Tab **"Cek Status"**
2. Paste registration number
3. View applicant info
4. If status = "Lolos Seleksi" → Click **"Lakukan Heregistrasi Sekarang"**
5. See success confirmation

### Test Admin Features
1. Go to `/admin` or use **"Login Admin"** link
2. Enter: `admin` / `pmb2025`
3. View statistics + applicants table
4. Change status in dropdown
5. Click **"Export CSV"** to download
6. Click **"Keluar"** to logout

---

## 🌍 Environment Variables

Create `.env` file:

```
VITE_API_URL=http://localhost:8000/api
```

For production:
```
VITE_API_URL=https://api.yourdomain.com/api
```

---

## 📦 Dependencies

- **React 18.x** — UI library
- **Vite 5.x** — Build tool & dev server
- **Tailwind CSS 3.x** — Utility-first CSS
- **PostCSS** — CSS processing

See `package.json` for full list.

---

## 🚢 Production Build

```bash
npm run build
# → Creates /dist folder

# Test production build locally
npm run preview
# → http://localhost:4173
```

Deploy `/dist` folder to:
- Vercel, Netlify, GitHub Pages
- Traditional web server (Apache, Nginx)
- S3 + CloudFront
- etc.

---

## 🐛 Troubleshooting

**Port 5173 already in use:**
```bash
npm run dev -- --port 5174
```

**API not responding:**
- Check `.env` → `VITE_API_URL` is correct
- Verify backend is running on port 8000
- Check browser console for CORS errors

**Build errors:**
```bash
rm node_modules package-lock.json
npm install
npm run build
```

**Token not persisting:**
- Check browser DevTools → Application → Session Storage
- Verify `sessionStorage` is enabled

---

## 🎓 Architecture Decisions

- **No React Router** — Simple `window.location.pathname` routing
- **No state management** — Props + hooks for local state
- **localStorage for user data** — Quick prototype (replaced by API in Phase 2)
- **Fetch API** — No axios, custom wrapper in `api.js`
- **Tailwind only** — No custom CSS except directives in `index.css`

---

*Part of PMB 2025 admission system — Built with GitHub Copilot AI agent*
