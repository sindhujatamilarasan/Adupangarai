import { Link, useSearchParams } from 'react-router-dom'
import { addDays, fmtQty, MEAL_TYPES, useApi, type DayNutrition, type MealPlanEntry } from '../api'
import { useAuth } from '../auth'
import Kolam from '../components/Kolam'
import { ErrorState, Spinner } from '../components/ui'
import { nm, useI18n } from '../i18n'

type PlanResponse = { data: MealPlanEntry[]; start: string; end: string; nutrition: Record<string, DayNutrition> }

const PROTEIN_GOAL = 50 // g per adult per day (reference value, same as the planner chart)
const CALORIE_GUIDE = 2000 // kcal per adult per day (general reference)

/** A4 landscape weekly plan to print or save as PDF (uses the browser's print engine, so Tamil renders correctly). */
export default function PrintPlanPage() {
  const [params] = useSearchParams()
  const start = params.get('start')
  const res = useApi<PlanResponse>(`/meal-plans${start ? `?start=${start}` : ''}`)
  const { user } = useAuth()
  const { t, fmtDate } = useI18n()

  if (res.loading && !res.data) return <Spinner label={t('Preparing your plan…')} />
  if (!res.data) return <ErrorState message={res.error ?? t('Could not load the plan.')} onRetry={res.reload} />

  const { data: plans, nutrition } = res.data
  const days = Array.from({ length: 7 }, (_, i) => addDays(res.data!.start, i))
  const cell = (date: string, meal: string) => plans.filter((p) => p.date === date && p.meal_type === meal)
  const planned = plans.length
  const range = `${fmtDate(res.data.start, { day: 'numeric', month: 'long' })} – ${fmtDate(res.data.end, { day: 'numeric', month: 'long', year: 'numeric' })}`

  return (
    <div className="min-h-svh bg-stone-200/60 py-6 print:bg-white print:py-0">
      {/* Screen-only toolbar */}
      <div className="mx-auto mb-4 flex max-w-[297mm] flex-wrap items-center justify-between gap-3 px-4 print:hidden">
        <Link to="/planner" className="text-sm font-semibold text-muted">
          ← {t('Back to planner')}
        </Link>
        <div className="flex flex-wrap items-center gap-2">
          <span className="text-xs text-muted">{t('A4 landscape · In the print window choose “Save as PDF” to download.')}</span>
          <button onClick={() => window.print()} className="rounded-full border border-line bg-white px-4 py-2 text-sm font-semibold text-brand">
            🖨️ {t('Print')}
          </button>
          <button onClick={() => window.print()} className="rounded-full bg-brand px-4 py-2 text-sm font-semibold text-white">
            ⬇ {t('Save as PDF')}
          </button>
        </div>
      </div>

      {/* The A4 sheet */}
      <article className="a4-sheet mx-auto flex flex-col bg-white text-ink shadow-xl print:shadow-none">
        <header className="flex items-center justify-between border-b border-line pb-3">
          <div className="flex items-center gap-3">
            <img src="/logo.svg" alt="" className="size-12 rounded-xl" />
            <div>
              <p className="font-display text-2xl leading-none font-semibold">{t('Weekly meal plan')}</p>
              <p className="mt-1 text-sm text-muted">
                {user?.household.name} · {range}
              </p>
            </div>
          </div>
          <div className="flex items-center gap-3 text-right">
            <div>
              <p className="font-display text-lg leading-none font-semibold">Adupangarai</p>
              <p className="mt-0.5 font-tamil text-xs text-muted">அடுப்பங்கரை</p>
            </div>
            <Kolam classic className="size-12 text-brand" strokeWidth={0.06} />
          </div>
        </header>

        {/* Days as columns, meals as rows */}
        <table className="mt-3 w-full table-fixed border-collapse text-[10.5pt]">
          <thead>
            <tr>
              <th className="w-[13%]" />
              {days.map((d) => (
                <th key={d} className="border-b-2 border-brand px-1.5 pb-1.5 text-left align-bottom">
                  <span className="block font-display text-[12pt] font-semibold">{fmtDate(d, { weekday: 'long' })}</span>
                  <span className="block text-[8.5pt] font-medium text-muted">{fmtDate(d, { day: 'numeric', month: 'short' })}</span>
                </th>
              ))}
            </tr>
          </thead>
          <tbody>
            {MEAL_TYPES.map((meal, row) => (
              <tr key={meal} className={row % 2 ? 'bg-cream/70' : ''}>
                <th className="border-b border-line px-1.5 py-2 text-left align-top text-[9pt] font-semibold tracking-wide text-brand uppercase">
                  {t(meal)}
                </th>
                {days.map((d) => (
                  <td key={d} className="h-[21mm] border-b border-l border-line px-1.5 py-1.5 align-top">
                    {cell(d, meal).map((p) => (
                      <p key={p.id} className="mb-1 leading-tight last:mb-0">
                        <span className="font-semibold">{nm(p.recipe)}</span>
                        <span className="ml-1 text-[8pt] text-muted">×{p.servings}</span>
                        {p.cooked_at && <span className="ml-1 text-leaf">✓</span>}
                      </p>
                    ))}
                  </td>
                ))}
              </tr>
            ))}
          </tbody>
        </table>

        {/* Charts */}
        <section className="mt-4 grid flex-1 grid-cols-2 gap-6">
          <DayBars
            title={t('Protein per person')}
            unit="g"
            days={days}
            values={days.map((d) => nutrition[d]?.protein_g ?? 0)}
            reference={PROTEIN_GOAL}
            referenceLabel={t('{n} g goal', { n: PROTEIN_GOAL })}
            color="var(--color-leaf)"
            fmtDay={(d) => fmtDate(d, { weekday: 'short' })}
          />
          <DayBars
            title={t('Calories per person')}
            unit="kcal"
            days={days}
            values={days.map((d) => nutrition[d]?.calories ?? 0)}
            reference={CALORIE_GUIDE}
            referenceLabel={t('{n} kcal guide', { n: CALORIE_GUIDE })}
            color="var(--color-brand)"
            fmtDay={(d) => fmtDate(d, { weekday: 'short' })}
          />
        </section>

        <footer className="mt-3 flex items-center justify-between border-t border-line pt-2 text-[8pt] text-muted">
          <span>{t('{n} meals planned · per-person values assume one serving of each dish · nutrition is estimated', { n: planned })}</span>
          <span>{t('Printed from Adupangarai')}</span>
        </footer>
      </article>
    </div>
  )
}

