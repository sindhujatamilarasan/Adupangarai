import { createContext, useCallback, useContext, useEffect, useState, type ReactNode } from 'react'
import { api, token, type User } from './api'

type AuthState = {
  user: User | null
  loading: boolean
  login: (email: string, password: string) => Promise<void>
  register: (data: { name: string; email: string; password: string; household_name?: string }) => Promise<void>
  logout: () => Promise<void>
  setUser: (u: User) => void
}

const AuthContext = createContext<AuthState | null>(null)

export function AuthProvider({ children }: { children: ReactNode }) {
  const [user, setUser] = useState<User | null>(null)
  const [loading, setLoading] = useState(!!token.get())

  useEffect(() => {
    if (token.get()) {
      api<{ user: User }>('/profile')
        .then((r) => setUser(r.user))
        .catch(() => token.clear())
        .finally(() => setLoading(false))
    }
    const onExpired = () => setUser(null)
    window.addEventListener('auth:expired', onExpired)
    return () => window.removeEventListener('auth:expired', onExpired)
  }, [])

  const handleAuth = (r: { token: string; user: User }) => {
    token.set(r.token)
    setUser(r.user)
  }

  const login = useCallback(async (email: string, password: string) => {
    handleAuth(await api('/login', { method: 'POST', body: { email, password } }))
  }, [])

  const register = useCallback(async (data: Parameters<AuthState['register']>[0]) => {
    handleAuth(await api('/register', { method: 'POST', body: data }))
  }, [])

  const logout = useCallback(async () => {
    await api('/logout', { method: 'POST' }).catch(() => {})
    token.clear()
    setUser(null)
  }, [])

  return (
    <AuthContext.Provider value={{ user, loading, login, register, logout, setUser }}>{children}</AuthContext.Provider>
  )
}

// oxlint-disable-next-line react/only-export-components -- hook lives beside its provider
export function useAuth() {
  const ctx = useContext(AuthContext)
  if (!ctx) throw new Error('useAuth must be used inside AuthProvider')
  return ctx
}
