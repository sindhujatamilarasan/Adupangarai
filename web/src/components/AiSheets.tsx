import { useMemo, useState } from 'react'
import { useNavigate } from 'react-router-dom'
import {
  addDays,
  AI_WAIT,
  api,
  ApiError,
  isoDate,
  MEAL_TYPES,
  useApi,
  type AiItem,
  type AiPlanEntry,
  type Ingredient,
  type MealType,
  type RecipeDraft,
  type UnitInfo,
} from '../api'
import VoiceInput from './VoiceInput'
import { nm, tk, unitLabel, useI18n } from '../i18n'
import { Alert, Button, Sheet, Spinner } from './ui'

const problemText: Record<NonNullable<AiItem['problem']>, string> = {
  unknown_ingredient: tk('Not in your ingredient list — pick one'),
  no_quantity: tk('How much?'),
  unit_mismatch: tk('Check the unit'),
  guessed: tk('Best guess — check it'),
}

function Thinking({ label }: { label: string }) {
  const { t } = useI18n()
  return (
    <div className="rounded-2xl border border-line bg-white p-2 text-center">
      <Spinner label={label} />
      <p className="-mt-8 pb-4 text-xs text-muted">{t(AI_WAIT)}</p>
    </div>
  )
}

/** Speak what you bought -> review rows -> add to kitchen (one transaction). */
export function AiPantrySheet({ onClose, onDone }: { onClose: () => void; onDone: (message: string) => void }) {
  const { t, lang } = useI18n()
  const ingredients = useApi<{ data: Ingredient[] }>('/ingredients')
  const units = useApi<{ data: UnitInfo[] }>('/units')
  const [text, setText] = useState('')
  const [rows, setRows] = useState<(AiItem & { expiry_date: string })[] | null>(null)
  const [source, setSource] = useState('')
  const [type, setType] = useState<'PURCHASE' | 'ADD'>('PURCHASE')
  const [busy, setBusy] = useState(false)
  const [error, setError] = useState('')
  const byId = useMemo(() => new Map((ingredients.data?.data ?? []).map((i) => [i.id, i])), [ingredients.data])

  async function understand() {
    setBusy(true)
    setError('')
    try {
      const r = await api<{ data: AiItem[]; source: string }>('/ai/pantry-parse', { method: 'POST', body: { text } })
      setSource(r.source)
      setRows(r.data.map((i) => ({ ...i, expiry_date: i.expiry_days != null ? addDays(isoDate(new Date()), i.expiry_days) : '' })))
    } catch (e) {
      setError((e as ApiError).message)
    } finally {
      setBusy(false)
    }
  }

  const setRow = (i: number, patch: Partial<AiItem & { expiry_date: string }>) => setRows(rows!.map((r, j) => (j === i ? { ...r, ...patch, problem: null } : r)))
  const unitsFor = (id: number | null) => {
    const dim = (units.data?.data ?? []).find((u) => u.value === byId.get(id ?? 0)?.default_unit)?.dimension
    return (units.data?.data ?? []).filter((u) => u.dimension === dim)
  }
  const ready = rows?.length && rows.every((r) => r.ingredient_id && r.quantity && r.quantity > 0 && r.unit)

  async function confirm() {
    setBusy(true)
    setError('')
    try {
      const r = await api<{ message: string }>('/pantry/bulk', {
        method: 'POST',
        body: { type, items: rows!.map((r) => ({ ingredient_id: r.ingredient_id, quantity: r.quantity, unit: r.unit, expiry_date: r.expiry_date || null })) },
      })
      onDone(r.message)
    } catch (e) {
      setError((e as ApiError).message)
      setBusy(false)
    }
  }

  return (
    <Sheet open onClose={onClose} title={`🎙️ ${t('Say what you bought')}`}>
      <div className="space-y-4">
        {error && <Alert kind="error">{error}</Alert>}
        {!rows ? (
          <>
            <VoiceInput value={text} onChange={setText} placeholder={t('e.g. I bought 1 kg chicken, a dozen eggs and 2 litres of milk')} />
            {busy ? <Thinking label={t('Understanding…')} /> : <Button onClick={understand} disabled={!text.trim()}>{t('Understand')}</Button>}
          </>
        ) : (
          <>
            <p className="text-sm text-muted">
              {t('Check and fix before adding.')} {source === 'rules' ? t('Read instantly (no AI needed).') : `✨ ${t('Understood by AI.')}`}
            </p>
            <ul className="space-y-2">
              {rows.map((r, i) => (
                <li key={i} className={`space-y-2 rounded-2xl border border-line bg-white p-3 ${r.problem ? 'ring-2 ring-amber-400' : ''}`}>
                  <div className="flex items-center gap-2">
                    <select
                      value={r.ingredient_id ?? ''}
                      onChange={(e) => {
                        const ing = byId.get(Number(e.target.value))
                        setRow(i, { ingredient_id: ing?.id ?? null, name: ing ? nm(ing) : r.name, unit: ing?.default_unit ?? null })
                      }}
                      className="min-w-0 flex-1 rounded-xl border border-line bg-white px-2 py-2 font-semibold"
                    >
                      <option value="">{t('Choose ingredient…')}</option>
                      {(ingredients.data?.data ?? []).map((ing) => (
                        <option key={ing.id} value={ing.id}>
                          {nm(ing)}
                        </option>
                      ))}
                    </select>
                    <button onClick={() => setRows(rows.filter((_, j) => j !== i))} aria-label={t('Remove')} className="px-2 text-xl text-muted">
                      ×
                    </button>
                  </div>
                  <div className="grid grid-cols-[1fr_5.5rem_8.5rem] gap-2">
                    <input
                      type="number"
                      inputMode="decimal"
                      min="0"
                      step="any"
                      value={r.quantity ?? ''}
                      onChange={(e) => setRow(i, { quantity: e.target.value === '' ? null : Number(e.target.value) })}
                      className="rounded-xl border border-line px-3 py-2"
                      aria-label={t('Quantity')}
                    />
                    <select value={r.unit ?? ''} onChange={(e) => setRow(i, { unit: e.target.value as AiItem['unit'] })} className="rounded-xl border border-line bg-white px-2 py-2" aria-label={t('Unit')}>
                      {unitsFor(r.ingredient_id).map((u) => (
                        <option key={u.value} value={u.value}>{unitLabel(lang, u.value)}</option>
                      ))}
                    </select>
                    <input type="date" value={r.expiry_date} onChange={(e) => setRow(i, { expiry_date: e.target.value })} className="rounded-xl border border-line px-2 py-2 text-sm" aria-label={t('Expiry date')} />
                  </div>
                  <p className="text-xs text-muted">
                    {t('Heard “{heard}”', { heard: r.heard })}{r.problem && <span className="font-bold text-amber-700"> · {t(problemText[r.problem])}</span>}
                  </p>
                </li>
              ))}
            </ul>
            <div className="grid grid-cols-2 gap-1 rounded-2xl border border-line bg-white p-1">
              {(['PURCHASE', 'ADD'] as const).map((kind) => (
                <button key={kind} onClick={() => setType(kind)} className={`rounded-xl py-2 text-sm font-bold ${type === kind ? 'bg-ink text-white' : 'text-muted'}`}>
                  {kind === 'PURCHASE' ? t('I bought these') : t('Just add to stock')}
                </button>
              ))}
            </div>
            <Button onClick={confirm} loading={busy} disabled={!ready}>
              {t('Add {n} item(s) to kitchen', { n: rows.length })}
            </Button>
            <button onClick={() => setRows(null)} className="w-full py-1 text-sm font-bold text-muted">
              ← {t('Say it again')}
            </button>
          </>
        )}
      </div>
    </Sheet>
  )
}

