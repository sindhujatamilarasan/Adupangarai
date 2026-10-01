import { createContext, useCallback, useContext, useEffect, useState, type ReactNode } from 'react'
import { api, setOnExpired, token, type User } from './api'
import { currentLang, hasStoredLang, useI18n } from './i18n'

type AuthState = {
  user: User | null
  loading: boolean
  login: (email: string, password: string) => Promise<void>
  register: (data: { name: string; email: string; password: string; household_name?: string }) => Promise<void>
  loginWithGoogle: (credential: string) => Promise<void>
  logout: () => Promise<void>
  setUser: (u: User) => void
}
const AuthContext = createContext<AuthState | null>(null)

export function AuthProvider({ children }: { children: ReactNode }) {
  const { setLang } = useI18n()
  const [user, setUser] = useState<User | null>(null)
  const [loading, setLoading] = useState(true)

  /** The latest language choice on this phone wins; the account is updated to match. */
  const syncLang = useCallback(
    (u: User) => {
      if (!hasStoredLang()) setLang(u.locale)
      else if (u.locale !== currentLang()) api<{ user: User }>('/profile', { method: 'PUT', body: { locale: currentLang() } }).then((r) => setUser(r.user), () => {})
    },
    [setLang],
  )

  useEffect(() => {
    setOnExpired(() => setUser(null))
    token.load().then((t) => {
      if (!t) return setLoading(false)
      api<{ user: User }>('/profile')
        .then((r) => {
          setUser(r.user)
          syncLang(r.user)
        })
        .catch(() => {})
        .finally(() => setLoading(false))
    })
  }, [syncLang])

  const handleAuth = useCallback(
    (r: { token: string; user: User }) => {
      token.set(r.token)
      setUser(r.user)
      syncLang(r.user)
    },
    [syncLang],
  )

  const value: AuthState = {
    user,
    loading,
    setUser,
    login: async (email, password) => handleAuth(await api('/login', { method: 'POST', body: { email, password } })),
    register: async (data) => handleAuth(await api('/register', { method: 'POST', body: { ...data, locale: currentLang() } })),
    loginWithGoogle: async (credential) => handleAuth(await api('/auth/google', { method: 'POST', body: { credential } })),
    logout: async () => {
      await api('/logout', { method: 'POST' }).catch(() => {})
      token.clear()
      setUser(null)
    },
  }
  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>
}

export function useAuth() {
  const ctx = useContext(AuthContext)
  if (!ctx) throw new Error('useAuth must be used inside AuthProvider')
  return ctx
}
