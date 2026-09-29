import { useMemo, useState, type FormEvent } from 'react'
import { useLocation, useNavigate, useParams } from 'react-router-dom'
import { api, ApiError, MEAL_TYPES, fmtQty, useApi, type Ingredient, type RecipeDetail, type RecipeDraft, type UnitInfo } from '../api'
import { Alert, Button, ErrorState, Field, Select, Spinner } from '../components/ui'
import { nm, unitLabel, useI18n } from '../i18n'

type Row = { ingredient_id: string; quantity: string; unit: string; optional: boolean; heard?: string }

export default function RecipeFormPage() {
  const { id } = useParams()
  const existing = useApi<{ data: RecipeDetail }>(id ? `/recipes/${id}` : null)
  const ingredients = useApi<{ data: Ingredient[] }>('/ingredients')
  const units = useApi<{ data: UnitInfo[] }>('/units')
  const { t } = useI18n()
  const state = useLocation().state as { draft?: RecipeDraft } | null
  const draft = id ? undefined : state?.draft

  const error = existing.error ?? ingredients.error ?? units.error
  if (error) return <ErrorState message={error} onRetry={() => [existing, ingredients, units].forEach((r) => r.reload())} />
  if ((id && !existing.data) || !ingredients.data || !units.data) return <Spinner />
  if (existing.data && !existing.data.data.is_editable) return <ErrorState message={t("Built-in recipes can't be edited.")} />

  return <RecipeForm id={id} recipe={existing.data?.data} draft={draft} ingredients={ingredients.data.data} units={units.data.data} />
}

type FormProps = { id?: string; recipe?: RecipeDetail; draft?: RecipeDraft; ingredients: Ingredient[]; units: UnitInfo[] }

