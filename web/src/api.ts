import { useCallback, useEffect, useState } from 'react'

const TOKEN_KEY = 'adupangarai.token'

export const token = {
  get: () => localStorage.getItem(TOKEN_KEY),
  set: (t: string) => localStorage.setItem(TOKEN_KEY, t),
  clear: () => localStorage.removeItem(TOKEN_KEY),
}

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
  const headers: Record<string, string> = { Accept: 'application/json' }
  if (options.body !== undefined) headers['Content-Type'] = 'application/json'
  const t = token.get()
  if (t) headers.Authorization = `Bearer ${t}`

  let res: Response
  try {
    res = await fetch(`/api${path}`, {
      method: options.method ?? 'GET',
      headers,
      body: options.body !== undefined ? JSON.stringify(options.body) : undefined,
    })
  } catch {
    throw new ApiError(0, 'Could not reach the server. Check your connection.')
  }

  const data = res.status === 204 ? null : await res.json().catch(() => null)
  if (!res.ok) {
    if (res.status === 401) {
      token.clear()
      window.dispatchEvent(new Event('auth:expired'))
    }
    throw new ApiError(res.status, data?.message ?? 'Something went wrong.', data?.errors)
  }
  return data as T
}

export type Household = { id: number; name: string }
export type User = { id: number; name: string; email: string; household_id: number; household: Household }

export type UnitValue = 'g' | 'kg' | 'ml' | 'L' | 'cup' | 'tbsp' | 'tsp' | 'piece' | 'packet'
export type UnitInfo = { value: UnitValue; dimension: string }
export type Category = { id: number; name: string }
export type Ingredient = { id: number; name: string; default_unit: UnitValue; ingredient_category_id: number; category: Category }
export type ExpiryStatus = 'fresh' | 'expiring_soon' | 'expired' | null
export type PantryView = 'all' | 'low_stock' | 'expiring_soon' | 'expired'
export type PantryItem = {
  id: number
  ingredient: Ingredient
  quantity: number
  unit: UnitValue
  expiry_date: string | null
  minimum_stock: number | null
  storage_location: 'pantry' | 'fridge' | 'freezer' | null
  expiry_status: ExpiryStatus
  days_to_expiry: number | null
  is_low_stock: boolean
}
export type PantryTransaction = {
  id: number
  type: 'PURCHASE' | 'ADD' | 'COOKED' | 'ADJUSTMENT' | 'EXPIRED' | 'DISCARDED'
  quantity_change: number
  unit: UnitValue
  balance_after: number
  note: string | null
  created_at: string
  user: { id: number; name: string } | null
}

/** Display only: 1.500 -> "1.5" */
export const fmtQty = (n: number) => String(Number(n.toFixed(3)))

export function useApi<T>(path: string | null) {
  const [tick, setTick] = useState(0)
  const key = path && `${path}#${tick}`
  // `done` is the key of the last finished request; data is kept while reloading.
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
