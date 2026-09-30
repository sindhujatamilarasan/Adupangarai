import { useState, type FormEvent } from 'react'
import { AI_WAIT, api, ApiError, type Activity, type CoachGoal, type HealthProfile, type QuickFood } from '../api'
import { tk, useI18n } from '../i18n'
import VoiceInput from './VoiceInput'
import { Alert, Button, Field, Sheet, Spinner } from './ui'

/** Badge key (from the API) -> how it looks. What earns it is decided on the server. */
// oxlint-disable-next-line react/only-export-components
export const BADGES: Record<string, { icon: string; title: string; hint: string }> = {
  started: { icon: '🌱', title: tk('Started'), hint: tk('Set your goal') },
  first_log: { icon: '📝', title: tk('First log'), hint: tk('Log your first meal') },
  plan_follower: { icon: '🍽️', title: tk('Plan follower'), hint: tk('Eat every planned meal in a day, on target') },
  plan_streak_3: { icon: '📅', title: tk('Plan pro'), hint: tk('Follow the plan 3 days in a row') },
  on_target_3: { icon: '🎯', title: tk('On target'), hint: tk('3 days in a row on target') },
  on_target_7: { icon: '🔥', title: tk('Week warrior'), hint: tk('7 days in a row on target') },
  on_target_21: { icon: '👑', title: tk('Habit hero'), hint: tk('21 days in a row on target') },
  step_goal: { icon: '👟', title: tk('Step goal'), hint: tk('Reach your step goal') },
  step_streak_7: { icon: '🏃', title: tk('Walker'), hint: tk('Step goal 7 days in a row') },
  steps_100k: { icon: '🗺️', title: tk('100K club'), hint: tk('100,000 steps in total') },
  protein_5: { icon: '💪', title: tk('Protein pro'), hint: tk('Hit your protein target on 5 days') },
  honest_5: { icon: '🤝', title: tk('Honest eater'), hint: tk('Log extra food 5 times') },
  first_kg: { icon: '⚖️', title: tk('First kg'), hint: tk('1 kg towards your goal') },
  halfway: { icon: '⛰️', title: tk('Halfway'), hint: tk('Halfway to your target weight') },
  goal_reached: { icon: '🏆', title: tk('Goal reached'), hint: tk('Reach your target weight') },
}

const goals: { key: CoachGoal; icon: string; label: string }[] = [
  { key: 'lose', icon: '🔻', label: tk('Lose weight') },
  { key: 'maintain', icon: '⚖️', label: tk('Stay fit') },
  { key: 'gain', icon: '🔺', label: tk('Gain weight') },
]
const activities: { key: Activity; label: string; hint: string }[] = [
  { key: 'sedentary', label: tk('Mostly sitting'), hint: tk('Desk work, little walking') },
  { key: 'light', label: tk('Lightly active'), hint: tk('Housework, walks 1–3 days a week') },
  { key: 'moderate', label: tk('Active'), hint: tk('Exercise 3–5 days a week') },
  { key: 'active', label: tk('Very active'), hint: tk('Hard exercise or physical work most days') },
]
const paces: Record<'lose' | 'gain', { kg: number; label: string }[]> = {
  lose: [
    { kg: 0.25, label: tk('Gentle') },
    { kg: 0.5, label: tk('Steady') },
    { kg: 0.75, label: tk('Faster') },
  ],
  gain: [
    { kg: 0.25, label: tk('Gentle') },
    { kg: 0.5, label: tk('Steady') },
  ],
}
const STEP_GOALS = [5000, 7500, 10000, 12000]

const chip = (active: boolean) => `rounded-xl border px-3 py-2.5 text-sm font-semibold ${active ? 'border-brand bg-brand/5 text-brand' : 'border-line bg-white text-muted'}`