/** Dictate a recipe -> AI draft -> opens the recipe form to review and save. */
export function AiRecipeSheet({ onClose }: { onClose: () => void }) {
  const { t } = useI18n()
  const navigate = useNavigate()
  const [text, setText] = useState('')
  const [busy, setBusy] = useState(false)
  const [error, setError] = useState('')

  async function draft() {
    setBusy(true)
    setError('')
    try {
      const r = await api<{ data: RecipeDraft }>('/ai/recipe-parse', { method: 'POST', body: { text } })
      navigate('/recipes/new', { state: { draft: r.data } })
    } catch (e) {
      setError((e as ApiError).message)
      setBusy(false)
    }
  }

  return (
    <Sheet open onClose={onClose} title={`🎙️ ${t('Say a recipe')}`}>
      <div className="space-y-4">
        {error && <Alert kind="error">{error}</Alert>}
        <VoiceInput
          rows={7}
          value={text}
          onChange={setText}
          placeholder={t('e.g. Egg curry for 4. Boil 6 eggs. Fry 2 onions and 2 tomatoes in 2 tablespoons oil with 1 teaspoon chilli powder…')}
        />
        <p className="text-xs text-muted">{t('Say the name, how many it serves, ingredients with amounts, and the steps. You’ll review everything before saving.')}</p>
        {busy ? <Thinking label={t('Writing your recipe…')} /> : <Button onClick={draft} disabled={text.trim().length < 20}>✨ {t('Create recipe draft')}</Button>}
      </div>
    </Sheet>
  )
}

const goals = [
  { key: 'balanced', label: tk('⚖️ Balanced') },
  { key: 'high_protein', label: tk('💪 High protein') },
  { key: 'low_calorie', label: tk('🥗 Lighter') },
] as const

