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
  const isForm = options.body instanceof FormData
  if (options.body !== undefined && !isForm) headers['Content-Type'] = 'application/json'
  const t = token.get()
  if (t) headers.Authorization = `Bearer ${t}`

  let res: Response
  try {
    res = await fetch(`/api${path}`, {
      method: options.method ?? 'GET',
      headers,
      body: isForm ? (options.body as FormData) : options.body !== undefined ? JSON.stringify(options.body) : undefined,
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
export type Category = { id: number; name: string; icon: string | null }
export type Ingredient = { id: number; name: string; default_unit: UnitValue; ingredient_category_id: number; category: Category; display_icon: string }
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

export type MealType = 'breakfast' | 'lunch' | 'snack' | 'dinner'
export const MEAL_TYPES: MealType[] = ['breakfast', 'lunch', 'snack', 'dinner']
export type RecipeSummary = {
  id: number
  name: string
  description: string | null
  meal_type: MealType
  cuisine: string | null
  servings: number
  prep_time: number
  cook_time: number
  total_time: number
  is_veg: boolean
  is_editable: boolean
  ingredients_count: number
  health_tags: HealthTag[]
  image_url: string | null
} & NutritionValues

export type HealthTag = 'high_protein' | 'low_calorie' | 'high_fiber'
/** Estimated, per serving; null until estimated. */
export type NutritionValues = { calories: number | null; protein_g: number | null; carbs_g: number | null; fat_g: number | null; fiber_g: number | null }
export type RecipeIngredientRow = { ingredient_id: number; ingredient: Ingredient; quantity: number; unit: UnitValue; optional: boolean }
export type RecipeDetail = Omit<RecipeSummary, 'ingredients_count'> & {
  requested_servings: number
  ingredients: RecipeIngredientRow[]
  steps: string[]
  match: RecipeMatch
}

export type MatchRow = { ingredient_id: number; name: string; unit: UnitValue; need: number; have: number; optional: boolean; short?: number }
export type RecipeMatch = {
  servings: number
  match_percent: number
  status: 'available' | 'almost' | 'unavailable'
  available: MatchRow[]
  missing: MatchRow[]
  insufficient: MatchRow[]
  optional_missing: MatchRow[]
  uses_expiring: { ingredient_id: number; name: string; days_to_expiry: number }[]
}
export type CookResult = {
  recipe: Pick<RecipeSummary, 'id' | 'name' | 'description' | 'meal_type' | 'cuisine' | 'servings' | 'total_time' | 'is_veg' | 'calories' | 'protein_g' | 'health_tags' | 'image_url'>
  match: RecipeMatch
}

export type MealPlanEntry = {
  id: number
  date: string
  meal_type: MealType
  recipe_id: number
  servings: number
  cooked_at: string | null
  recipe: Pick<RecipeSummary, 'id' | 'name' | 'meal_type' | 'servings' | 'prep_time' | 'cook_time' | 'is_veg' | 'calories' | 'protein_g' | 'carbs_g' | 'fat_g' | 'fiber_g' | 'image_url'>
}
export type DayNutrition = { calories: number; protein_g: number; carbs_g: number; fat_g: number; fiber_g: number; missing: number }

/** Local calendar date as YYYY-MM-DD (display/navigation only). */
export const isoDate = (d: Date) => `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`
export const addDays = (iso: string, n: number) => {
  const d = new Date(`${iso}T00:00:00`)
  d.setDate(d.getDate() + n)
  return isoDate(d)
}

export type GroceryItem = {
  id: number
  ingredient_id: number | null
  name: string
  category: string
  quantity: number | null
  unit: UnitValue | null
  source: 'plan' | 'manual'
  purchased: boolean
  actual_quantity: number | null
  price: number | null
  added_to_pantry_at: string | null
  ingredient: { display_icon: string } | null
}
export type GroceryResponse = {
  data: GroceryItem[]
  list: { planned_from: string | null; planned_to: string | null }
  summary: { total: number; remaining: number; to_add_to_pantry: number; spent: number }
}

export type CookRow = {
  ingredient_id: number
  name: string
  optional: boolean
  need: number
  unit: UnitValue
  short: number
  pantry_item_id: number | null
  deduct: number
  pantry_unit: UnitValue | null
  pantry_after: number | null
}

export type Dashboard = {
  today_meals: { id: number; meal_type: MealType; servings: number; cooked_at: string | null; recipe: { id: number; name: string; is_veg: boolean; meal_type: MealType; image_url: string | null }; can_cook_now: boolean }[]
  cook_now: CookResult[]
  almost_count: number
  use_soon: CookResult[]
  expiring: (Pick<PantryItem, 'id' | 'quantity' | 'unit' | 'days_to_expiry'> & { ingredient: { id: number; name: string; display_icon: string } })[]
  expired_count: number
  low_stock: (Pick<PantryItem, 'id' | 'quantity' | 'unit' | 'minimum_stock'> & { ingredient: { id: number; name: string; display_icon: string } })[]
  grocery_remaining: number
  pantry_count: number
  recipe_count: number
}

export type AiItem = {
  heard: string
  ingredient_id: number | null
  name: string
  quantity: number | null
  unit: UnitValue | null
  expiry_days?: number | null
  problem: null | 'unknown_ingredient' | 'no_quantity' | 'unit_mismatch' | 'guessed'
  optional?: boolean
}
export type RecipeDraft = Omit<RecipeDetail, 'id' | 'ingredients' | 'match' | 'requested_servings' | 'total_time' | 'is_editable' | 'health_tags' | 'image_url'> & {
  ingredients: AiItem[]
}
export type AiPlanEntry = { date: string; meal_type: MealType; recipe_id: number; servings: number; recipe_name: string }
export const AI_WAIT = 'On a home computer the free AI can take 1–2 minutes — please keep this open.'