/** Set or change the goal. The server works out the calorie target and checks it is safe. */
export function GoalForm({ profile, weight, onSaved, onCancel }: { profile?: HealthProfile; weight?: number; onSaved: (message: string) => void; onCancel?: () => void }) {
  const { t } = useI18n()
  const [f, setF] = useState({
    sex: profile?.sex ?? ('female' as HealthProfile['sex']),
    birth_year: String(profile?.birth_year ?? ''),
    height_cm: String(profile?.height_cm ?? ''),
    weight_kg: String(weight ?? ''),
    activity: profile?.activity ?? ('light' as Activity),
    goal: profile?.goal ?? ('lose' as CoachGoal),
    target_weight_kg: profile && profile.goal !== 'maintain' ? String(profile.target_weight_kg) : '',
    pace_kg: profile?.pace_kg || 0.5,
    step_goal: profile?.step_goal ?? 10000,
  })
  const [errors, setErrors] = useState<Record<string, string[]>>({})
  const [error, setError] = useState('')
  const [busy, setBusy] = useState(false)
  const set = (patch: Partial<typeof f>) => setF({ ...f, ...patch })
  const heightM = Number(f.height_cm) / 100
  const healthy = heightM > 1 ? [Math.round(18.5 * heightM ** 2), Math.round(24.9 * heightM ** 2)] : null

  async function submit(e: FormEvent) {
    e.preventDefault()
    setBusy(true)
    setErrors({})
    setError('')
    try {
      const r = await api<{ message: string }>('/coach/profile', {
        method: 'PUT',
        body: {
          ...f,
          birth_year: Number(f.birth_year),
          height_cm: Number(f.height_cm),
          weight_kg: Number(f.weight_kg),
          target_weight_kg: f.goal === 'maintain' ? null : Number(f.target_weight_kg),
          pace_kg: f.goal === 'maintain' ? null : f.pace_kg,
        },
      })
      onSaved(r.message)
    } catch (err) {
      const e = err as ApiError
      setErrors(e.errors ?? {})
      setError(e.message)
      setBusy(false)
    }
  }

  return (
    <form onSubmit={submit} className="space-y-5" noValidate>
      {error && <Alert kind="error">{error}</Alert>}
      <div>
        <p className="mb-1.5 text-sm font-semibold text-muted">{t('Your goal')}</p>
        <div className="grid grid-cols-3 gap-2">
          {goals.map((g) => (
            <button type="button" key={g.key} onClick={() => set({ goal: g.key, pace_kg: g.key === 'gain' && f.pace_kg > 0.5 ? 0.5 : f.pace_kg })} aria-pressed={f.goal === g.key} className={`${chip(f.goal === g.key)} flex flex-col items-center gap-1 py-3`}>
              <span className="text-xl">{g.icon}</span>
              {t(g.label)}
            </button>
          ))}
        </div>
      </div>

      <div className="grid grid-cols-2 gap-2">
        {(['female', 'male'] as const).map((s) => (
          <button type="button" key={s} onClick={() => set({ sex: s })} aria-pressed={f.sex === s} className={chip(f.sex === s)}>
            {s === 'female' ? t('Woman') : t('Man')}
          </button>
        ))}
      </div>

      <div className="grid grid-cols-3 gap-2">
        <Field label={t('Birth year')} inputMode="numeric" placeholder="1995" value={f.birth_year} onChange={(e) => set({ birth_year: e.target.value })} error={errors.birth_year?.[0]} />
        <Field label={t('Height (cm)')} inputMode="decimal" placeholder="160" value={f.height_cm} onChange={(e) => set({ height_cm: e.target.value })} error={errors.height_cm?.[0]} />
        <Field label={t('Weight (kg)')} inputMode="decimal" placeholder="65" value={f.weight_kg} onChange={(e) => set({ weight_kg: e.target.value })} error={errors.weight_kg?.[0]} />
      </div>

      {f.goal !== 'maintain' && (
        <div className="space-y-3">
          <Field label={t('Target weight (kg)')} inputMode="decimal" value={f.target_weight_kg} onChange={(e) => set({ target_weight_kg: e.target.value })} error={errors.target_weight_kg?.[0]} />
          {healthy && <p className="-mt-2 text-xs text-muted">{t('A healthy weight for your height is about {from}–{to} kg.', { from: healthy[0], to: healthy[1] })}</p>}
          <div>
            <p className="mb-1.5 text-sm font-semibold text-muted">{t('Pace')}</p>
            <div className="grid grid-cols-3 gap-2">
              {paces[f.goal].map((p) => (
                <button type="button" key={p.kg} onClick={() => set({ pace_kg: p.kg })} aria-pressed={f.pace_kg === p.kg} className={`${chip(f.pace_kg === p.kg)} flex flex-col items-center`}>
                  {t(p.label)}
                  <span className="text-xs font-medium">{t('{kg} kg / week', { kg: p.kg })}</span>
                </button>
              ))}
            </div>
          </div>
        </div>
      )}

      <div>
        <p className="mb-1.5 text-sm font-semibold text-muted">{t('How active are you?')}</p>
        <div className="space-y-2">
          {activities.map((a) => (
            <button type="button" key={a.key} onClick={() => set({ activity: a.key })} aria-pressed={f.activity === a.key} className={`${chip(f.activity === a.key)} block w-full text-left`}>
              {t(a.label)} <span className="font-normal text-muted">· {t(a.hint)}</span>
            </button>
          ))}
        </div>
      </div>

      <div>
        <p className="mb-1.5 text-sm font-semibold text-muted">{t('Daily step goal')}</p>
        <div className="grid grid-cols-4 gap-2">
          {STEP_GOALS.map((s) => (
            <button type="button" key={s} onClick={() => set({ step_goal: s })} aria-pressed={f.step_goal === s} className={chip(f.step_goal === s)}>
              {s / 1000}k
            </button>
          ))}
        </div>
      </div>

      <p className="text-xs text-muted">{t('General guidance, not medical advice. If you are pregnant, diabetic or have a health condition, please check with your doctor first.')}</p>
      <Button type="submit" loading={busy}>
        {profile ? t('Save goal') : t('Start my challenge')} 💪
      </Button>
      {onCancel && (
        <button type="button" onClick={onCancel} className="w-full py-1 text-sm font-bold text-muted">
          {t('Cancel')}
        </button>
      )}
    </form>
  )
}

