import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { addDays, api, ApiError, isoDate, useApi, type CoachData, type CoachResponse } from '../api'
import { BADGES, ExtraFoodSheet, GoalForm, WeightSheet } from '../components/CoachSheets'
import Kolam from '../components/Kolam'
import RecipeCover from '../components/RecipeCover'
import { Alert, ErrorState, Spinner } from '../components/ui'
import { nm, useI18n } from '../i18n'

const SEEN_KEY = 'adupangarai.seenBadges'
const PORTIONS = [0.5, 1, 1.5, 2]
const fmtPortion = (p: number) => (p === 0.5 ? '½' : p === 1.5 ? '1½' : String(p))
const n = (v: number) => Math.round(v).toLocaleString('en-IN')

function readSeen(): string[] {
  try {
    return JSON.parse(localStorage.getItem(SEEN_KEY) ?? '[]') as string[]
  } catch {
    return []
  }
}

/** Personal health coach: calorie target, "I ate it" ticks, extras, steps, weight challenge and badges. */
export default function CoachPage() {
  const { t, fmtDate } = useI18n()
  const today = isoDate(new Date())
  const [date, setDate] = useState(today)
  const res = useApi<CoachResponse>(`/coach${date === today ? '' : `?date=${date}`}`)
  const [editing, setEditing] = useState(false)
  const [sheet, setSheet] = useState<'extra' | 'weight' | null>(null)
  const [flash, setFlash] = useState<{ kind: 'error' | 'success'; text: string } | null>(null)

  if (res.loading && !res.data) return <Spinner label={t('Opening your coach…')} />
  if (!res.data) return <ErrorState message={res.error ?? ''} onRetry={res.reload} />

  const done = (text: string) => {
    setFlash({ kind: 'success', text })
    setSheet(null)
    setEditing(false)
    res.reload()
  }

  if (!res.data.profile || editing) {
    const c = res.data.profile ? (res.data as CoachData) : null
    return (
      <div className="space-y-4">
        <header className="relative overflow-hidden pt-2">
          <Kolam classic className="pointer-events-none absolute -top-3 -right-4 size-36 text-brand opacity-[.12]" strokeWidth={0.05} />
          <h1 className="font-display text-[1.75rem] font-semibold tracking-tight">{c ? t('Edit your goal') : t('Your health coach')}</h1>
          {!c && <p className="mt-1 max-w-xs text-sm text-muted">{t('Set a goal and I’ll give you a daily calorie target, plan meals to it, cheer you on and hand out badges. 🏅')}</p>}
        </header>
        <section className="card p-5">
          <GoalForm profile={c?.profile} weight={c?.current_weight} onSaved={done} onCancel={c ? () => setEditing(false) : undefined} />
        </section>
      </div>
    )
  }

  const c = res.data as CoachData
  const act = async (fn: () => Promise<{ message: string }>) => {
    try {
      await fn()
      res.reload()
    } catch (e) {
      setFlash({ kind: 'error', text: (e as ApiError).message })
    }
  }
  const setSteps = (steps: number) => act(() => api('/coach/steps', { method: 'PUT', body: { date, steps: Math.max(0, Math.min(100000, steps)) } }))
  const ateIt = (planId: number, portion: number) => act(() => api('/coach/food', { method: 'POST', body: { date, items: [{ meal_plan_id: planId, portion }] } }))
  const unlog = (logId: number) => act(() => api(`/coach/food/${logId}`, { method: 'DELETE' }))

  const goalLine =
    c.profile.goal === 'maintain'
      ? t('Stay fit at {kg} kg', { kg: c.current_weight })
      : t(c.profile.goal === 'lose' ? 'Lose weight · {from} → {to} kg' : 'Gain weight · {from} → {to} kg', { from: c.profile.start_weight_kg, to: c.profile.target_weight_kg })

  return (
    <div className="space-y-4">
      <header className="relative overflow-hidden pt-2">
        <Kolam classic className="pointer-events-none absolute -top-3 -right-4 size-36 text-brand opacity-[.12]" strokeWidth={0.05} />
        <p className="text-sm text-muted">{goalLine}</p>
        <h1 className="font-display text-[1.75rem] font-semibold tracking-tight">{t('Coach')}</h1>
        <div className="mt-2 flex items-center gap-2">
          <button onClick={() => setDate(addDays(date, -1))} aria-label={t('Previous day')} className="grid size-8 place-items-center rounded-full border border-line bg-white">
            ‹
          </button>
          <span className="min-w-28 text-center text-sm font-semibold">{date === today ? t('Today') : fmtDate(date, { weekday: 'short', day: 'numeric', month: 'short' })}</span>
          <button onClick={() => setDate(addDays(date, 1))} disabled={date >= today} aria-label={t('Next day')} className="grid size-8 place-items-center rounded-full border border-line bg-white disabled:opacity-30">
            ›
          </button>
          <button onClick={() => setEditing(true)} className="ml-auto text-sm font-bold text-brand">
            {t('Edit goal')}
          </button>
        </div>
      </header>

      {flash && (
        <button className="block w-full text-left" onClick={() => setFlash(null)}>
          <Alert kind={flash.kind}>{flash.text}</Alert>
        </button>
      )}
      <NewBadges badges={c.badges} />

      <TodayCard c={c} />

      {c.tips.length > 0 && (
        <section className="card p-5">
          <p className="mb-2 flex items-center gap-2 font-display text-lg font-semibold">
            <span className="grid size-8 place-items-center rounded-full bg-brand/10 text-base">🧑‍🍳</span>
            {t('Your coach says')}
          </p>
          <ul className="space-y-2">
            {c.tips.map((tip) => (
              <li key={tip} className="rounded-xl bg-cream px-3 py-2 text-sm leading-relaxed">
                {tip}
              </li>
            ))}
          </ul>
        </section>
      )}

      <section className="card p-5">
        <div className="mb-2 flex items-center justify-between">
          <h2 className="font-display text-lg font-semibold">{t('What I ate')}</h2>
          <Link to="/planner" className="text-sm font-bold text-brand">{t('Planner')} →</Link>
        </div>
        {c.today.planned.length === 0 && <p className="py-1 text-sm text-muted">{t('No meals planned for this day. Add extras below, or plan with “🎯 My target”.')}</p>}
        <ul className="divide-y divide-line">
          {c.today.planned.map((m) => (
            <li key={m.id} className="py-2.5">
              <div className="flex items-center gap-3">
                <RecipeCover recipe={m.recipe} className="size-11 shrink-0 rounded-xl" />
                <span className="min-w-0 flex-1">
                  <span className="block text-xs font-semibold tracking-wide text-muted uppercase">{t(m.meal_type)}</span>
                  <span className="block truncate font-semibold">{nm(m.recipe)}</span>
                  <span className="block text-xs text-muted">{m.recipe.calories !== null ? t('{n} kcal per serving', { n: Math.round(m.recipe.calories) }) : t('Calories not estimated yet')}</span>
                </span>
                {m.log ? (
                  <button onClick={() => unlog(m.log!.id)} aria-pressed className="rounded-full bg-leaf px-3 py-1.5 text-sm font-bold text-white">
                    ✓ {t('Ate it')}
                  </button>
                ) : (
                  <button onClick={() => ateIt(m.id, 1)} className="rounded-full border border-line bg-white px-3 py-1.5 text-sm font-bold text-ink">
                    {t('Ate it?')}
                  </button>
                )}
              </div>
              {m.log && (
                <div className="mt-2 flex items-center gap-1.5 pl-14 text-xs">
                  <span className="mr-1 text-muted">{t('Portion')}</span>
                  {PORTIONS.map((p) => (
                    <button key={p} onClick={() => ateIt(m.id, p)} aria-pressed={m.log!.portion === p} className={`rounded-full px-2.5 py-1 font-bold ${m.log!.portion === p ? 'bg-ink text-white' : 'bg-cream text-muted'}`}>
                      {fmtPortion(p)}
                    </button>
                  ))}
                  <span className="ml-auto font-semibold">{n(m.log.calories)} kcal</span>
                </div>
              )}
            </li>
          ))}
          {c.today.extras.map((x) => (
            <li key={x.id} className="flex items-center gap-3 py-2.5">
              <span className="grid size-11 shrink-0 place-items-center rounded-xl bg-amber-50 text-xl">🍪</span>
              <span className="min-w-0 flex-1">
                <span className="block text-xs font-semibold tracking-wide text-muted uppercase">{t('Extra')}</span>
                <span className="block truncate font-semibold">{x.name}</span>
              </span>
              <span className="text-sm font-semibold">{n(x.calories)} kcal</span>
              <button onClick={() => unlog(x.id)} aria-label={t('Remove')} className="px-1 text-xl text-muted">
                ×
              </button>
            </li>
          ))}
        </ul>
        <button onClick={() => setSheet('extra')} className="mt-3 w-full rounded-xl border border-dashed border-brand/50 py-2.5 text-sm font-bold text-brand">
          + {t('Ate something extra?')}
        </button>
      </section>

      <StepsCard steps={c.today.steps} goal={c.profile.step_goal} streak={c.streaks.steps} onSet={setSteps} />
      <WeekCard c={c} />
      <ChallengeCard c={c} onLogWeight={() => setSheet('weight')} />

      <section className="card p-5">
        <h2 className="mb-3 font-display text-lg font-semibold">{t('Badges')}</h2>
        <ul className="grid grid-cols-3 gap-2">
          {c.badges.map((b) => {
            const meta = BADGES[b.key]
            if (!meta) return null
            return (
              <li key={b.key} title={t(meta.hint)} className={`flex flex-col items-center rounded-2xl border p-2.5 text-center ${b.earned_on ? 'border-accent/40 bg-amber-50/60' : 'border-line bg-white'}`}>
                <span className={`text-3xl ${b.earned_on ? '' : 'opacity-25 grayscale'}`}>{meta.icon}</span>
                <span className={`mt-1 text-xs font-bold ${b.earned_on ? 'text-ink' : 'text-muted'}`}>{t(meta.title)}</span>
                <span className="mt-0.5 text-[10.5px] leading-tight text-muted">{b.earned_on ? fmtDate(b.earned_on, { day: 'numeric', month: 'short' }) : t(meta.hint)}</span>
              </li>
            )
          })}
        </ul>
      </section>

      <p className="px-1 text-xs text-muted">{t('General guidance, not medical advice. If you are pregnant, diabetic or have a health condition, please check with your doctor first.')}</p>

      {sheet === 'extra' && <ExtraFoodSheet date={date} quick={c.quick_foods} onClose={() => setSheet(null)} onDone={done} />}
      {sheet === 'weight' && <WeightSheet current={c.current_weight} onClose={() => setSheet(null)} onDone={done} />}
    </div>
  )
}

