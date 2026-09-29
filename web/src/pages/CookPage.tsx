import { useState } from 'react'
import { Link } from 'react-router-dom'
import { fmtQty, useApi, type CookResult } from '../api'
import { HealthBadges } from '../components/Health'
import RecipeCover from '../components/RecipeCover'
import { Badge, EmptyState, ErrorState, Spinner } from '../components/ui'
import { VegDot } from './RecipesPage'

const availability = [
  { key: 'all', label: 'All' },
  { key: 'available', label: 'Available now' },
  { key: 'almost', label: 'Almost' },
  { key: 'use_soon', label: 'Use soon' },
]
const extras = [
  { key: '', label: 'Any meal' },
  { key: 'meal_type=breakfast', label: 'Breakfast' },
  { key: 'meal_type=lunch', label: 'Lunch' },
  { key: 'meal_type=dinner', label: 'Dinner' },
  { key: 'max_time=30', label: 'Under 30 min' },
  { key: 'health=high_protein', label: '💪 High protein' },
  { key: 'health=low_calorie', label: '🥗 Low calorie' },
]

const empty: Record<string, [string, string, string]> = {
  all: ['📖', 'No recipes match', 'Try a different meal or time filter.'],
  available: ['🧺', 'Nothing fully ready yet', 'Check "Almost" — you may be just an ingredient or two away.'],
  almost: ['🛒', 'No near misses', 'Add more to your kitchen to unlock recipes.'],
  use_soon: ['🌿', 'Nothing needs using up', 'Recipes using items that expire within 3 days show up here.'],
}

function Chips({ items, value, onChange }: { items: { key: string; label: string }[]; value: string; onChange: (k: string) => void }) {
  return (
    <div className="-mx-4 flex gap-2 overflow-x-auto px-4 pb-1">
      {items.map((f) => (
        <button
          key={f.key}
          onClick={() => onChange(f.key)}
          className={`shrink-0 rounded-full px-4 py-2 text-sm font-bold ${value === f.key ? 'border border-ink bg-ink text-white' : 'border border-line bg-white text-muted'}`}
        >
          {f.label}
        </button>
      ))}
    </div>
  )
}

export function MatchBar({ percent, status }: { percent: number; status: CookResult['match']['status'] }) {
  const color = status === 'available' ? 'bg-leaf' : status === 'almost' ? 'bg-amber-500' : 'bg-stone-300'
  return (
    <div className="flex items-center gap-2">
      <div className="h-2 flex-1 overflow-hidden rounded-full bg-cream">
        <div className={`h-full rounded-full ${color}`} style={{ width: `${percent}%` }} />
      </div>
      <span className="w-10 text-right text-xs font-bold">{percent}%</span>
    </div>
  )
}

/** One calm line instead of a pill per item: "Use soon: Coriander Leaves (today) +2". */
function expiringLabel(items: CookResult['match']['uses_expiring']) {
  const [first, ...rest] = [...items].sort((a, b) => a.days_to_expiry - b.days_to_expiry)
  const when = first.days_to_expiry === 0 ? 'today' : first.days_to_expiry === 1 ? 'tomorrow' : `${first.days_to_expiry}d`
  return `Use soon: ${first.name} (${when})${rest.length ? ` +${rest.length}` : ''}`
}

function shortSummary(m: CookResult['match']) {
  const rows = [...m.insufficient, ...m.missing]
  if (rows.length === 0) return 'You have everything'
  const parts = rows.slice(0, 3).map((r) => `${r.name} ${fmtQty(r.short ?? 0)} ${r.unit}`)
  return `Need ${parts.join(', ')}${rows.length > 3 ? ` +${rows.length - 3} more` : ''}`
}

export default function CookPage() {
  const [filter, setFilter] = useState('all')
  const [extra, setExtra] = useState('')
  const res = useApi<{ data: CookResult[]; pantry_count: number }>(`/cook?filter=${filter}${extra ? `&${extra}` : ''}`)

  return (
    <div>
      <div className="flex items-center justify-between">
        <h1 className="font-display text-[1.75rem] font-semibold tracking-tight">What can I cook?</h1>
        <Link to="/recipes" className="text-sm font-bold text-brand">
          All recipes →
        </Link>
      </div>
      <div className="mt-4 space-y-2">
        <Chips items={availability} value={filter} onChange={setFilter} />
        <Chips items={extras} value={extra} onChange={setExtra} />
      </div>

      <div className="mt-4 space-y-2">
        {res.loading && !res.data && <Spinner label="Checking your kitchen…" />}
        {res.error && <ErrorState message={res.error} onRetry={res.reload} />}
        {res.data?.pantry_count === 0 && (
          <div className="rounded-2xl bg-amber-50 p-4 text-sm text-amber-900">
            Your kitchen is empty, so every recipe shows 0%.{' '}
            <Link to="/kitchen" className="font-bold underline">
              Add what you have
            </Link>
            .
          </div>
        )}
        {res.data?.data.length === 0 && (
          <EmptyState emoji={empty[filter][0]} title={empty[filter][1]}>
            {empty[filter][2]}
          </EmptyState>
        )}
        {res.data?.data.map(({ recipe, match }) => (
          <Link key={recipe.id} to={`/recipes/${recipe.id}`} className="flex gap-3 card p-3 active:scale-[.99]">
            <RecipeCover recipe={{ ...recipe, meal_type: recipe.meal_type }} className="size-20 shrink-0 rounded-2xl" />
            <div className="min-w-0 flex-1 space-y-2">
            <div className="flex items-center gap-2">
              <VegDot veg={recipe.is_veg} />
              <p className="flex-1 truncate font-bold">{recipe.name}</p>
              <span className="text-xs font-semibold text-muted">
                {recipe.total_time} min{recipe.calories !== null && ` · ${recipe.calories} kcal`}
              </span>
            </div>
            <MatchBar percent={match.match_percent} status={match.status} />
            <p className={`text-[13px] ${match.status === 'available' ? 'font-medium text-leaf' : 'text-muted'}`}>{shortSummary(match)}</p>
            {(match.uses_expiring.length > 0 || recipe.health_tags.length > 0) && (
              <div className="flex flex-wrap gap-1.5">
                <HealthBadges tags={recipe.health_tags} />
                {match.uses_expiring.length > 0 && <Badge color="amber">{expiringLabel(match.uses_expiring)}</Badge>}
              </div>
            )}
            </div>
          </Link>
        ))}
      </div>
    </div>
  )
}
