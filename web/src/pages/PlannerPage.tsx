import { useMemo, useState } from 'react'
import { Link, useLocation } from 'react-router-dom'
import { addDays, api, ApiError, isoDate, MEAL_TYPES, useApi, type DayNutrition, type MealPlanEntry, type MealType, type RecipeSummary } from '../api'
import { AiPlanSheet } from '../components/AiSheets'
import { ProteinWeekChart } from '../components/Health'
import { Alert, Button, ErrorState, Sheet, Spinner } from '../components/ui'
import { VegDot } from './RecipesPage'

const mealLabel: Record<MealType, string> = { breakfast: 'Breakfast', lunch: 'Lunch', snack: 'Snack', dinner: 'Dinner' }

type Editing = { mode: 'add'; date: string; meal_type: MealType } | { mode: 'edit'; entry: MealPlanEntry }

export default function PlannerPage() {
  const [start, setStart] = useState<string | null>(null)
  const res = useApi<{ data: MealPlanEntry[]; start: string; end: string; nutrition: Record<string, DayNutrition> }>(`/meal-plans${start ? `?start=${start}` : ''}`)
  const [editing, setEditing] = useState<Editing | null>(null)
  const [aiOpen, setAiOpen] = useState(false)
  const [flash, setFlash] = useState<string>((useLocation().state as { flash?: string } | null)?.flash ?? '')
  const weekStart = res.data?.start
  const today = isoDate(new Date())

  const byDay = useMemo(() => {
    const map = new Map<string, MealPlanEntry[]>()
    res.data?.data.forEach((p) => map.set(`${p.date}|${p.meal_type}`, [...(map.get(`${p.date}|${p.meal_type}`) ?? []), p]))
    return map
  }, [res.data])

  const fmt = (iso: string, opts: Intl.DateTimeFormatOptions) => new Date(`${iso}T00:00:00`).toLocaleDateString(undefined, opts)

  return (
    <div>
      <div className="flex items-center justify-between">
        <h1 className="text-2xl font-extrabold">Meal planner</h1>
        <button onClick={() => setAiOpen(true)} className="rounded-full bg-gradient-to-br from-brand to-amber-500 px-4 py-2 text-sm font-extrabold text-white shadow-sm">
          ✨ AI plan
        </button>
      </div>
      {flash && (
        <div className="mt-3">
          <Alert kind="success">{flash}</Alert>
        </div>
      )}

      {weekStart && (
        <div className="mt-4 flex items-center justify-between rounded-2xl bg-white p-2">
          <button onClick={() => setStart(addDays(weekStart, -7))} className="rounded-xl px-3 py-2 font-bold text-muted" aria-label="Previous week">
            ←
          </button>
          <button onClick={() => setStart(null)} className="text-sm font-bold">
            {fmt(weekStart, { day: 'numeric', month: 'short' })} – {fmt(res.data!.end, { day: 'numeric', month: 'short' })}
          </button>
          <button onClick={() => setStart(addDays(weekStart, 7))} className="rounded-xl px-3 py-2 font-bold text-muted" aria-label="Next week">
            →
          </button>
        </div>
      )}

      <div className="mt-4 space-y-4">
        {weekStart && <ProteinWeekChart days={Array.from({ length: 7 }, (_, i) => addDays(weekStart, i))} nutrition={res.data!.nutrition} />}
        {res.loading && !res.data && <Spinner label="Loading your week…" />}
        {res.error && <ErrorState message={res.error} onRetry={res.reload} />}
        {weekStart &&
          Array.from({ length: 7 }, (_, i) => addDays(weekStart, i)).map((date) => (
            <section key={date} className={`rounded-3xl bg-white p-4 ${date === today ? 'ring-2 ring-brand' : ''}`}>
              <h2 className="mb-2 font-extrabold">
                {fmt(date, { weekday: 'long' })} <span className="font-semibold text-muted">{fmt(date, { day: 'numeric', month: 'short' })}</span>
                {date === today && <span className="ml-2 text-xs font-bold text-brand">TODAY</span>}
              </h2>
              <div className="divide-y divide-line">
                {MEAL_TYPES.map((meal) => (
                  <div key={meal} className="flex items-start gap-3 py-2">
                    <span className="w-20 shrink-0 pt-1.5 text-xs font-bold uppercase text-muted">{mealLabel[meal]}</span>
                    <div className="flex flex-1 flex-wrap gap-1.5">
                      {(byDay.get(`${date}|${meal}`) ?? []).map((p) => (
                        <button
                          key={p.id}
                          onClick={() => setEditing({ mode: 'edit', entry: p })}
                          className={`flex items-center gap-1.5 rounded-full px-3 py-1.5 text-sm font-semibold ${p.cooked_at ? 'bg-green-50 text-leaf' : 'bg-cream'}`}
                        >
                          <VegDot veg={p.recipe.is_veg} />
                          {p.recipe.name}
                          <span className="text-xs text-muted">×{p.servings}</span>
                          {p.cooked_at && <span aria-label="cooked">✓</span>}
                        </button>
                      ))}
                      <button
                        onClick={() => setEditing({ mode: 'add', date, meal_type: meal })}
                        className="rounded-full border border-dashed border-line px-3 py-1.5 text-sm font-bold text-muted"
                        aria-label={`Add ${meal} on ${date}`}
                      >
                        +
                      </button>
                    </div>
                  </div>
                ))}
              </div>
            </section>
          ))}
      </div>

      {aiOpen && (
        <AiPlanSheet
          start={weekStart && weekStart > today ? weekStart : today}
          onClose={() => setAiOpen(false)}
          onDone={(m) => {
            setAiOpen(false)
            setFlash(m)
            res.reload()
          }}
        />
      )}
      {editing && <PlanSheet key={editing.mode === 'edit' ? editing.entry.id : 'add'} editing={editing} onClose={() => setEditing(null)} onSaved={res.reload} />}
    </div>
  )
}

