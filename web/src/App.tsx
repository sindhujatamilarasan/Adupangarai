import { BrowserRouter, Navigate, Outlet, Route, Routes } from 'react-router-dom'
import { AuthProvider, useAuth } from './auth'
import BottomNav from './components/BottomNav'
import { Spinner } from './components/ui'
import AuthPage from './pages/AuthPage'
import ComingSoon from './pages/ComingSoon'
import HomePage from './pages/HomePage'
import KitchenPage from './pages/KitchenPage'
import ProfilePage from './pages/ProfilePage'

function AppShell() {
  const { user, loading } = useAuth()
  if (loading) return <Spinner label="Opening your kitchen…" />
  if (!user) return <Navigate to="/login" replace />
  return (
    <>
      <main className="mx-auto max-w-lg px-4 pt-6 pb-28">
        <Outlet />
      </main>
      <BottomNav />
    </>
  )
}

function GuestOnly() {
  const { user, loading } = useAuth()
  if (loading) return <Spinner />
  return user ? <Navigate to="/" replace /> : <Outlet />
}

export default function App() {
  return (
    <AuthProvider>
      <BrowserRouter>
        <Routes>
          <Route element={<GuestOnly />}>
            <Route path="/login" element={<AuthPage mode="login" />} />
            <Route path="/register" element={<AuthPage mode="register" />} />
          </Route>
          <Route element={<AppShell />}>
            <Route path="/" element={<HomePage />} />
            <Route path="/profile" element={<ProfilePage />} />
            <Route path="/kitchen" element={<KitchenPage />} />
            <Route path="/cook" element={<ComingSoon title="What can I cook?" emoji="🍳" />} />
            <Route path="/planner" element={<ComingSoon title="Meal planner" emoji="📅" />} />
            <Route path="/groceries" element={<ComingSoon title="Groceries" emoji="🛒" />} />
          </Route>
          <Route path="*" element={<Navigate to="/" replace />} />
        </Routes>
      </BrowserRouter>
    </AuthProvider>
  )
}