function RecipeForm({ id, recipe, draft, ingredients, units }: FormProps) {
  const navigate = useNavigate()
  const { t, lang } = useI18n()
  const src = recipe ?? draft
  const [form, setForm] = useState({
    name: src?.name ?? '',
    description: src?.description ?? '',
    meal_type: src?.meal_type ?? 'lunch',
    cuisine: src?.cuisine ?? '',
    servings: String(src?.servings ?? 2),
    prep_time: String(src?.prep_time ?? 10),
    cook_time: String(src?.cook_time ?? 20),
    is_veg: src?.is_veg ?? true,
    steps: src?.steps.join('\n') ?? '',
  })
  const [rows, setRows] = useState<Row[]>(
    recipe?.ingredients.map((i) => ({ ingredient_id: String(i.ingredient_id), quantity: fmtQty(i.quantity), unit: i.unit, optional: i.optional })) ??
      draft?.ingredients.map((i) => ({
        ingredient_id: i.ingredient_id ? String(i.ingredient_id) : '',
        quantity: i.quantity ? fmtQty(i.quantity) : '',
        unit: i.unit ?? '',
        optional: !!i.optional,
        heard: i.problem ? i.heard : undefined,
      })) ?? [{ ingredient_id: '', quantity: '', unit: '', optional: false }],
  )
  const [errors, setErrors] = useState<Record<string, string[]>>({})
  const [message, setMessage] = useState('')
  const [busy, setBusy] = useState(false)

  const byId = useMemo(() => new Map(ingredients.map((i) => [String(i.id), i])), [ingredients])
  const grouped = useMemo(() => {
    const groups = new Map<string, Ingredient[]>()
    ingredients.forEach((i) => groups.set(nm(i.category), [...(groups.get(nm(i.category)) ?? []), i]))
    return [...groups]
  }, [ingredients])

  const unitsFor = (ingredientId: string) => {
    const dim = units.find((u) => u.value === byId.get(ingredientId)?.default_unit)?.dimension
    return units.filter((u) => u.dimension === dim)
  }

  const set = (k: keyof typeof form) => (e: { target: { value: string } }) => setForm({ ...form, [k]: e.target.value })
  const setRow = (i: number, patch: Partial<Row>) => setRows(rows.map((r, j) => (j === i ? { ...r, ...patch } : r)))

  async function submit(e: FormEvent) {
    e.preventDefault()
    setBusy(true)
    setErrors({})
    setMessage('')
    const body = {
      ...form,
      servings: Number(form.servings),
      prep_time: Number(form.prep_time),
      cook_time: Number(form.cook_time),
      description: form.description || null,
      cuisine: form.cuisine || null,
      steps: form.steps.split('\n').map((s) => s.trim()).filter(Boolean),
      // Keep the AI's nutrition estimate for a new recipe made from a voice draft.
      ...(draft && !id && draft.calories !== null
        ? { calories: draft.calories, protein_g: draft.protein_g, carbs_g: draft.carbs_g, fat_g: draft.fat_g, fiber_g: draft.fiber_g }
        : {}),
      ingredients: rows
        .filter((r) => r.ingredient_id)
        .map((r) => ({ ingredient_id: Number(r.ingredient_id), quantity: Number(r.quantity), unit: r.unit, optional: r.optional })),
    }
    try {
      const r = await api<{ data: { id: number } }>(id ? `/recipes/${id}` : '/recipes', { method: id ? 'PUT' : 'POST', body })
      navigate(`/recipes/${r.data.id}`, { replace: true })
    } catch (err) {
      const e = err as ApiError
      setErrors(e.errors ?? {})
      setMessage(e.message)
    } finally {
      setBusy(false)
    }
  }

  return (
    <form onSubmit={submit} className="space-y-5" noValidate>
      <button type="button" onClick={() => navigate(-1)} className="text-sm font-bold text-muted">
        ← {t('Cancel')}
      </button>
      <h1 className="font-display text-[1.75rem] font-semibold tracking-tight">{id ? t('Edit recipe') : draft ? `✨ ${t('Check your recipe')}` : t('New recipe')}</h1>
      {draft && (
        <div className="rounded-2xl bg-amber-50 p-4 text-sm text-amber-900">
          {t('Drafted by AI from what you said. Check the amounts, pick any highlighted ingredients, then save.')}
          {draft.calories !== null && ` ${t('Estimated {kcal} kcal and {protein} g protein per serving.', { kcal: draft.calories, protein: draft.protein_g ?? 0 })}`}
        </div>
      )}
      {message && <Alert kind="error">{message}</Alert>}

      <section className="space-y-3 card p-5">
        <Field label={t('Name')} value={form.name} onChange={set('name')} error={errors.name?.[0]} />
        <Field label={t('Short description')} value={form.description} onChange={set('description')} error={errors.description?.[0]} />
        <div className="grid grid-cols-2 gap-3">
          <Select label={t('Meal')} value={form.meal_type} onChange={set('meal_type')}>
            {MEAL_TYPES.map((m) => (
              <option key={m} value={m}>
                {t(m)}
              </option>
            ))}
          </Select>
          <Field label={t('Cuisine')} value={form.cuisine} onChange={set('cuisine')} placeholder={t('e.g. South Indian')} />
        </div>
        <div className="grid grid-cols-3 gap-3">
          <Field label={t('Serves')} type="number" min="1" value={form.servings} onChange={set('servings')} error={errors.servings?.[0]} />
          <Field label={t('Prep min')} type="number" min="0" value={form.prep_time} onChange={set('prep_time')} error={errors.prep_time?.[0]} />
          <Field label={t('Cook min')} type="number" min="0" value={form.cook_time} onChange={set('cook_time')} error={errors.cook_time?.[0]} />
        </div>
        <div className="grid grid-cols-2 gap-1 rounded-2xl bg-cream p-1">
          {[true, false].map((veg) => (
            <button
              key={String(veg)}
              type="button"
              onClick={() => setForm({ ...form, is_veg: veg })}
              className={`rounded-xl py-2 text-sm font-bold ${form.is_veg === veg ? (veg ? 'bg-leaf text-white' : 'bg-red-700 text-white') : 'text-muted'}`}
            >
              {veg ? t('Veg') : t('Non-veg')}
            </button>
          ))}
        </div>
      </section>

      <section className="space-y-3 card p-5">
        <h2 className="font-display text-lg font-semibold">{t('Ingredients')}</h2>
        {errors.ingredients && <Alert kind="error">{errors.ingredients[0]}</Alert>}
        {rows.map((row, i) => (
          <div key={i} className={`space-y-2 rounded-2xl border p-3 ${row.heard ? 'border-amber-400 bg-amber-50' : 'border-line'}`}>
            {row.heard && (
              <p className="text-xs font-bold text-amber-800">
                {t('AI heard “{heard}”', { heard: row.heard })} — {row.ingredient_id ? t('check this is the right ingredient and amount.') : t('pick the closest ingredient or remove this row.')}
              </p>
            )}
            <Select
              label={t('Ingredient {n}', { n: i + 1 })}
              value={row.ingredient_id}
              onChange={(e) => setRow(i, { ingredient_id: e.target.value, unit: byId.get(e.target.value)?.default_unit ?? '' })}
              error={errors[`ingredients.${i}.ingredient_id`]?.[0]}
            >
              <option value="">{t('Choose…')}</option>
              {grouped.map(([cat, list]) => (
                <optgroup key={cat} label={cat}>
                  {list.map((ing) => (
                    <option key={ing.id} value={ing.id}>
                      {nm(ing)}
                    </option>
                  ))}
                </optgroup>
              ))}
            </Select>
            <div className="grid grid-cols-[1fr_6rem] gap-2">
              <Field
                label={t('Quantity')}
                type="number"
                inputMode="decimal"
                min="0"
                step="any"
                value={row.quantity}
                onChange={(e) => setRow(i, { quantity: e.target.value })}
                error={errors[`ingredients.${i}.quantity`]?.[0]}
              />
              <Select label={t('Unit')} value={row.unit} onChange={(e) => setRow(i, { unit: e.target.value })} error={errors[`ingredients.${i}.unit`]?.[0]}>
                {unitsFor(row.ingredient_id).map((u) => (
                  <option key={u.value} value={u.value}>{unitLabel(lang, u.value)}</option>
                ))}
              </Select>
            </div>
            <div className="flex items-center justify-between">
              <label className="flex items-center gap-2 text-sm font-semibold text-muted">
                <input type="checkbox" checked={row.optional} onChange={(e) => setRow(i, { optional: e.target.checked })} className="size-4 accent-brand" />
                {t('Optional')}
              </label>
              {rows.length > 1 && (
                <button type="button" onClick={() => setRows(rows.filter((_, j) => j !== i))} className="text-sm font-bold text-red-700">
                  {t('Remove')}
                </button>
              )}
            </div>
          </div>
        ))}
        <button
          type="button"
          onClick={() => setRows([...rows, { ingredient_id: '', quantity: '', unit: '', optional: false }])}
          className="w-full rounded-2xl border border-dashed border-brand py-3 font-bold text-brand"
        >
          + {t('Add ingredient')}
        </button>
      </section>

      <section className="card p-5">
        <label className="block">
          <span className="mb-1 block font-semibold">{t('Method')}</span>
          <span className="mb-2 block text-sm text-muted">{t('One step per line.')}</span>
          <textarea
            rows={6}
            value={form.steps}
            onChange={set('steps')}
            className="w-full rounded-xl border border-line bg-white px-4 py-3 outline-none focus:border-brand"
          />
        </label>
      </section>

      <Button type="submit" loading={busy}>
        {id ? t('Save recipe') : t('Create recipe')}
      </Button>
    </form>
  )
}
