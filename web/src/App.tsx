import { BrowserRouter, Navigate, Outlet, Route, Routes } from 'react-router-dom'
import { AuthProvider, useAuth } from './auth'
import { I18nProvider, useI18n } from './i18n'
import AiPage from './pages/AiPage'
import BottomNav from './components/BottomNav'
import TopBar from './components/TopBar'
import { Spinner } from './components/ui'
import AuthPage from './pages/AuthPage'
import CookPage from './pages/CookPage'
import GroceriesPage from './pages/GroceriesPage'
import HomePage from './pages/HomePage'
import KitchenPage from './pages/KitchenPage'
import PlannerPage from './pages/PlannerPage'
import ProfilePage from './pages/ProfilePage'
import RecipeDetailPage from './pages/RecipeDetailPage'
import RecipeFormPage from './pages/RecipeFormPage'
import RecipesPage from './pages/RecipesPage'

function AppShell() {
  const { user, loading } = useAuth()
  const { t } = useI18n()
  if (loading) return <Spinner label={t('Opening your kitchen…')} />
  if (!user) return <Navigate to="/login" replace />
  return (
    <>
      <TopBar />
      <main className="mx-auto max-w-lg px-4 pt-4 pb-28">
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
    <I18nProvider>
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
            <Route path="/ai" element={<AiPage />} />
            <Route path="/kitchen" element={<KitchenPage />} />
            <Route path="/cook" element={<CookPage />} />
            <Route path="/recipes" element={<RecipesPage />} />
            <Route path="/recipes/new" element={<RecipeFormPage />} />
            <Route path="/recipes/:id" element={<RecipeDetailPage />} />
            <Route path="/recipes/:id/edit" element={<RecipeFormPage />} />
            <Route path="/planner" element={<PlannerPage />} />
            <Route path="/groceries" element={<GroceriesPage />} />
          </Route>
          <Route path="*" element={<Navigate to="/" replace />} />
        </Routes>
      </BrowserRouter>
    </AuthProvider>
    </I18nProvider>
  )
}
