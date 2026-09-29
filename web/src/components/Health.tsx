import { useState } from 'react'
import { api, ApiError, fmtQty, type DayNutrition, type HealthTag, type NutritionValues } from '../api'
import { tk, useI18n } from '../i18n'
import { Alert } from './ui'

const healthLabels: Record<HealthTag, { label: string; emoji: string }> = {
  high_protein: { label: tk('High protein'), emoji: '💪' },
  low_calorie: { label: tk('Low calorie'), emoji: '🥗' },
  high_fiber: { label: tk('High fiber'), emoji: '🌾' },
}

export function HealthBadges({ tags }: { tags: HealthTag[] }) {
  const { t } = useI18n()
  return (
    <>
      {tags.map((tag) => (
        <span key={tag} className="rounded-full bg-green-50 px-2 py-0.5 text-xs font-bold text-green-800">
          {healthLabels[tag].emoji} {t(healthLabels[tag].label)}
        </span>
      ))}
    </>
  )
}

const macros = [
  { key: 'protein_g', label: tk('Protein') },
  { key: 'carbs_g', label: tk('Carbs') },
  { key: 'fat_g', label: tk('Fat') },
  { key: 'fiber_g', label: tk('Fiber') },
] as const

/** Per-serving nutrition: hero calories + one-hue horizontal bars (grams). */
export function NutritionCard({
  recipeId,
  values,
  canEstimate,
  onEstimated,
}: {
  recipeId: number
  values: NutritionValues
  canEstimate: boolean
  onEstimated: () => void
}) {
  const { t } = useI18n()
  const [busy, setBusy] = useState(false)
  const [error, setError] = useState('')
  const has = values.calories !== null
  const max = Math.max(...macros.map((m) => values[m.key] ?? 0), 1)

  async function estimate() {
    setBusy(true)
    setError('')
    try {
      await api(`/recipes/${recipeId}/nutrition`, { method: 'POST' })
      onEstimated()
    } catch (e) {
      setError((e as ApiError).message)
    } finally {
      setBusy(false)
    }
  }

  return (
    <section className="card p-5">
      <div className="mb-3 flex items-baseline justify-between">
        <h2 className="font-display text-lg font-semibold">{t('Nutrition')}</h2>
        <span className="text-xs text-muted">{t('per serving · estimated')}</span>
      </div>
      {error && (
        <div className="mb-3">
          <Alert kind="error">{error}</Alert>
        </div>
      )}
      {has ? (
        <div className="grid grid-cols-[auto_1fr] items-center gap-5">
          <div className="text-center">
            <p className="text-4xl font-semibold">{fmtQty(values.calories!)}</p>
            <p className="text-xs font-semibold text-muted">kcal</p>
          </div>
          <ul className="grid grid-cols-[max-content_1fr_auto] items-center gap-x-3 gap-y-2.5" aria-label={t('Macronutrients in grams per serving')}>
            {macros.map((m) => {
              const v = values[m.key] ?? 0
              return (
                <li key={m.key} className="contents text-sm" title={t('{label}: {n} g per serving', { label: t(m.label), n: fmtQty(v) })}>
                  <span className="text-sm font-semibold text-muted">{t(m.label)}</span>
                  <span className="h-3 rounded-r bg-cream">
                    <span className="block h-full rounded-r bg-leaf" style={{ width: `${(v / max) * 100}%` }} />
                  </span>
                  <span className="text-right text-sm font-bold tabular-nums">{fmtQty(v)} g</span>
                </li>
              )
            })}
          </ul>
        </div>
      ) : (
        <p className="text-sm text-muted">{t('No nutrition estimate yet.')}</p>
      )}
      {canEstimate && (
        <button
          onClick={estimate}
          disabled={busy}
          className="mt-3 w-full rounded-2xl border border-leaf py-2.5 text-sm font-bold text-leaf disabled:opacity-60"
        >
          ✨ {busy ? t('Estimating…') : has ? t('Re-estimate with AI') : t('Estimate with AI')}
        </button>
      )}
    </section>
  )
}

const PROTEIN_GOAL = 50 // g per adult per day, a common reference value