type Row = { name: string; calories: string; protein_g: number; source: 'quick' | 'ai' | 'manual' }

/** "Ate something extra?" — tap a common item, say it (AI estimates), or type the calories. */
export function ExtraFoodSheet({ date, quick, onClose, onDone }: { date: string; quick: QuickFood[]; onClose: () => void; onDone: (message: string) => void }) {
  const { t } = useI18n()
  const [rows, setRows] = useState<Row[]>([])
  const [text, setText] = useState('')
  const [thinking, setThinking] = useState(false)
  const [busy, setBusy] = useState(false)
  const [error, setError] = useState('')
  const setRow = (i: number, patch: Partial<Row>) => setRows(rows.map((r, j) => (j === i ? { ...r, ...patch } : r)))

  async function estimate() {
    setThinking(true)
    setError('')
    try {
      const r = await api<{ data: { name: string; calories: number; protein_g: number }[] }>('/coach/food/estimate', { method: 'POST', body: { text } })
      setRows([...rows, ...r.data.map((i) => ({ ...i, calories: String(i.calories), source: 'ai' as const }))])
      setText('')
    } catch (e) {
      setError((e as ApiError).message)
    } finally {
      setThinking(false)
    }
  }

  async function save() {
    setBusy(true)
    setError('')
    try {
      const r = await api<{ message: string }>('/coach/food', {
        method: 'POST',
        body: { date, items: rows.map((r) => ({ name: r.name, calories: Number(r.calories), protein_g: r.protein_g, source: r.source })) },
      })
      onDone(r.message)
    } catch (e) {
      setError((e as ApiError).message)
      setBusy(false)
    }
  }

  const ready = rows.length > 0 && rows.every((r) => r.name.trim() && r.calories !== '' && Number(r.calories) >= 0)
  const total = rows.reduce((s, r) => s + (Number(r.calories) || 0), 0)

  return (
    <Sheet open onClose={onClose} title={t('Ate something extra?')}>
      <div className="space-y-4">
        <p className="text-sm text-muted">{t('No judgement — every honest log helps your coach help you. 🙂')}</p>
        {error && <Alert kind="error">{error}</Alert>}

        <div className="flex flex-wrap gap-2">
          {quick.map((q) => (
            <button key={q.name} onClick={() => setRows([...rows, { name: q.label, calories: String(q.calories), protein_g: q.protein_g, source: 'quick' }])} className="rounded-full border border-line bg-white px-3 py-1.5 text-sm font-semibold">
              {q.label} <span className="text-muted">· {q.calories}</span>
            </button>
          ))}
        </div>

        <div className="space-y-2 rounded-2xl border border-line bg-white p-3">
          <p className="text-sm font-semibold">✦ {t('Or just say it')}</p>
          <VoiceInput rows={2} value={text} onChange={setText} placeholder={t('e.g. 2 vadai and a filter coffee')} />
          {thinking ? (
            <div>
              <Spinner label={t('Counting calories…')} />
              <p className="-mt-12 pb-4 text-center text-xs text-muted">{t(AI_WAIT)}</p>
            </div>
          ) : (
            <button onClick={estimate} disabled={!text.trim()} className="w-full rounded-xl border border-brand py-2.5 font-semibold text-brand disabled:opacity-40">
              {t('Estimate calories')}
            </button>
          )}
        </div>

        {rows.length > 0 && (
          <ul className="space-y-2">
            {rows.map((r, i) => (
              <li key={i} className="flex items-center gap-2">
                <input value={r.name} onChange={(e) => setRow(i, { name: e.target.value })} aria-label={t('Food')} className="min-w-0 flex-1 rounded-xl border border-line bg-white px-3 py-2 font-semibold" />
                <input value={r.calories} onChange={(e) => setRow(i, { calories: e.target.value })} inputMode="numeric" aria-label="kcal" className="w-20 rounded-xl border border-line bg-white px-2 py-2 text-right" />
                <span className="text-xs text-muted">kcal</span>
                <button onClick={() => setRows(rows.filter((_, j) => j !== i))} aria-label={t('Remove')} className="px-1 text-xl text-muted">
                  ×
                </button>
              </li>
            ))}
          </ul>
        )}
        <button onClick={() => setRows([...rows, { name: '', calories: '', protein_g: 0, source: 'manual' }])} className="text-sm font-bold text-brand">
          + {t('Type it myself')}
        </button>
        <Button onClick={save} loading={busy} disabled={!ready}>
          {t('Log {n} kcal', { n: Math.round(total) })}
        </Button>
      </div>
    </Sheet>
  )
}

export function WeightSheet({ current, onClose, onDone }: { current: number; onClose: () => void; onDone: (message: string) => void }) {
  const { t } = useI18n()
  const [kg, setKg] = useState(String(current))
  const [busy, setBusy] = useState(false)
  const [error, setError] = useState('')

  async function save(e: FormEvent) {
    e.preventDefault()
    setBusy(true)
    try {
      const r = await api<{ message: string }>('/coach/weight', { method: 'POST', body: { weight_kg: Number(kg) } })
      onDone(r.message)
    } catch (err) {
      setError((err as ApiError).message)
      setBusy(false)
    }
  }

  return (
    <Sheet open onClose={onClose} title={t('Today’s weight')}>
      <form onSubmit={save} className="space-y-4">
        {error && <Alert kind="error">{error}</Alert>}
        <Field label={t('Weight (kg)')} inputMode="decimal" value={kg} onChange={(e) => setKg(e.target.value)} autoFocus />
        <p className="text-xs text-muted">{t('Tip: weigh yourself in the morning, before breakfast, once or twice a week. Daily ups and downs are just water.')}</p>
        <Button type="submit" loading={busy}>{t('Save')}</Button>
      </form>
    </Sheet>
  )
}