/** Big ring: eaten vs target, plus protein. */
function TodayCard({ c }: { c: CoachData }) {
  const { t } = useI18n()
  const eaten = c.today.calories
  const target = c.target.calories
  const left = target - eaten
  const share = Math.min(eaten / target, 1)
  const over = eaten > target * 1.05 && c.profile.goal !== 'gain'
  const R = 52
  const C = 2 * Math.PI * R
  const proteinShare = Math.min(c.today.protein_g / c.target.protein_g, 1)

  return (
    <section className="card p-5">
      <div className="flex items-center gap-5">
        <svg viewBox="0 0 128 128" className="size-32 shrink-0 -rotate-90" aria-hidden>
          <circle cx="64" cy="64" r={R} fill="none" stroke="var(--color-line)" strokeWidth="11" />
          <circle cx="64" cy="64" r={R} fill="none" stroke={over ? 'var(--color-accent)' : 'var(--color-leaf)'} strokeWidth="11" strokeLinecap="round" strokeDasharray={`${C * share} ${C}`} className="transition-all duration-700" />
        </svg>
        <div className="min-w-0">
          <p className="text-3xl font-semibold tracking-tight">{n(eaten)}</p>
          <p className="text-sm text-muted">{t('of {n} kcal', { n: n(target) })}</p>
          <p className={`mt-1 text-sm font-bold ${over ? 'text-accent' : 'text-leaf'}`}>
            {left >= 0 ? t('{n} kcal left', { n: n(left) }) : t('{n} kcal over', { n: n(-left) })}
          </p>
          <div className="mt-2 flex flex-wrap gap-1.5 text-xs font-bold">
            {c.streaks.on_target > 0 && <span className="rounded-full bg-amber-50 px-2 py-0.5 text-amber-800">🔥 {t('{n}-day streak', { n: c.streaks.on_target })}</span>}
            {c.target.floored && <span className="rounded-full bg-stone-100 px-2 py-0.5 text-stone-700">{t('Safe minimum')}</span>}
          </div>
        </div>
      </div>
      <div className="mt-4">
        <div className="mb-1 flex justify-between text-xs font-semibold">
          <span>{t('Protein')}</span>
          <span className="text-muted">
            {Math.round(c.today.protein_g)} / {c.target.protein_g} g
          </span>
        </div>
        <div className="h-2 overflow-hidden rounded-full bg-line">
          <div className="h-full rounded-full bg-brand transition-all duration-700" style={{ width: `${proteinShare * 100}%` }} />
        </div>
      </div>
    </section>
  )
}