/** Static single-hue column chart for print: value on each column, one reference line, no interaction needed on paper. */
function DayBars(props: {
  title: string
  unit: string
  days: string[]
  values: number[]
  reference: number
  referenceLabel: string
  color: string
  fmtDay: (d: string) => string
}) {
  const { title, unit, days, values, reference, referenceLabel, color, fmtDay } = props
  const W = 440
  const H = 150
  const top = 16
  const bottom = 20
  const plotH = H - top - bottom
  const max = Math.max(reference * 1.15, ...values) || 1
  const y = (v: number) => top + plotH - (v / max) * plotH
  const band = W / days.length
  const barW = Math.min(24, band * 0.5)

  return (
    <figure className="flex flex-col">
      <figcaption className="mb-1 flex items-baseline justify-between">
        <span className="font-display text-[12pt] font-semibold">{title}</span>
        <span className="text-[8pt] text-muted">{unit}</span>
      </figcaption>
      <svg viewBox={`0 0 ${W} ${H}`} className="w-full" role="img" aria-label={title}>
        <line x1="0" x2={W} y1={top + plotH} y2={top + plotH} stroke="var(--color-line)" strokeWidth="1" />
        <line x1="0" x2={W} y1={y(reference)} y2={y(reference)} stroke="#a8a29e" strokeWidth="1" />
        <text x={W} y={y(reference) - 4} textAnchor="end" fontSize="9" fill="var(--color-muted)">
          {referenceLabel}
        </text>
        {values.map((v, i) => {
          const cx = band * i + band / 2
          const h = top + plotH - y(v)
          return (
            <g key={days[i]}>
              {v > 0 && (
                <>
                  <path
                    d={`M${cx - barW / 2} ${top + plotH} V${y(v) + 4} Q${cx - barW / 2} ${y(v)} ${cx - barW / 2 + 4} ${y(v)} H${cx + barW / 2 - 4} Q${cx + barW / 2} ${y(v)} ${cx + barW / 2} ${y(v) + 4} V${top + plotH} Z`}
                    fill={color}
                    opacity={h > 0 ? 1 : 0}
                  />
                  <text x={cx} y={y(v) - 4} textAnchor="middle" fontSize="10" fontWeight="600" fill="var(--color-ink)">
                    {fmtQty(Math.round(v))}
                  </text>
                </>
              )}
              <text x={cx} y={H - 5} textAnchor="middle" fontSize="10" fill="var(--color-muted)">
                {fmtDay(days[i])}
              </text>
            </g>
          )
        })}
      </svg>
    </figure>
  )
}
