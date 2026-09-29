import { useMemo, useState, type FormEvent } from 'react'
import { api, ApiError, useApi, type Category, type Ingredient, type UnitInfo, type UnitValue } from '../api'
import { Alert, Button, Field, Select, Sheet, Spinner } from './ui'

type Props = { open: boolean; onClose: () => void; onSaved: (message: string) => void; units: UnitInfo[] }

export default function AddPantrySheet({ open, onClose, onSaved, units }: Props) {
  const ingredients = useApi<{ data: Ingredient[] }>(open ? '/ingredients' : null)
  const categories = useApi<{ data: Category[] }>(open ? '/ingredient-categories' : null)
  const [search, setSearch] = useState('')
  const [picked, setPicked] = useState<Ingredient | null>(null)
  const [newCategory, setNewCategory] = useState('')
  const [form, setForm] = useState({ quantity: '', unit: '' as UnitValue | '', expiry_date: '', minimum_stock: '', storage_location: '' })
  const [errors, setErrors] = useState<Record<string, string[]>>({})
  const [message, setMessage] = useState('')
  const [busy, setBusy] = useState(false)

  const matches = useMemo(() => {
    const q = search.trim().toLowerCase()
    const list = ingredients.data?.data ?? []
    return q ? list.filter((i) => i.name.toLowerCase().includes(q)) : list
  }, [search, ingredients.data])

  const unitOptions = useMemo(() => {
    const dim = units.find((u) => u.value === picked?.default_unit)?.dimension
    return units.filter((u) => u.dimension === dim)
  }, [units, picked])

  function reset() {
    setSearch('')
    setPicked(null)
    setNewCategory('')
    setForm({ quantity: '', unit: '', expiry_date: '', minimum_stock: '', storage_location: '' })
    setErrors({})
    setMessage('')
  }

  function close() {
    reset()
    onClose()
  }

  function pick(i: Ingredient) {
    setPicked(i)
    setForm((f) => ({ ...f, unit: i.default_unit }))
  }

  async function createIngredient(unit: UnitValue) {
    setBusy(true)
    setMessage('')
    try {
      const r = await api<{ data: Ingredient }>('/ingredients', {
        method: 'POST',
        body: { name: search.trim(), ingredient_category_id: Number(newCategory), default_unit: unit },
      })
      pick(r.data)
      ingredients.reload()
    } catch (e) {
      setMessage((e as ApiError).message)
    } finally {
      setBusy(false)
    }
  }

  async function submit(e: FormEvent) {
    e.preventDefault()
    if (!picked) return
    setBusy(true)
    setErrors({})
    setMessage('')
    try {
      await api('/pantry', {
        method: 'POST',
        body: {
          ingredient_id: picked.id,
          quantity: Number(form.quantity),
          unit: form.unit,
          expiry_date: form.expiry_date || null,
          minimum_stock: form.minimum_stock === '' ? null : Number(form.minimum_stock),
          storage_location: form.storage_location || null,
        },
      })
      onSaved(`${picked.name} added to your kitchen.`)
      close()
    } catch (err) {
      const e = err as ApiError
      setErrors(e.errors ?? {})
      setMessage(e.message)
    } finally {
      setBusy(false)
    }
  }

  const set = (k: keyof typeof form) => (e: { target: { value: string } }) => setForm({ ...form, [k]: e.target.value })

  return (
    <Sheet open={open} onClose={close} title={picked ? `Add ${picked.name}` : 'Add to kitchen'}>
      {!picked ? (
        <div className="space-y-3">
          <Field label="Search ingredient" placeholder="e.g. tomato" value={search} onChange={(e) => setSearch(e.target.value)} autoFocus />
          {ingredients.loading && <Spinner />}
          {ingredients.error && <Alert kind="error">{ingredients.error}</Alert>}
          <ul className="max-h-72 divide-y divide-line overflow-y-auto rounded-2xl bg-white">
            {matches.map((i) => (
              <li key={i.id}>
                <button type="button" onClick={() => pick(i)} className="flex w-full items-center justify-between gap-3 px-4 py-2.5 text-left">
                  <span className="flex items-center gap-3 font-semibold">
                    <span className="text-2xl" aria-hidden>
                      {i.display_icon}
                    </span>
                    {i.name}
                  </span>
                  <span className="text-xs text-muted">{i.category.name}</span>
                </button>
              </li>
            ))}
          </ul>
          {search.trim() && !ingredients.loading && matches.length === 0 && (
            <div className="space-y-3 rounded-2xl bg-white p-4">
              <p className="text-sm">
                No match. Add <b>{search.trim()}</b> as a new ingredient:
              </p>
              {message && <Alert kind="error">{message}</Alert>}
              <Select label="Category" value={newCategory} onChange={(e) => setNewCategory(e.target.value)}>
                <option value="">Choose…</option>
                {categories.data?.data.map((c) => (
                  <option key={c.id} value={c.id}>
                    {c.name}
                  </option>
                ))}
              </Select>
              <div className="flex flex-wrap gap-2">
                {units.map((u) => (
                  <button
                    key={u.value}
                    type="button"
                    disabled={!newCategory || busy}
                    onClick={() => createIngredient(u.value)}
                    className="rounded-full border border-line px-3 py-1.5 text-sm font-semibold disabled:opacity-40"
                  >
                    {u.value}
                  </button>
                ))}
              </div>
              <p className="text-xs text-muted">Pick the unit you usually measure it in.</p>
            </div>
          )}
        </div>
      ) : (
        <form onSubmit={submit} className="space-y-4" noValidate>
          {message && <Alert kind="error">{message}</Alert>}
          <div className="grid grid-cols-[1fr_7rem] gap-3">
            <Field label="Quantity" type="number" inputMode="decimal" min="0" step="any" value={form.quantity} onChange={set('quantity')} error={errors.quantity?.[0]} autoFocus />
            <Select label="Unit" value={form.unit} onChange={set('unit')} error={errors.unit?.[0]}>
              {unitOptions.map((u) => (
                <option key={u.value}>{u.value}</option>
              ))}
            </Select>
          </div>
          <Field label="Expiry date (optional)" type="date" value={form.expiry_date} onChange={set('expiry_date')} error={errors.expiry_date?.[0]} />
          <div className="grid grid-cols-2 gap-3">
            <Field label="Min. stock (optional)" type="number" inputMode="decimal" min="0" step="any" value={form.minimum_stock} onChange={set('minimum_stock')} error={errors.minimum_stock?.[0]} />
            <Select label="Stored in" value={form.storage_location} onChange={set('storage_location')}>
              <option value="">—</option>
              <option value="pantry">Pantry</option>
              <option value="fridge">Fridge</option>
              <option value="freezer">Freezer</option>
            </Select>
          </div>
          <div className="flex gap-3">
            <button type="button" onClick={() => setPicked(null)} className="rounded-2xl border border-line bg-white px-4 font-bold text-muted">
              Back
            </button>
            <Button type="submit" loading={busy} disabled={!form.quantity}>
              Add to kitchen
            </Button>
          </div>
        </form>
      )}
    </Sheet>
  )
}
