import Home from './pages/Home';
import Admin from './pages/Admin';
import Operator from './pages/Operator';

/**
 * App — root component yang menangani routing sederhana via window.location.pathname
 * /          → Home (halaman publik)
 * /admin     → Admin (dashboard admin, butuh login)
 * /operator  → Operator (scan QR check-in)
 */
const App = () => {
  const path = window.location.pathname;

  if (path === '/admin' || path === '/admin/') {
    return <Admin />;
  }

  if (path === '/operator' || path === '/operator/') {
    return <Operator />;
  }

  return <Home />;
};

export default App;