function StepsCard({ steps, goal, streak, onSet }: { steps: number; goal: number; streak: number; onSet: (s: number) => void }) {
  const { t } = useI18n()
  const [typed, setTyped] = useState('')
  const share = Math.min(steps / goal, 1)
  return (
    <section className="card p-5">
      <div className="mb-2 flex items-baseline justify-between">
        <h2 className="font-display text-lg font-semibold">👟 {t('Steps')}</h2>
        {streak > 0 && <span className="text-xs font-bold text-leaf">{t('{n} days in a row', { n: streak })}</span>}
      </div>
      <p className="text-3xl font-semibold tracking-tight">
        {n(steps)} <span className="text-base font-medium text-muted">/ {n(goal)}</span>
      </p>
      <div className="mt-2 h-2.5 overflow-hidden rounded-full bg-line">
        <div className={`h-full rounded-full transition-all duration-700 ${share >= 1 ? 'bg-leaf' : 'bg-accent'}`} style={{ width: `${share * 100}%` }} />
      </div>
      <div className="mt-3 flex flex-wrap items-center gap-2">
        {[1000, 2000, 5000].map((s) => (
          <button key={s} onClick={() => onSet(steps + s)} className="rounded-full border border-line bg-white px-3 py-1.5 text-sm font-bold">
            +{s / 1000}k
          </button>
        ))}
        <form
          onSubmit={(e) => {
            e.preventDefault()
            if (typed !== '') onSet(Number(typed))
            setTyped('')
          }}
          className="ml-auto flex items-center gap-1"
        >
          <input value={typed} onChange={(e) => setTyped(e.target.value.replace(/\D/g, ''))} inputMode="numeric" placeholder={t('Total')} aria-label={t('Total steps today')} className="w-20 rounded-xl border border-line bg-white px-2 py-1.5 text-sm" />
          <button className="rounded-xl bg-ink px-3 py-1.5 text-sm font-bold text-white">{t('Set')}</button>
        </form>
      </div>
      <p className="mt-2 text-xs text-muted">{t('Copy the count from your phone’s step counter (Google Fit, Samsung Health…).')}</p>
    </section>
  )
}

