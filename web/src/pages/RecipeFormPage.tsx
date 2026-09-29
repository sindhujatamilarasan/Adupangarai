import { useMemo, useState, type FormEvent } from 'react'
import { useLocation, useNavigate, useParams } from 'react-router-dom'
import { api, ApiError, MEAL_TYPES, fmtQty, useApi, type Ingredient, type RecipeDetail, type RecipeDraft, type UnitInfo } from '../api'
import { Alert, Button, ErrorState, Field, Select, Spinner } from '../components/ui'

type Row = { ingredient_id: string; quantity: string; unit: string; optional: boolean; heard?: string }

export default function RecipeFormPage() {
  const { id } = useParams()
  const existing = useApi<{ data: RecipeDetail }>(id ? `/recipes/${id}` : null)
  const ingredients = useApi<{ data: Ingredient[] }>('/ingredients')
  const units = useApi<{ data: UnitInfo[] }>('/units')
  const state = useLocation().state as { draft?: RecipeDraft } | null
  const draft = id ? undefined : state?.draft

  const error = existing.error ?? ingredients.error ?? units.error
  if (error) return <ErrorState message={error} onRetry={() => [existing, ingredients, units].forEach((r) => r.reload())} />
  if ((id && !existing.data) || !ingredients.data || !units.data) return <Spinner />
  if (existing.data && !existing.data.data.is_editable) return <ErrorState message="Built-in recipes can't be edited." />

  return <RecipeForm id={id} recipe={existing.data?.data} draft={draft} ingredients={ingredients.data.data} units={units.data.data} />
}

type FormProps = { id?: string; recipe?: RecipeDetail; draft?: RecipeDraft; ingredients: Ingredient[]; units: UnitInfo[] }

function RecipeForm({ id, recipe, draft, ingredients, units }: FormProps) {
  const navigate = useNavigate()
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
    ingredients.forEach((i) => groups.set(i.category.name, [...(groups.get(i.category.name) ?? []), i]))
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
        ← Cancel
      </button>
      <h1 className="text-2xl font-extrabold">{id ? 'Edit recipe' : draft ? '✨ Check your recipe' : 'New recipe'}</h1>
      {draft && (
        <div className="rounded-2xl bg-amber-50 p-4 text-sm text-amber-900">
          Drafted by AI from what you said. Check the amounts, pick any highlighted ingredients, then save.
          {draft.calories !== null && ` Estimated ${draft.calories} kcal and ${draft.protein_g} g protein per serving.`}
        </div>
      )}
      {message && <Alert kind="error">{message}</Alert>}

      <section className="space-y-3 kolam-card rounded-3xl px-1.5 py-4">
        <Field label="Name" value={form.name} onChange={set('name')} error={errors.name?.[0]} />
        <Field label="Short description" value={form.description} onChange={set('description')} error={errors.description?.[0]} />
        <div className="grid grid-cols-2 gap-3">
          <Select label="Meal" value={form.meal_type} onChange={set('meal_type')}>
            {MEAL_TYPES.map((m) => (
              <option key={m} value={m} className="capitalize">
                {m[0].toUpperCase() + m.slice(1)}
              </option>
            ))}
          </Select>
          <Field label="Cuisine" value={form.cuisine} onChange={set('cuisine')} placeholder="e.g. South Indian" />
        </div>
        <div className="grid grid-cols-3 gap-3">
          <Field label="Serves" type="number" min="1" value={form.servings} onChange={set('servings')} error={errors.servings?.[0]} />
          <Field label="Prep min" type="number" min="0" value={form.prep_time} onChange={set('prep_time')} error={errors.prep_time?.[0]} />
          <Field label="Cook min" type="number" min="0" value={form.cook_time} onChange={set('cook_time')} error={errors.cook_time?.[0]} />
        </div>
        <div className="grid grid-cols-2 gap-1 rounded-2xl bg-cream p-1">
          {[true, false].map((veg) => (
            <button
              key={String(veg)}
              type="button"
              onClick={() => setForm({ ...form, is_veg: veg })}
              className={`rounded-xl py-2 text-sm font-bold ${form.is_veg === veg ? (veg ? 'bg-leaf text-white' : 'bg-red-700 text-white') : 'text-muted'}`}
            >
              {veg ? 'Veg' : 'Non-veg'}
            </button>
          ))}
        </div>
      </section>

      <section className="space-y-3 kolam-card rounded-3xl px-1.5 py-4">
        <h2 className="font-extrabold">Ingredients</h2>
        {errors.ingredients && <Alert kind="error">{errors.ingredients[0]}</Alert>}
        {rows.map((row, i) => (
          <div key={i} className={`space-y-2 rounded-2xl border p-3 ${row.heard ? 'border-amber-400 bg-amber-50' : 'border-line'}`}>
            {row.heard && (
              <p className="text-xs font-bold text-amber-800">
                AI heard “{row.heard}” — {row.ingredient_id ? 'check this is the right ingredient and amount.' : 'pick the closest ingredient or remove this row.'}
              </p>
            )}
            <Select
              label={`Ingredient ${i + 1}`}
              value={row.ingredient_id}
              onChange={(e) => setRow(i, { ingredient_id: e.target.value, unit: byId.get(e.target.value)?.default_unit ?? '' })}
              error={errors[`ingredients.${i}.ingredient_id`]?.[0]}
            >
              <option value="">Choose…</option>
              {grouped.map(([cat, list]) => (
                <optgroup key={cat} label={cat}>
                  {list.map((ing) => (
                    <option key={ing.id} value={ing.id}>
                      {ing.name}
                    </option>
                  ))}
                </optgroup>
              ))}
            </Select>
            <div className="grid grid-cols-[1fr_6rem] gap-2">
              <Field
                label="Quantity"
                type="number"
                inputMode="decimal"
                min="0"
                step="any"
                value={row.quantity}
                onChange={(e) => setRow(i, { quantity: e.target.value })}
                error={errors[`ingredients.${i}.quantity`]?.[0]}
              />
              <Select label="Unit" value={row.unit} onChange={(e) => setRow(i, { unit: e.target.value })} error={errors[`ingredients.${i}.unit`]?.[0]}>
                {unitsFor(row.ingredient_id).map((u) => (
                  <option key={u.value}>{u.value}</option>
                ))}
              </Select>
            </div>
            <div className="flex items-center justify-between">
              <label className="flex items-center gap-2 text-sm font-semibold text-muted">
                <input type="checkbox" checked={row.optional} onChange={(e) => setRow(i, { optional: e.target.checked })} className="size-4 accent-brand" />
                Optional
              </label>
              {rows.length > 1 && (
                <button type="button" onClick={() => setRows(rows.filter((_, j) => j !== i))} className="text-sm font-bold text-red-700">
                  Remove
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
          + Add ingredient
        </button>
      </section>

      <section className="kolam-card rounded-3xl px-1.5 py-4">
        <label className="block">
          <span className="mb-1 block font-extrabold">Method</span>
          <span className="mb-2 block text-sm text-muted">One step per line.</span>
          <textarea
            rows={6}
            value={form.steps}
            onChange={set('steps')}
            className="w-full rounded-xl border border-line bg-white px-4 py-3 outline-none focus:border-brand"
          />
        </label>
      </section>

      <Button type="submit" loading={busy}>
        {id ? 'Save recipe' : 'Create recipe'}
      </Button>
    </form>
  )
}