/** Protein per person per day across the week: one-hue columns, goal reference line, hover details, table toggle. */
export function ProteinWeekChart({ days, nutrition }: { days: string[]; nutrition: Record<string, DayNutrition> }) {
  const { t, fmtDate } = useI18n()
  const [hover, setHover] = useState<string | null>(null)
  const [table, setTable] = useState(false)
  const values = days.map((d) => nutrition[d]?.protein_g ?? 0)
  const top = Math.max(PROTEIN_GOAL * 1.2, ...values)
  const H = 120
  const label = (d: string, o: Intl.DateTimeFormatOptions) => fmtDate(d, o)
  if (!values.some((v) => v > 0)) return null

  return (
    <section className="card p-5">
      <div className="mb-1 flex items-baseline justify-between">
        <h2 className="font-display text-lg font-semibold">{t('Protein per person')}</h2>
        <button onClick={() => setTable(!table)} className="text-xs font-bold text-brand">
          {table ? t('Show chart') : t('Show table')}
        </button>
      </div>
      <p className="mb-3 text-xs text-muted">{t('Grams per day from planned meals (one serving of each dish). Line = {n} g goal.', { n: PROTEIN_GOAL })}</p>

      {table ? (
        <table className="w-full text-sm">
          <thead>
            <tr className="text-left text-xs text-muted">
              <th className="py-1 font-semibold">{t('Day')}</th>
              <th className="font-semibold text-right">{t('Protein')}</th>
              <th className="font-semibold text-right">kcal</th>
            </tr>
          </thead>
          <tbody className="tabular-nums">
            {days.map((d) => (
              <tr key={d} className="border-t border-line">
                <td className="py-1.5">{label(d, { weekday: 'short', day: 'numeric' })}</td>
                <td className="text-right">{fmtQty(nutrition[d]?.protein_g ?? 0)} g</td>
                <td className="text-right">{fmtQty(nutrition[d]?.calories ?? 0)}</td>
              </tr>
            ))}
          </tbody>
        </table>
      ) : (
        <div className="relative" style={{ height: H + 36 }}>
          {/* goal reference line */}
          <div className="absolute inset-x-0 border-t border-stone-300" style={{ top: 16 + H - (PROTEIN_GOAL / top) * H }} aria-hidden />
          <span className="absolute right-0 text-[10px] font-semibold text-muted" style={{ top: 16 + H - (PROTEIN_GOAL / top) * H - 13 }}>
            {t('{n} g goal', { n: PROTEIN_GOAL })}
          </span>
          <div className="absolute inset-x-0 bottom-0 flex items-end justify-between gap-1" style={{ height: H + 36 }}>
            {days.map((d, i) => {
              const v = values[i]
              const n = nutrition[d]
              return (
                <button
                  key={d}
                  onMouseEnter={() => setHover(d)}
                  onMouseLeave={() => setHover(null)}
                  onFocus={() => setHover(d)}
                  onBlur={() => setHover(null)}
                  onClick={() => setHover(hover === d ? null : d)}
                  aria-label={`${label(d, { weekday: 'long' })}: ${t('{n} g protein', { n: fmtQty(v) })}`}
                  className="relative flex h-full flex-1 flex-col items-center justify-end"
                >
                  <span className="mb-0.5 text-[11px] font-bold tabular-nums">{v > 0 ? fmtQty(Math.round(v)) : ''}</span>
                  <span className="w-full max-w-6 rounded-t bg-leaf" style={{ height: Math.max((v / top) * H, v > 0 ? 2 : 0) }} />
                  <span className="mt-1 h-4 text-[11px] font-semibold text-muted">{label(d, { weekday: 'narrow' })}</span>
                  {hover === d && n && (
                    <span className="absolute bottom-full z-10 mb-1 w-36 rounded-xl bg-ink p-2 text-left text-[11px] leading-4 text-white shadow-lg">
                      <b>{label(d, { weekday: 'short', day: 'numeric', month: 'short' })}</b>
                      <br />
                      {t('Protein')} {fmtQty(n.protein_g)} g · {fmtQty(n.calories)} kcal
                      <br />
                      {t('Carbs')} {fmtQty(n.carbs_g)} g · {t('Fat')} {fmtQty(n.fat_g)} g
                      {n.missing > 0 && (
                        <>
                          <br />
                          {t('{n} dish(es) without estimate', { n: n.missing })}
                        </>
                      )}
                    </span>
                  )}
                </button>
              )
            })}
          </div>
        </div>
      )}
    </section>
  )
}
