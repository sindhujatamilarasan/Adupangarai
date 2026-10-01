import { useCallback, useEffect, useState } from 'react'
import { currentLang, translate } from './i18n'
import { storage } from './storage'

export * from '../../../shared/types'

/** The Laravel API, e.g. https://adupangarai.in (set at build time). */
export const API_BASE = (process.env.EXPO_PUBLIC_API_URL ?? 'http://10.0.2.2:8787').replace(/\/$/, '')

const TOKEN_KEY = 'adupangarai.token'
let tokenValue: string | null = null
export const token = {
  load: async () => (tokenValue = await storage.get(TOKEN_KEY)),
  get: () => tokenValue,
  set: (t: string) => {
    tokenValue = t
    storage.set(TOKEN_KEY, t)
  },
  clear: () => {
    tokenValue = null
    storage.remove(TOKEN_KEY)
  },
}

/** Called when the server says the login has expired (the auth provider listens). */
let onExpired: () => void = () => {}
export const setOnExpired = (fn: () => void) => (onExpired = fn)

export class ApiError extends Error {
  status: number
  errors: Record<string, string[]>
  constructor(status: number, message: string, errors: Record<string, string[]> = {}) {
    super(message)
    this.status = status
    this.errors = errors
  }
}

export async function api<T>(path: string, options: { method?: string; body?: unknown } = {}): Promise<T> {
  const headers: Record<string, string> = { Accept: 'application/json', 'Accept-Language': currentLang() }
  const isForm = options.body instanceof FormData
  if (options.body !== undefined && !isForm) headers['Content-Type'] = 'application/json'
  if (tokenValue) headers.Authorization = `Bearer ${tokenValue}`

  let res: Response
  try {
    res = await fetch(`${API_BASE}/api${path}`, {
      method: options.method ?? 'GET',
      headers,
      body: isForm ? (options.body as FormData) : options.body !== undefined ? JSON.stringify(options.body) : undefined,
    })
  } catch {
    throw new ApiError(0, translate(currentLang(), 'Could not reach the server. Check your connection.'))
  }

  const data = res.status === 204 ? null : await res.json().catch(() => null)
  if (!res.ok) {
    if (res.status === 401 && tokenValue) {
      token.clear()
      onExpired()
    }
    throw new ApiError(res.status, data?.message ?? translate(currentLang(), 'Something went wrong.'), data?.errors)
  }
  return data as T
}

/** GET with loading / error state; keeps the last data while reloading. `reload` refetches. */
export function useApi<T>(path: string | null) {
  const [tick, setTick] = useState(0)
  const key = path && `${path}#${tick}`
  const [state, setState] = useState<{ done: string | null; data: T | null; error: string | null }>({ done: null, data: null, error: null })

  useEffect(() => {
    if (!path || !key) return
    let cancelled = false
    api<T>(path)
      .then((data) => !cancelled && setState({ done: key, data, error: null }))
      .catch((e: ApiError) => !cancelled && setState((s) => ({ ...s, done: key, error: e.message })))
    return () => {
      cancelled = true
    }
  }, [path, key])

  const reload = useCallback(() => setTick((t) => t + 1), [])
  return { data: state.data, error: state.done === key ? state.error : null, loading: !!key && state.done !== key, reload }
}
