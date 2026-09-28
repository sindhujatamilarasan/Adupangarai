import { useState } from 'react'
import { Link } from 'react-router-dom'
import { useApi, type RecipeSummary } from '../api'
import { HealthBadges } from '../components/Health'
import { EmptyState, ErrorState, Spinner } from '../components/ui'

const filters = [
  { key: '', label: 'All' },
  { key: 'meal_type=breakfast', label: 'Breakfast' },
  { key: 'meal_type=lunch', label: 'Lunch' },
  { key: 'meal_type=dinner', label: 'Dinner' },
  { key: 'meal_type=snack', label: 'Snack' },
  { key: 'max_time=30', label: 'Under 30 min' },
  { key: 'veg=1', label: 'Veg' },
  { key: 'health=high_protein', label: '💪 High protein' },
  { key: 'health=low_calorie', label: '🥗 Low calorie' },
  { key: 'health=high_fiber', label: '🌾 High fiber' },
  { key: 'mine=1', label: 'My recipes' },
]

export function VegDot({ veg }: { veg: boolean }) {
  return (
    <span
      title={veg ? 'Veg' : 'Non-veg'}
      className={`inline-grid size-4 shrink-0 place-items-center rounded-sm border-2 ${veg ? 'border-leaf' : 'border-red-700'}`}
    >
      <span className={`size-1.5 rounded-full ${veg ? 'bg-leaf' : 'bg-red-700'}`} />
    </span>
  )
}

export default function RecipesPage() {
  const [filter, setFilter] = useState('')
  const [search, setSearch] = useState('')
  const query = [filter, search.trim() && `search=${encodeURIComponent(search.trim())}`].filter(Boolean).join('&')
  const recipes = useApi<{ data: RecipeSummary[] }>(`/recipes?${query}`)

  return (
    <div>
      <div className="flex items-center justify-between">
        <h1 className="text-2xl font-extrabold">Recipes</h1>
        <Link to="/recipes/new" className="rounded-full bg-brand px-4 py-2 font-bold text-white shadow-sm">
          + New
        </Link>
      </div>

      <input
        type="search"
        placeholder="Search recipes…"
        value={search}
        onChange={(e) => setSearch(e.target.value)}
        className="mt-4 w-full rounded-2xl border border-line bg-white px-4 py-3 outline-none focus:border-brand"
      />

      <div className="-mx-4 mt-3 flex gap-2 overflow-x-auto px-4 pb-1">
        {filters.map((f) => (
          <button
            key={f.key}
            onClick={() => setFilter(f.key)}
            className={`shrink-0 rounded-full px-4 py-2 text-sm font-bold ${filter === f.key ? 'bg-ink text-white' : 'bg-white text-muted'}`}
          >
            {f.label}
          </button>
        ))}
      </div>

      <div className="mt-4 space-y-2">
        {recipes.loading && !recipes.data && <Spinner label="Finding recipes…" />}
        {recipes.error && <ErrorState message={recipes.error} onRetry={recipes.reload} />}
        {recipes.data?.data.length === 0 && (
          <EmptyState emoji="📖" title="No recipes found">
            {filter === 'mine=1' ? 'Recipes you create will appear here.' : 'Try a different search or filter.'}
          </EmptyState>
        )}
        {recipes.data?.data.map((r) => (
          <Link key={r.id} to={`/recipes/${r.id}`} className="block rounded-2xl bg-white p-4 shadow-sm active:scale-[.99]">
            <div className="flex items-center gap-2">
              <VegDot veg={r.is_veg} />
              <p className="flex-1 truncate font-bold">{r.name}</p>
              <span className="text-xs font-semibold text-muted">{r.total_time} min</span>
            </div>
            {r.description && <p className="mt-1 line-clamp-1 text-sm text-muted">{r.description}</p>}
            <p className="mt-1 text-xs font-semibold capitalize text-brand">
              {r.meal_type} · serves {r.servings} · {r.ingredients_count} ingredients
              {r.calories !== null && <span className="normal-case text-muted"> · {r.calories} kcal · {r.protein_g} g protein</span>}
            </p>
            {r.health_tags.length > 0 && (
              <div className="mt-2 flex flex-wrap gap-1.5">
                <HealthBadges tags={r.health_tags} />
              </div>
            )}
          </Link>
        ))}
      </div>
    </div>
  )
}
