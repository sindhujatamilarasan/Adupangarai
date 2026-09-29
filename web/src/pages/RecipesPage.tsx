import { useState } from 'react'
import { Link } from 'react-router-dom'
import { useApi, type RecipeSummary } from '../api'
import { AiRecipeSheet } from '../components/AiSheets'
import { HealthBadges } from '../components/Health'
import RecipeCover from '../components/RecipeCover'
import { EmptyState, ErrorState, Spinner } from '../components/ui'
import { nm, tk, useI18n } from '../i18n'

const filters = [
  { key: '', label: tk('All') },
  { key: 'meal_type=breakfast', label: tk('Breakfast') },
  { key: 'meal_type=lunch', label: tk('Lunch') },
  { key: 'meal_type=dinner', label: tk('Dinner') },
  { key: 'meal_type=snack', label: tk('Snack') },
  { key: 'max_time=30', label: tk('Under 30 min') },
  { key: 'veg=1', label: tk('Veg') },
  { key: 'health=high_protein', label: tk('💪 High protein') },
  { key: 'health=low_calorie', label: tk('🥗 Low calorie') },
  { key: 'health=high_fiber', label: tk('🌾 High fiber') },
  { key: 'mine=1', label: tk('My recipes') },
]

export function VegDot({ veg }: { veg: boolean }) {
  const { t } = useI18n()
  return (
    <span
      title={veg ? t('Veg') : t('Non-veg')}
      className={`inline-grid size-4 shrink-0 place-items-center rounded-sm border-2 ${veg ? 'border-leaf' : 'border-red-700'}`}
    >
      <span className={`size-1.5 rounded-full ${veg ? 'bg-leaf' : 'bg-red-700'}`} />
    </span>
  )
}

export default function RecipesPage() {
  const { t } = useI18n()
  const [filter, setFilter] = useState('')
  const [search, setSearch] = useState('')
  const [speaking, setSpeaking] = useState(false)
  const query = [filter, search.trim() && `search=${encodeURIComponent(search.trim())}`].filter(Boolean).join('&')
  const recipes = useApi<{ data: RecipeSummary[] }>(`/recipes?${query}`)

  return (
    <div>
      <div className="flex flex-wrap items-center justify-between gap-x-3 gap-y-2">
        <h1 className="font-display text-[1.75rem] font-semibold tracking-tight">{t('Recipes')}</h1>
        <div className="flex gap-2">
          <button onClick={() => setSpeaking(true)} className="rounded-full border border-line bg-white px-4 py-2 text-sm font-semibold text-brand whitespace-nowrap">
            🎙️ {t('Say')}
          </button>
          <Link to="/recipes/new" className="rounded-full bg-brand px-4 py-2 text-sm font-semibold text-white whitespace-nowrap">
            + {t('New')}
          </Link>
        </div>
      </div>

      <input
        type="search"
        placeholder={t('Search recipes…')}
        value={search}
        onChange={(e) => setSearch(e.target.value)}
        className="mt-4 w-full rounded-2xl border border-line bg-white px-4 py-3 outline-none focus:border-brand"
      />

      <div className="-mx-4 mt-3 flex gap-2 overflow-x-auto px-4 pb-1">
        {filters.map((f) => (
          <button
            key={f.key}
            onClick={() => setFilter(f.key)}
            className={`shrink-0 rounded-full px-4 py-2 text-sm font-bold ${filter === f.key ? 'border border-ink bg-ink text-white' : 'border border-line bg-white text-muted'}`}
          >
            {t(f.label)}
          </button>
        ))}
      </div>

      <div className="mt-4 space-y-2">
        {recipes.loading && !recipes.data && <Spinner label={t('Finding recipes…')} />}
        {recipes.error && <ErrorState message={recipes.error} onRetry={recipes.reload} />}
        {recipes.data?.data.length === 0 && (
          <EmptyState emoji="📖" title={t('No recipes found')}>
            {filter === 'mine=1' ? t('Recipes you create will appear here.') : t('Try a different search or filter.')}
          </EmptyState>
        )}
        {recipes.data?.data.map((r) => (
          <Link key={r.id} to={`/recipes/${r.id}`} className="flex gap-3 card p-3 active:scale-[.99]">
            <RecipeCover recipe={r} className="size-24 shrink-0 rounded-2xl" />
            <div className="min-w-0 flex-1">
            <div className="flex items-center gap-2">
              <VegDot veg={r.is_veg} />
              <p className="flex-1 truncate font-semibold">{nm(r)}</p>
              <span className="text-xs font-semibold text-muted">{t('{n} min', { n: r.total_time })}</span>
            </div>
            {(r.blurb ?? r.description) && <p className="mt-1 line-clamp-1 text-sm text-muted">{r.blurb ?? r.description}</p>}
            <p className="mt-1 text-xs font-semibold text-brand">
              {t(r.meal_type)} · {t('serves {n}', { n: r.servings })} · {t('{n} ingredients', { n: r.ingredients_count })}
              {r.calories !== null && <span className="text-muted"> · {r.calories} kcal · {t('{n} g protein', { n: r.protein_g ?? 0 })}</span>}
            </p>
            {r.health_tags.length > 0 && (
              <div className="mt-2 flex flex-wrap gap-1.5">
                <HealthBadges tags={r.health_tags} />
              </div>
            )}
            </div>
          </Link>
        ))}
      </div>
      {speaking && <AiRecipeSheet onClose={() => setSpeaking(false)} />}
    </div>
  )
}