/** Last 7 days: calories against the target line; green = on target. */
function WeekCard({ c }: { c: CoachData }) {
  const { t, fmtDate } = useI18n()
  const max = Math.max(c.target.calories * 1.3, ...c.week.map((d) => d.calories))
  const H = 90
  return (
    <section className="card p-5">
      <h2 className="mb-3 font-display text-lg font-semibold">{t('Last 7 days')}</h2>
      <div className="relative">
        <div className="absolute inset-x-0 border-t border-dashed border-muted/50" style={{ bottom: `${(c.target.calories / max) * H + 18}px` }} aria-hidden />
        <ul className="flex items-end justify-between gap-1.5">
          {c.week.map((d) => (
            <li key={d.date} className="flex flex-1 flex-col items-center gap-1">
              <div className="flex w-full items-end justify-center" style={{ height: H }}>
                <div
                  className={`w-full max-w-7 rounded-t-md ${!d.logged ? 'bg-line' : d.on_target ? 'bg-leaf' : 'bg-accent'}`}
                  style={{ height: `${d.logged ? Math.max(4, (d.calories / max) * H) : 4}px` }}
                  title={`${n(d.calories)} kcal`}
                />
              </div>
              <span className="text-[10.5px] text-muted">{fmtDate(d.date, { weekday: 'narrow' })}</span>
            </li>
          ))}
        </ul>
      </div>
      <p className="mt-2 flex flex-wrap gap-x-3 text-xs text-muted">
        <span><span className="mr-1 inline-block size-2 rounded-full bg-leaf" />{t('On target')}</span>
        <span><span className="mr-1 inline-block size-2 rounded-full bg-accent" />{t('Off target')}</span>
        <span>┄ {t('{n} kcal target', { n: n(c.target.calories) })}</span>
      </p>
    </section>
  )
}

