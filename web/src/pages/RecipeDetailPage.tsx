import { useState } from 'react'
import { Link, useNavigate, useParams, useSearchParams } from 'react-router-dom'
import { api, ApiError, fmtQty, useApi, type RecipeDetail } from '../api'
import CookSheet from '../components/CookSheet'
import { HealthBadges, NutritionCard } from '../components/Health'
import { Alert, Button, ErrorState, Spinner } from '../components/ui'
import { MatchBar } from './CookPage'
import { VegDot } from './RecipesPage'

export default function RecipeDetailPage() {
  const { id } = useParams()
  const navigate = useNavigate()
  const [params] = useSearchParams()
  const planId = params.get('plan')
  const [servings, setServings] = useState<number | null>(Number(params.get('servings')) || null)
  const [cooking, setCooking] = useState(false)
  const [cooked, setCooked] = useState('')
  const res = useApi<{ data: RecipeDetail }>(`/recipes/${id}${servings ? `?servings=${servings}` : ''}`)
  const recipe = res.data?.data
  const [error, setError] = useState('')

  async function remove() {
    if (!recipe || !confirm(`Delete ${recipe.name}?`)) return
    try {
      await api(`/recipes/${id}`, { method: 'DELETE' })
      navigate('/cook', { replace: true })
    } catch (e) {
      setError((e as ApiError).message)
    }
  }

  if (!recipe && res.loading) return <Spinner label="Opening recipe…" />
  if (!recipe) return <ErrorState message={res.error ?? 'Recipe not found.'} onRetry={res.reload} />

  const current = recipe.requested_servings
  const m = recipe.match
  const shortById = new Map([...m.insufficient, ...m.missing, ...m.optional_missing].map((r) => [r.ingredient_id, r]))
  return (
    <div className="space-y-5">
      <button onClick={() => navigate(-1)} className="text-sm font-bold text-muted">
        ← Back
      </button>

      <header>
        <div className="flex items-center gap-2">
          <VegDot veg={recipe.is_veg} />
          <span className="text-xs font-bold uppercase tracking-wide text-brand">
            {recipe.meal_type}
            {recipe.cuisine && ` · ${recipe.cuisine}`}
          </span>
        </div>
        <h1 className="mt-1 text-3xl font-extrabold">{recipe.name}</h1>
        {recipe.description && <p className="mt-1 text-muted">{recipe.description}</p>}
        <div className="mt-3 flex flex-wrap gap-2 text-sm">
          <span className="rounded-full bg-white px-3 py-1 font-semibold">Prep {recipe.prep_time} min</span>
          <span className="rounded-full bg-white px-3 py-1 font-semibold">Cook {recipe.cook_time} min</span>
          <HealthBadges tags={recipe.health_tags} />
        </div>
      </header>

      <NutritionCard recipeId={recipe.id} values={recipe} canEstimate={recipe.is_editable} onEstimated={res.reload} />

      {error && <Alert kind="error">{error}</Alert>}

      <section className="rounded-3xl bg-white p-4">
        <div className="mb-3 flex items-center justify-between">
          <h2 className="text-lg font-extrabold">Ingredients</h2>
          <div className="flex items-center gap-2 rounded-full bg-cream p-1">
            <button
              aria-label="Fewer servings"
              disabled={current <= 1 || res.loading}
              onClick={() => setServings(current - 1)}
              className="grid size-8 place-items-center rounded-full bg-white text-lg font-bold disabled:opacity-40"
            >
              −
            </button>
            <span className="min-w-16 text-center text-sm font-bold">Serves {current}</span>
            <button
              aria-label="More servings"
              disabled={current >= 100 || res.loading}
              onClick={() => setServings(current + 1)}
              className="grid size-8 place-items-center rounded-full bg-white text-lg font-bold disabled:opacity-40"
            >
              +
            </button>
          </div>
        </div>
        {res.error && <Alert kind="error">{res.error}</Alert>}
        <div className="mb-2">
          <MatchBar percent={m.match_percent} status={m.status} />
          <p className="mt-1 text-xs text-muted">
            {m.status === 'available' ? 'You have everything for this.' : 'Compared with what’s in your kitchen (expired items excluded).'}
          </p>
        </div>
        <ul className={`divide-y divide-line ${res.loading ? 'opacity-50' : ''}`}>
          {recipe.ingredients.map((i) => {
            const short = shortById.get(i.ingredient_id)
            return (
              <li key={i.ingredient_id} className="flex items-center justify-between gap-3 py-2.5">
                <span className="flex items-center gap-2">
                  <span aria-hidden className={short ? (short.optional ? 'text-muted' : 'text-red-700') : 'text-leaf'}>
                    {short ? '○' : '✓'}
                  </span>
                  <span>
                    {i.ingredient.name}
                    {i.optional && <span className="ml-1 text-xs text-muted">(optional)</span>}
                    {short && (
                      <span className={`block text-xs ${short.optional ? 'text-muted' : 'text-red-700'}`}>
                        {short.have > 0 ? `Have ${fmtQty(short.have)} · need ${fmtQty(short.short ?? 0)} more` : 'Not in kitchen'}
                      </span>
                    )}
                  </span>
                </span>
                <span className="shrink-0 font-bold">
                  {fmtQty(i.quantity)} {i.unit}
                </span>
              </li>
            )
          })}
        </ul>
      </section>

      {recipe.steps.length > 0 && (
        <section className="rounded-3xl bg-white p-4">
          <h2 className="mb-3 text-lg font-extrabold">Method</h2>
          <ol className="space-y-3">
            {recipe.steps.map((s, i) => (
              <li key={i} className="flex gap-3">
                <span className="grid size-7 shrink-0 place-items-center rounded-full bg-brand text-sm font-bold text-white">{i + 1}</span>
                <p className="pt-0.5">{s}</p>
              </li>
            ))}
          </ol>
        </section>
      )}

      {cooked && <Alert kind="success">{cooked}</Alert>}
      <Button onClick={() => setCooking(true)}>🍳 Cook this · serves {current}</Button>
      {cooking && (
        <CookSheet
          recipeId={recipe.id}
          servings={current}
          mealPlanId={planId ? Number(planId) : null}
          onClose={() => setCooking(false)}
          onCooked={(message) => {
            setCooking(false)
            setCooked(message)
            res.reload()
          }}
        />
      )}

      {recipe.is_editable && (
        <div className="flex gap-3">
          <Link to={`/recipes/${id}/edit`} className="flex-1 rounded-2xl border border-brand bg-white py-3 text-center font-bold text-brand">
            Edit
          </Link>
          <button onClick={remove} className="flex-1 rounded-2xl border border-line bg-white py-3 font-bold text-red-700">
            Delete
          </button>
        </div>
      )}
    </div>
  )
}
