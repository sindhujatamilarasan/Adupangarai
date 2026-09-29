import { useState } from 'react'
import { Link } from 'react-router-dom'
import { fmtQty, useApi, type CookResult } from '../api'
import { HealthBadges } from '../components/Health'
import RecipeCover from '../components/RecipeCover'
import { Badge, EmptyState, ErrorState, Spinner } from '../components/ui'
import { nm, tk, translate, unitLabel, useI18n, type Lang } from '../i18n'
import { VegDot } from './RecipesPage'

const availability = [
  { key: 'all', label: tk('All') },
  { key: 'available', label: tk('Available now') },
  { key: 'almost', label: tk('Almost') },
  { key: 'use_soon', label: tk('Use soon') },
]
const extras = [
  { key: '', label: tk('Any meal') },
  { key: 'meal_type=breakfast', label: tk('Breakfast') },
  { key: 'meal_type=lunch', label: tk('Lunch') },
  { key: 'meal_type=dinner', label: tk('Dinner') },
  { key: 'max_time=30', label: tk('Under 30 min') },
  { key: 'health=high_protein', label: tk('💪 High protein') },
  { key: 'health=low_calorie', label: tk('🥗 Low calorie') },
]

const empty: Record<string, [string, string, string]> = {
  all: ['📖', tk('No recipes match'), tk('Try a different meal or time filter.')],
  available: ['🧺', tk('Nothing fully ready yet'), tk('Check “Almost” — you may be just an ingredient or two away.')],
  almost: ['🛒', tk('No near misses'), tk('Add more to your kitchen to unlock recipes.')],
  use_soon: ['🌿', tk('Nothing needs using up'), tk('Recipes using items that expire within 3 days show up here.')],
}

function Chips({ items, value, onChange }: { items: { key: string; label: string }[]; value: string; onChange: (k: string) => void }) {
  const { t } = useI18n()
  return (
    <div className="-mx-4 flex gap-2 overflow-x-auto px-4 pb-1">
      {items.map((f) => (
        <button
          key={f.key}
          onClick={() => onChange(f.key)}
          className={`shrink-0 rounded-full px-4 py-2 text-sm font-bold ${value === f.key ? 'border border-ink bg-ink text-white' : 'border border-line bg-white text-muted'}`}
        >
          {t(f.label)}
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
function expiringLabel(lang: Lang, items: CookResult['match']['uses_expiring']) {
  const t = (k: string, v?: Record<string, string | number>) => translate(lang, k, v)
  const [first, ...rest] = [...items].sort((a, b) => a.days_to_expiry - b.days_to_expiry)
  const when = first.days_to_expiry === 0 ? t('today') : first.days_to_expiry === 1 ? t('tomorrow') : t('{n}d', { n: first.days_to_expiry })
  return t('Use soon: {name} ({when})', { name: first.name, when }) + (rest.length ? ` +${rest.length}` : '')
}

function shortSummary(lang: Lang, m: CookResult['match']) {
  const t = (k: string, v?: Record<string, string | number>) => translate(lang, k, v)
  const rows = [...m.insufficient, ...m.missing]
  if (rows.length === 0) return t('You have everything')
  const parts = rows.slice(0, 3).map((r) => `${r.name} ${fmtQty(r.short ?? 0)} ${unitLabel(lang, r.unit)}`)
  return t('Need {items}', { items: parts.join(', ') }) + (rows.length > 3 ? ` ${t('+{n} more', { n: rows.length - 3 })}` : '')
}

export default function CookPage() {
  const { t, lang } = useI18n()
  const [filter, setFilter] = useState('all')
  const [extra, setExtra] = useState('')
  const res = useApi<{ data: CookResult[]; pantry_count: number }>(`/cook?filter=${filter}${extra ? `&${extra}` : ''}`)

  return (
    <div>
      <div className="flex flex-wrap items-center justify-between gap-x-3 gap-y-2">
        <h1 className="font-display text-[1.75rem] font-semibold tracking-tight">{t('What can I cook?')}</h1>
        <Link to="/recipes" className="text-sm font-bold text-brand">
          {t('All recipes')} →
        </Link>
      </div>
      <div className="mt-4 space-y-2">
        <Chips items={availability} value={filter} onChange={setFilter} />
        <Chips items={extras} value={extra} onChange={setExtra} />
      </div>

      <div className="mt-4 space-y-2">
        {res.loading && !res.data && <Spinner label={t('Checking your kitchen…')} />}
        {res.error && <ErrorState message={res.error} onRetry={res.reload} />}
        {res.data?.pantry_count === 0 && (
          <div className="rounded-2xl bg-amber-50 p-4 text-sm text-amber-900">
            {t('Your kitchen is empty, so every recipe shows 0%.')}{' '}
            <Link to="/kitchen" className="font-bold underline">
              {t('Add what you have')}
            </Link>
          </div>
        )}
        {res.data?.data.length === 0 && (
          <EmptyState emoji={empty[filter][0]} title={t(empty[filter][1])}>
            {t(empty[filter][2])}
          </EmptyState>
        )}
        {res.data?.data.map(({ recipe, match }) => (
          <Link key={recipe.id} to={`/recipes/${recipe.id}`} className="flex gap-3 card p-3 active:scale-[.99]">
            <RecipeCover recipe={{ ...recipe, meal_type: recipe.meal_type }} className="size-20 shrink-0 rounded-2xl" />
            <div className="min-w-0 flex-1 space-y-2">
            <div className="flex items-center gap-2">
              <VegDot veg={recipe.is_veg} />
              <p className="flex-1 truncate font-semibold">{nm(recipe)}</p>
              <span className="text-xs font-semibold text-muted">
                {t('{n} min', { n: recipe.total_time })}{recipe.calories !== null && ` · ${recipe.calories} kcal`}
              </span>
            </div>
            <MatchBar percent={match.match_percent} status={match.status} />
            <p className={`text-[13px] ${match.status === 'available' ? 'font-medium text-leaf' : 'text-muted'}`}>{shortSummary(lang, match)}</p>
            {(match.uses_expiring.length > 0 || recipe.health_tags.length > 0) && (
              <div className="flex flex-wrap gap-1.5">
                <HealthBadges tags={recipe.health_tags} />
                {match.uses_expiring.length > 0 && <Badge color="amber">{expiringLabel(lang, match.uses_expiring)}</Badge>}
              </div>
            )}
            </div>
          </Link>
        ))}
      </div>
    </div>
  )
}
