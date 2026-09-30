import { useEffect, useRef } from 'react'
import { BrowserRouter, Navigate, Outlet, Route, Routes, useLocation, useNavigate } from 'react-router-dom'
import { AuthProvider, useAuth } from './auth'
import { I18nProvider, useI18n } from './i18n'
import AiPage from './pages/AiPage'
import BottomNav from './components/BottomNav'
import TopBar from './components/TopBar'
import { Spinner } from './components/ui'
import AuthPage from './pages/AuthPage'
import CoachPage from './pages/CoachPage'
import PasswordPage from './pages/PasswordPage'
import CookPage from './pages/CookPage'
import GroceriesPage from './pages/GroceriesPage'
import HomePage from './pages/HomePage'
import KitchenPage from './pages/KitchenPage'
import PlannerPage from './pages/PlannerPage'
import PrintPlanPage from './pages/PrintPlanPage'
import ProfilePage from './pages/ProfilePage'
import RecipeDetailPage from './pages/RecipeDetailPage'
import RecipeFormPage from './pages/RecipeFormPage'
import RecipesPage from './pages/RecipesPage'
import { setupNative } from './lib/native'

function AppShell() {
  const { user, loading } = useAuth()
  const { t, lang } = useI18n()
  if (loading) return <Spinner label={t('Opening your kitchen…')} />
  if (!user) return <Navigate to="/login" replace />
  return (
    <>
      <TopBar />
      <main className="mx-auto max-w-lg px-4 pt-4 pb-28">
        {/* Remount the page on language change so server data (dish names, messages) reloads in that language. */}
        <Outlet key={lang} />
      </main>
      <BottomNav />
    </>
  )
}

/** Signed-in pages without the app bars (e.g. the printable plan). */
function BareShell() {
  const { user, loading } = useAuth()
  const { lang } = useI18n()
  if (loading) return <Spinner />
  return user ? <Outlet key={lang} /> : <Navigate to="/login" replace />
}

function GuestOnly() {
  const { user, loading } = useAuth()
  if (loading) return <Spinner />
  return user ? <Navigate to="/" replace /> : <Outlet />
}

/** Android app only: back button and status bar (see lib/native). */
function NativeSetup() {
  const navigate = useNavigate()
  const { pathname } = useLocation()
  const path = useRef(pathname)
  useEffect(() => {
    path.current = pathname
  }, [pathname])
  useEffect(() => {
    setupNative(() => {
      if (['/', '/login'].includes(path.current)) return false
      if (window.history.state?.idx > 0) navigate(-1)
      else navigate('/', { replace: true })
      return true
    })
  }, [navigate])
  return null
}

export default function App() {
  return (
    <I18nProvider>
    <AuthProvider>
      <BrowserRouter>
        <NativeSetup />
        <Routes>
          <Route element={<GuestOnly />}>
            <Route path="/login" element={<AuthPage mode="login" />} />
            <Route path="/register" element={<AuthPage mode="register" />} />
            <Route path="/forgot-password" element={<PasswordPage mode="forgot" />} />
          </Route>
          <Route path="/reset-password" element={<PasswordPage mode="reset" />} />
          <Route element={<BareShell />}>
            <Route path="/planner/print" element={<PrintPlanPage />} />
          </Route>
          <Route element={<AppShell />}>
            <Route path="/" element={<HomePage />} />
            <Route path="/profile" element={<ProfilePage />} />
            <Route path="/ai" element={<AiPage />} />
            <Route path="/kitchen" element={<KitchenPage />} />
            <Route path="/cook" element={<CookPage />} />
            <Route path="/recipes" element={<RecipesPage />} />
            <Route path="/recipes/new" element={<RecipeFormPage />} />
            <Route path="/recipes/:id" element={<RecipeDetailPage />} />
            <Route path="/recipes/:id/edit" element={<RecipeFormPage />} />
            <Route path="/planner" element={<PlannerPage />} />
            <Route path="/groceries" element={<GroceriesPage />} />
            <Route path="/coach" element={<CoachPage />} />
          </Route>
          <Route path="*" element={<Navigate to="/" replace />} />
        </Routes>
      </BrowserRouter>
    </AuthProvider>
    </I18nProvider>
  )
}