function PlanSheet({ editing, onClose, onSaved }: { editing: Editing; onClose: () => void; onSaved: () => void }) {
  const entry = editing.mode === 'edit' ? editing.entry : null
  const recipes = useApi<{ data: RecipeSummary[] }>('/recipes')
  const [search, setSearch] = useState('')
  const [recipeId, setRecipeId] = useState<number | null>(entry?.recipe_id ?? null)
  const [servings, setServings] = useState(entry?.servings ?? 2)
  const [picking, setPicking] = useState(!entry)
  const [error, setError] = useState('')
  const [busy, setBusy] = useState(false)

  const list = recipes.data?.data ?? []
  const date = entry?.date ?? (editing.mode === 'add' ? editing.date : '')
  const meal = entry?.meal_type ?? (editing.mode === 'add' ? editing.meal_type : 'lunch')
  const chosen = list.find((r) => r.id === recipeId) ?? (entry ? { ...entry.recipe } : null)
  const matches = list
    .filter((r) => r.name.toLowerCase().includes(search.trim().toLowerCase()))
    .sort((a, b) => Number(b.meal_type === meal) - Number(a.meal_type === meal))

  async function save() {
    setBusy(true)
    setError('')
    try {
      const body = { date, meal_type: meal, recipe_id: recipeId, servings }
      await api(entry ? `/meal-plans/${entry.id}` : '/meal-plans', { method: entry ? 'PATCH' : 'POST', body })
      onSaved()
      onClose()
    } catch (e) {
      setError((e as ApiError).message)
    } finally {
      setBusy(false)
    }
  }

  async function remove() {
    setBusy(true)
    try {
      await api(`/meal-plans/${entry!.id}`, { method: 'DELETE' })
      onSaved()
      onClose()
    } catch (e) {
      setError((e as ApiError).message)
      setBusy(false)
    }
  }

  const when = `${mealLabel[meal]} · ${new Date(`${date}T00:00:00`).toLocaleDateString(undefined, { weekday: 'short', day: 'numeric', month: 'short' })}`

  return (
    <Sheet open onClose={onClose} title={entry ? 'Planned meal' : 'Plan a meal'}>
      <p className="-mt-2 mb-4 text-sm font-semibold text-brand">{when}</p>
      {error && (
        <div className="mb-3">
          <Alert kind="error">{error}</Alert>
        </div>
      )}
      {picking ? (
        <div className="space-y-3">
          <input
            type="search"
            placeholder="Search recipes…"
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            className="w-full rounded-2xl border border-line bg-white px-4 py-3 outline-none focus:border-brand"
            autoFocus
          />
          {recipes.loading && <Spinner />}
          {recipes.error && <ErrorState message={recipes.error} onRetry={recipes.reload} />}
          <ul className="max-h-80 divide-y divide-line overflow-y-auto rounded-2xl bg-white">
            {matches.map((r) => (
              <li key={r.id}>
                <button
                  onClick={() => {
                    setRecipeId(r.id)
                    if (!entry) setServings(r.servings)
                    setPicking(false)
                  }}
                  className="flex w-full items-center gap-2 px-4 py-3 text-left"
                >
                  <VegDot veg={r.is_veg} />
                  <span className="flex-1 font-semibold">{r.name}</span>
                  <span className="text-xs capitalize text-muted">{r.meal_type}</span>
                </button>
              </li>
            ))}
            {recipes.data && matches.length === 0 && <li className="px-4 py-6 text-center text-sm text-muted">No recipes match.</li>}
          </ul>
        </div>
      ) : (
        <div className="space-y-4">
          <div className="flex items-center justify-between rounded-2xl bg-white p-4">
            <div className="flex items-center gap-2">
              {chosen && <VegDot veg={chosen.is_veg} />}
              <span className="font-bold">{chosen?.name}</span>
            </div>
            <button onClick={() => setPicking(true)} className="text-sm font-bold text-brand">
              Change
            </button>
          </div>
          <div className="flex items-center justify-between rounded-2xl bg-white p-4">
            <span className="font-semibold">Servings</span>
            <div className="flex items-center gap-3">
              <button onClick={() => setServings(Math.max(1, servings - 1))} className="grid size-9 place-items-center rounded-full bg-cream text-lg font-bold" aria-label="Fewer">
                −
              </button>
              <span className="w-6 text-center text-lg font-extrabold">{servings}</span>
              <button onClick={() => setServings(Math.min(100, servings + 1))} className="grid size-9 place-items-center rounded-full bg-cream text-lg font-bold" aria-label="More">
                +
              </button>
            </div>
          </div>
          <Button onClick={save} loading={busy}>
            {entry ? 'Save changes' : 'Add to plan'}
          </Button>
          {entry && (
            <div className="flex gap-3">
              <Link
                to={`/recipes/${entry.recipe_id}?servings=${entry.servings}&plan=${entry.id}`}
                className="flex-1 rounded-2xl border border-brand bg-white py-3 text-center font-bold text-brand"
              >
                {entry.cooked_at ? 'View recipe' : 'Open & cook'}
              </Link>
              <button onClick={remove} disabled={busy} className="flex-1 rounded-2xl border border-line bg-white py-3 font-bold text-red-700">
                Remove
              </button>
            </div>
          )}
        </div>
      )}
    </Sheet>
  )
}
