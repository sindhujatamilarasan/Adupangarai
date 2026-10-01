/** Data shapes returned by the Adupangarai API, plus small display helpers. Used by the web and mobile apps. */

export type Household = { id: number; name: string }
export type User = { id: number; name: string; email: string; locale: 'en' | 'ta'; household_id: number; household: Household }

export type UnitValue = 'g' | 'kg' | 'ml' | 'L' | 'cup' | 'tbsp' | 'tsp' | 'piece' | 'packet'
export type UnitInfo = { value: UnitValue; dimension: string }
export type Category = { id: number; name: string; label?: string; icon: string | null }
export type Ingredient = { id: number; name: string; label?: string; default_unit: UnitValue; ingredient_category_id: number; category: Category; display_icon: string }
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

export type MealType = 'breakfast' | 'lunch' | 'snack' | 'dinner'
export const MEAL_TYPES: MealType[] = ['breakfast', 'lunch', 'snack', 'dinner']
export type RecipeSummary = {
  id: number
  name: string
  label?: string
  blurb?: string | null
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
  recipe: Pick<RecipeSummary, 'id' | 'name' | 'label' | 'blurb' | 'description' | 'meal_type' | 'cuisine' | 'servings' | 'total_time' | 'is_veg' | 'calories' | 'protein_g' | 'health_tags' | 'image_url'>
  match: RecipeMatch
}

export type MealPlanEntry = {
  id: number
  date: string
  meal_type: MealType
  recipe_id: number
  servings: number
  cooked_at: string | null
  recipe: Pick<RecipeSummary, 'id' | 'name' | 'label' | 'meal_type' | 'servings' | 'prep_time' | 'cook_time' | 'is_veg' | 'calories' | 'protein_g' | 'carbs_g' | 'fat_g' | 'fiber_g' | 'image_url'>
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
  label: string
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
  today_meals: { id: number; meal_type: MealType; servings: number; cooked_at: string | null; recipe: { id: number; name: string; label?: string; is_veg: boolean; meal_type: MealType; image_url: string | null }; can_cook_now: boolean }[]
  cook_now: CookResult[]
  almost_count: number
  use_soon: CookResult[]
  expiring: (Pick<PantryItem, 'id' | 'quantity' | 'unit' | 'days_to_expiry'> & { ingredient: { id: number; name: string; label?: string; display_icon: string } })[]
  expired_count: number
  low_stock: (Pick<PantryItem, 'id' | 'quantity' | 'unit' | 'minimum_stock'> & { ingredient: { id: number; name: string; label?: string; display_icon: string } })[]
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

export type CoachGoal = 'lose' | 'maintain' | 'gain'
export type Activity = 'sedentary' | 'light' | 'moderate' | 'active'
export type HealthProfile = {
  sex: 'male' | 'female'
  birth_year: number
  height_cm: number
  start_weight_kg: number
  target_weight_kg: number
  activity: Activity
  goal: CoachGoal
  pace_kg: number
  step_goal: number
  started_on: string
}
export type FoodLog = { id: number; date: string; meal_plan_id: number | null; name: string; portion: number; calories: number; protein_g: number; source: 'plan' | 'quick' | 'ai' | 'manual' }
export type QuickFood = { name: string; label: string; calories: number; protein_g: number }
export type CoachData = {
  profile: HealthProfile
  current_weight: number
  bmi: number
  healthy_weight: [number, number]
  target: { calories: number; protein_g: number; bmr: number; maintenance: number; floored: boolean }
  today: {
    date: string
    calories: number
    protein_g: number
    planned_calories: number
    steps: number
    planned: { id: number; meal_type: MealType; recipe: Pick<RecipeSummary, 'id' | 'name' | 'label' | 'is_veg' | 'calories' | 'protein_g' | 'image_url' | 'meal_type'>; log: FoodLog | null }[]
    extras: FoodLog[]
  }
  week: { date: string; calories: number; steps: number; on_target: boolean; logged: boolean }[]
  streaks: { on_target: number; steps: number; plan: number }
  badges: { key: string; earned_on: string | null }[]
  tips: string[]
  weights: { date: string; weight_kg: number }[]
  progress: { start: number; current: number; target: number; done_kg: number; percent: number; weeks_left: number } | null
  quick_foods: QuickFood[]
}
export type CoachResponse = CoachData | { profile: null }