function ChallengeCard({ c, onLogWeight }: { c: CoachData; onLogWeight: () => void }) {
  const { t } = useI18n()
  const p = c.progress
  const ws = c.weights
  const lo = Math.min(...ws.map((w) => w.weight_kg), p?.target ?? Infinity)
  const hi = Math.max(...ws.map((w) => w.weight_kg), p?.target ?? -Infinity)
  const span = Math.max(hi - lo, 1)
  const pts = ws.map((w, i) => `${ws.length === 1 ? 150 : (i / (ws.length - 1)) * 300},${8 + (1 - (w.weight_kg - lo) / span) * 64}`).join(' ')

  return (
    <section className="card p-5">
      <div className="mb-2 flex items-center justify-between">
        <h2 className="font-display text-lg font-semibold">{p ? t('Weight challenge') : t('Weight')}</h2>
        <button onClick={onLogWeight} className="text-sm font-bold text-brand">
          + {t('Log weight')}
        </button>
      </div>
      <div className="grid grid-cols-3 gap-2 text-center">
        {(p
          ? [
              [t('Start'), p.start],
              [t('Now'), p.current],
              [t('Target'), p.target],
            ]
          : [
              [t('Now'), c.current_weight],
              ['BMI', c.bmi],
              [t('Healthy'), `${c.healthy_weight[0]}–${c.healthy_weight[1]}`],
            ]
        ).map(([label, value]) => (
          <div key={String(label)} className="rounded-xl bg-cream py-2">
            <p className="text-lg font-semibold">{value}</p>
            <p className="text-xs text-muted">{label}</p>
          </div>
        ))}
      </div>
      {p && (
        <>
          <div className="mt-3 h-2.5 overflow-hidden rounded-full bg-line">
            <div className="h-full rounded-full bg-leaf transition-all duration-700" style={{ width: `${p.percent}%` }} />
          </div>
          <p className="mt-1.5 text-sm">
            <span className="font-bold text-leaf">{t('{kg} kg done · {p}%', { kg: p.done_kg, p: p.percent })}</span>
            {p.percent < 100 && <span className="text-muted"> · {t('about {n} weeks to go', { n: p.weeks_left })}</span>}
          </p>
        </>
      )}
      {ws.length > 1 && (
        <svg viewBox="-4 0 308 80" className="mt-3 w-full" role="img" aria-label={t('Weight trend')}>
          {p && <line x1="0" x2="300" y1={8 + (1 - (p.target - lo) / span) * 64} y2={8 + (1 - (p.target - lo) / span) * 64} stroke="var(--color-leaf)" strokeDasharray="4 4" strokeWidth="1" />}
          <polyline points={pts} fill="none" stroke="var(--color-brand)" strokeWidth="2.5" strokeLinejoin="round" strokeLinecap="round" />
        </svg>
      )}
    </section>
  )
}

/** A one-time celebration for badges earned since the last visit (remembered on this device). */
function NewBadges({ badges }: { badges: CoachData['badges'] }) {
  const { t } = useI18n()
  const earned = badges.filter((b) => b.earned_on).map((b) => b.key)
  const [seen, setSeen] = useState(readSeen)
  const fresh = earned.filter((k) => !seen.includes(k) && BADGES[k])

  useEffect(() => {
    if (fresh.length === 0) return
    const timer = setTimeout(() => {
      try {
        localStorage.setItem(SEEN_KEY, JSON.stringify([...new Set([...seen, ...earned])]))
      } catch {
        /* private mode: the celebration may show again */
      }
    }, 800)
    return () => clearTimeout(timer)
  }, [fresh.length, seen, earned])

  if (fresh.length === 0) return null
  return (
    <button onClick={() => setSeen([...seen, ...fresh])} className="block w-full rounded-[1.25rem] bg-brand p-4 text-left text-white">
      <p className="text-sm font-semibold opacity-90">🎉 {t('New badge!')}</p>
      <p className="mt-1 flex flex-wrap gap-2 text-lg font-semibold">
        {fresh.map((k) => (
          <span key={k}>
            {BADGES[k].icon} {t(BADGES[k].title)}
          </span>
        ))}
      </p>
      <p className="mt-1 text-xs opacity-80">{t('Keep going — tap to close')}</p>
    </button>
  )
}
