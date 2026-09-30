import { createContext, useCallback, useContext, useEffect, useState, type ReactNode } from 'react'
import { api, token, type User } from './api'
import { currentLang, hasStoredLang, useI18n, type Lang } from './i18n'

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
  const [loading, setLoading] = useState(!!token.get())

  /**
   * The language last chosen on this device wins; the account is quietly updated to match.
   * Only a device with no choice yet takes the account's saved language.
   */
  const syncLang = useCallback(
    (u: User) => {
      if (!hasStoredLang()) {
        setLang(u.locale)
      } else if (u.locale !== currentLang()) {
        const locale: Lang = currentLang()
        api<{ user: User }>('/profile', { method: 'PUT', body: { locale } }).then((r) => setUser(r.user), () => {})
      }
    },
    [setLang],
  )

  useEffect(() => {
    if (token.get()) {
      api<{ user: User }>('/profile')
        .then((r) => {
          setUser(r.user)
          syncLang(r.user)
        })
        .catch(() => token.clear())
        .finally(() => setLoading(false))
    }
    const onExpired = () => setUser(null)
    window.addEventListener('auth:expired', onExpired)
    return () => window.removeEventListener('auth:expired', onExpired)
  }, [syncLang])

  const handleAuth = useCallback(
    (r: { token: string; user: User }) => {
      token.set(r.token)
      setUser(r.user)
      syncLang(r.user)
    },
    [syncLang],
  )

  const login = useCallback(async (email: string, password: string) => {
    handleAuth(await api('/login', { method: 'POST', body: { email, password } }))
  }, [handleAuth])

  const register = useCallback(async (data: Parameters<AuthState['register']>[0]) => {
    handleAuth(await api('/register', { method: 'POST', body: { ...data, locale: currentLang() } }))
  }, [handleAuth])

  const loginWithGoogle = useCallback(
    async (credential: string) => {
      handleAuth(await api('/auth/google', { method: 'POST', body: { credential } }))
    },
    [handleAuth],
  )

  const logout = useCallback(async () => {
    await api('/logout', { method: 'POST' }).catch(() => {})
    token.clear()
    setUser(null)
  }, [])

  return (
    <AuthContext.Provider value={{ user, loading, login, register, loginWithGoogle, logout, setUser }}>{children}</AuthContext.Provider>
  )
}

// oxlint-disable-next-line react/only-export-components -- hook lives beside its provider
export function useAuth() {
  const ctx = useContext(AuthContext)
  if (!ctx) throw new Error('useAuth must be used inside AuthProvider')
  return ctx
}