/** AI picks recipes for the coming days -> preview -> add to planner. */
export function AiPlanSheet({ start: initialStart, onClose, onDone }: { start?: string; onClose: () => void; onDone: (message: string) => void }) {
  const { t, fmtDate } = useI18n()
  const [start, setStart] = useState(initialStart ?? isoDate(new Date()))
  const [days, setDays] = useState(3)
  const [meals, setMeals] = useState<MealType[]>(['breakfast', 'lunch', 'dinner'])
  const [goal, setGoal] = useState<(typeof goals)[number]['key']>('balanced')
  const [servings, setServings] = useState(2)
  const [plan, setPlan] = useState<AiPlanEntry[] | null>(null)
  const [busy, setBusy] = useState(false)
  const [error, setError] = useState('')

  async function run<T>(fn: () => Promise<T>) {
    setBusy(true)
    setError('')
    try {
      return await fn()
    } catch (e) {
      setError((e as ApiError).message)
    } finally {
      setBusy(false)
    }
  }

  const generate = () =>
    run(async () => {
      const r = await api<{ data: AiPlanEntry[] }>('/ai/meal-plan', { method: 'POST', body: { start, days, meals: MEAL_TYPES.filter((m) => meals.includes(m)), goal, servings } })
      if (r.data.length === 0) throw new ApiError(0, t('The AI couldn’t make a plan this time. Please try again.'))
      setPlan(r.data)
    })

  const apply = () =>
    run(async () => {
      const r = await api<{ message: string }>('/meal-plans/bulk', { method: 'POST', body: { entries: plan!.map((e) => ({ date: e.date, meal_type: e.meal_type, recipe_id: e.recipe_id, servings: e.servings })) } })
      onDone(r.message)
    })

  const byDate = new Map<string, AiPlanEntry[]>()
  plan?.forEach((p) => byDate.set(p.date, [...(byDate.get(p.date) ?? []), p]))
  const chip = (active: boolean) => `rounded-full px-3 py-1.5 text-sm font-bold ${active ? 'border border-ink bg-ink text-white' : 'border border-line bg-white text-muted'}`

  return (
    <Sheet open onClose={onClose} title={`✨ ${t('AI meal plan')}`}>
      <div className="space-y-4">
        {error && <Alert kind="error">{error}</Alert>}
        {!plan ? (
          <>
            <div className="grid grid-cols-2 gap-3">
              <label className="text-sm font-semibold text-muted">
                {t('Starting')}
                <input type="date" value={start} onChange={(e) => setStart(e.target.value)} className="mt-1 w-full rounded-xl border border-line bg-white px-3 py-2.5 text-ink" />
              </label>
              <label className="text-sm font-semibold text-muted">
                {t('Days')}
                <select value={days} onChange={(e) => setDays(Number(e.target.value))} className="mt-1 w-full rounded-xl border border-line bg-white px-3 py-2.5 text-ink">
                  {[1, 2, 3, 4, 5, 6, 7].map((d) => (
                    <option key={d}>{d}</option>
                  ))}
                </select>
              </label>
            </div>
            <div>
              <p className="mb-1 text-sm font-semibold text-muted">{t('Meals')}</p>
              <div className="flex flex-wrap gap-2">
                {MEAL_TYPES.map((m) => (
                  <button key={m} onClick={() => setMeals(meals.includes(m) ? meals.filter((x) => x !== m) : [...meals, m])} className={chip(meals.includes(m))}>
                    {t(m)}
                  </button>
                ))}
              </div>
            </div>
            <div>
              <p className="mb-1 text-sm font-semibold text-muted">{t('Goal')}</p>
              <div className="flex flex-wrap gap-2">
                {goals.map((g) => (
                  <button key={g.key} onClick={() => setGoal(g.key)} className={chip(goal === g.key)}>
                    {t(g.label)}
                  </button>
                ))}
              </div>
            </div>
            <div className="flex items-center justify-between rounded-2xl border border-line bg-white p-3">
              <span className="font-semibold">{t('People')}</span>
              <div className="flex items-center gap-3">
                <button onClick={() => setServings(Math.max(1, servings - 1))} className="grid size-8 place-items-center rounded-full bg-cream font-bold" aria-label={t('Fewer')}>
                  −
                </button>
                <span className="w-5 text-center font-semibold">{servings}</span>
                <button onClick={() => setServings(Math.min(20, servings + 1))} className="grid size-8 place-items-center rounded-full bg-cream font-bold" aria-label={t('More')}>
                  +
                </button>
              </div>
            </div>
            {busy ? <Thinking label={t('Planning your meals…')} /> : <Button onClick={generate} disabled={meals.length === 0}>✨ {t('Suggest a plan')}</Button>}
          </>
        ) : (
          <>
            <p className="text-sm text-muted">{t('Chosen from your recipes, favouring what’s in your kitchen and items expiring soon. Nothing is saved until you add it.')}</p>
            {[...byDate].map(([date, entries]) => (
              <section key={date} className="rounded-2xl border border-line bg-white p-3">
                <p className="mb-1 font-semibold">{fmtDate(date, { weekday: 'long', day: 'numeric', month: 'short' })}</p>
                {entries.map((e) => (
                  <p key={e.meal_type} className="flex justify-between py-1 text-sm">
                    <span className="font-semibold tracking-wide text-muted uppercase">{t(e.meal_type)}</span>
                    <span className="font-semibold">{e.recipe_name}</span>
                  </p>
                ))}
              </section>
            ))}
            <Button onClick={apply} loading={busy}>
              {t('Add {n} meals to planner', { n: plan.length })}
            </Button>
            <button onClick={() => setPlan(null)} className="w-full py-1 text-sm font-bold text-muted">
              ← {t('Try different options')}
            </button>
          </>
        )}
      </div>
    </Sheet>
  )
}
