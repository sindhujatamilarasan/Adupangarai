import { useState, type FormEvent } from 'react'
import { api, ApiError, fmtQty, useApi, type GroceryItem, type GroceryResponse } from '../api'
import { Alert, Button, EmptyState, ErrorState, Sheet, Spinner } from '../components/ui'

const fmtDate = (iso: string) => new Date(`${iso}T00:00:00`).toLocaleDateString(undefined, { day: 'numeric', month: 'short' })
const qty = (i: GroceryItem) => (i.quantity ? `${fmtQty(i.quantity)} ${i.unit ?? ''}` : '')

export default function GroceriesPage() {
  const res = useApi<GroceryResponse>('/grocery')
  const [data, setData] = useState<GroceryResponse | null>(null)
  const list = data ?? res.data
  const [busy, setBusy] = useState<string | null>(null)
  const [flash, setFlash] = useState<{ kind: 'error' | 'success'; text: string } | null>(null)
  const [newItem, setNewItem] = useState('')
  const [openId, setOpenId] = useState<number | null>(null)
  const [confirming, setConfirming] = useState(false)

  async function call(key: string, fn: () => Promise<GroceryResponse | unknown>, success?: string) {
    setBusy(key)
    setFlash(null)
    try {
      const r = await fn()
      if (r && typeof r === 'object' && 'summary' in r) setData(r as GroceryResponse)
      else {
        setData(await api<GroceryResponse>('/grocery'))
      }
      if (success) setFlash({ kind: 'success', text: success })
      return true
    } catch (e) {
      setFlash({ kind: 'error', text: (e as ApiError).message })
      return false
    } finally {
      setBusy(null)
    }
  }

  const generate = () => call('generate', () => api('/grocery/generate', { method: 'POST', body: {} }), 'List updated from your meal plan for the next 7 days.')
  const toggle = (i: GroceryItem) => call(`item-${i.id}`, () => api(`/grocery/items/${i.id}`, { method: 'PATCH', body: { purchased: !i.purchased } }))
  const clear = () => call('clear', () => api('/grocery/clear-purchased', { method: 'POST' }), 'Cleared purchased items.')

  async function add(e: FormEvent) {
    e.preventDefault()
    if (!newItem.trim()) return
    if (await call('add', () => api('/grocery/items', { method: 'POST', body: { name: newItem.trim() } }))) setNewItem('')
  }

  if (!list && res.loading) return <Spinner label="Loading your list…" />
  if (!list) return <ErrorState message={res.error ?? 'Could not load groceries.'} onRetry={res.reload} />

  const groups = new Map<string, GroceryItem[]>()
  list.data.forEach((i) => groups.set(i.category, [...(groups.get(i.category) ?? []), i]))
  const pending = list.data.filter((i) => i.purchased && i.ingredient_id && !i.added_to_pantry_at)
  const open = list.data.find((i) => i.id === openId)

  return (
    <div>
      <div className="flex items-center justify-between">
        <h1 className="text-2xl font-extrabold">Groceries</h1>
        {list.summary.total > 0 && (
          <span className="text-sm font-bold text-muted">
            {list.summary.remaining} left{list.summary.spent > 0 && ` · ₹${fmtQty(list.summary.spent)}`}
          </span>
        )}
      </div>

      <div className="mt-4 space-y-3">
        <button
          onClick={generate}
          disabled={!!busy}
          className="w-full rounded-2xl bg-white p-4 text-left shadow-sm active:scale-[.99] disabled:opacity-60"
        >
          <p className="font-bold text-brand">{busy === 'generate' ? 'Calculating…' : '🧮 Update from meal plan'}</p>
          <p className="text-sm text-muted">
            {list.list.planned_from
              ? `Last calculated for ${fmtDate(list.list.planned_from)} – ${fmtDate(list.list.planned_to!)}. Recalculate for the next 7 days.`
              : 'Adds what your planned meals for the next 7 days need, minus what’s in your kitchen.'}
          </p>
        </button>

        {flash && <Alert kind={flash.kind}>{flash.text}</Alert>}

        {pending.length > 0 && (
          <div className="flex items-center gap-3 rounded-2xl bg-green-50 p-4">
            <p className="flex-1 text-sm font-semibold text-green-900">
              Add {pending.length} purchased food item{pending.length > 1 ? 's' : ''} to your kitchen?
            </p>
            <button onClick={() => setConfirming(true)} className="rounded-full bg-leaf px-4 py-2 text-sm font-bold text-white">
              Review
            </button>
          </div>
        )}

        <form onSubmit={add} className="flex gap-2">
          <input
            value={newItem}
            onChange={(e) => setNewItem(e.target.value)}
            placeholder="Add item, e.g. Soap"
            className="flex-1 rounded-2xl border border-line bg-white px-4 py-3 outline-none focus:border-brand"
          />
          <button disabled={!newItem.trim() || !!busy} className="rounded-2xl bg-brand px-5 font-bold text-white disabled:opacity-50">
            Add
          </button>
        </form>

        {list.data.length === 0 && (
          <EmptyState emoji="🛒" title="Your list is empty">
            Plan some meals, then tap “Update from meal plan” — or add items yourself.
          </EmptyState>
        )}

        {[...groups].map(([category, items]) => (
          <section key={category} className="rounded-3xl bg-white p-2">
            <h2 className="px-3 pt-2 pb-1 text-xs font-bold uppercase tracking-wide text-muted">{category}</h2>
            <ul>
              {items.map((i) => (
                <li key={i.id} className="flex items-center gap-3 rounded-2xl px-3 py-2.5">
                  <button
                    onClick={() => toggle(i)}
                    disabled={!!i.added_to_pantry_at || busy === `item-${i.id}`}
                    aria-label={i.purchased ? `Unmark ${i.name}` : `Mark ${i.name} purchased`}
                    className={`grid size-7 shrink-0 place-items-center rounded-full border-2 text-sm font-bold ${i.purchased ? 'border-leaf bg-leaf text-white' : 'border-line'}`}
                  >
                    {i.purchased && '✓'}
                  </button>
                  <button onClick={() => setOpenId(i.id)} className="flex min-w-0 flex-1 items-center justify-between gap-2 text-left">
                    <span className={`flex min-w-0 items-center gap-2 font-semibold ${i.purchased ? 'text-muted line-through opacity-70' : ''}`}>
                      <span className="text-xl" aria-hidden>
                        {i.ingredient?.display_icon ?? '🧴'}
                      </span>
                      <span className="truncate">{i.name}</span>
                    </span>
                    <span className="shrink-0 text-sm text-muted">
                      {i.actual_quantity ? `${fmtQty(i.actual_quantity)} ${i.unit ?? ''}` : qty(i)}
                      {i.price !== null && ` · ₹${fmtQty(i.price)}`}
                      {i.added_to_pantry_at && ' · in kitchen'}
                    </span>
                  </button>
                </li>
              ))}
            </ul>
          </section>
        ))}

        {list.data.some((i) => i.purchased) && (
          <button onClick={clear} disabled={!!busy} className="w-full py-2 text-sm font-bold text-muted">
            Clear purchased items
          </button>
        )}
      </div>

      {open && <ItemSheet key={open.id} item={open} onClose={() => setOpenId(null)} onSaved={(r) => setData(r)} />}
      {confirming && (
        <ConfirmPantrySheet
          items={pending}
          onClose={() => setConfirming(false)}
          onDone={(r) => {
            setData(r)
            setConfirming(false)
            setFlash({ kind: 'success', text: 'Added to your kitchen.' })
          }}
        />
      )}
    </div>
  )
}

function ItemSheet({ item, onClose, onSaved }: { item: GroceryItem; onClose: () => void; onSaved: (r: GroceryResponse) => void }) {
  const [form, setForm] = useState({
    quantity: item.quantity === null ? '' : fmtQty(item.quantity),
    actual_quantity: item.actual_quantity === null ? '' : fmtQty(item.actual_quantity),
    price: item.price === null ? '' : fmtQty(item.price),
  })
  const [error, setError] = useState('')
  const [busy, setBusy] = useState(false)
  const locked = !!item.added_to_pantry_at
  const num = (v: string) => (v === '' ? null : Number(v))

  async function run(fn: () => Promise<unknown>) {
    setBusy(true)
    setError('')
    try {
      await fn()
      onSaved(await api<GroceryResponse>('/grocery'))
      onClose()
    } catch (e) {
      setError((e as ApiError).message)
      setBusy(false)
    }
  }

  const save = (e: FormEvent) => {
    e.preventDefault()
    const body = locked
      ? { price: num(form.price) }
      : { quantity: num(form.quantity), actual_quantity: num(form.actual_quantity), price: num(form.price), purchased: item.purchased || form.actual_quantity !== '' }
    run(() => api(`/grocery/items/${item.id}`, { method: 'PATCH', body }))
  }

  const input = (k: keyof typeof form, label: string, disabled = false) => (
    <label className="block">
      <span className="mb-1 block text-sm font-semibold text-muted">{label}</span>
      <input
        type="number"
        inputMode="decimal"
        min="0"
        step="any"
        disabled={disabled}
        value={form[k]}
        onChange={(e) => setForm({ ...form, [k]: e.target.value })}
        className="w-full rounded-xl border border-line bg-white px-4 py-3 outline-none focus:border-brand disabled:bg-cream"
      />
    </label>
  )

  return (
    <Sheet open onClose={onClose} title={item.name}>
      <form onSubmit={save} className="space-y-4">
        {error && <Alert kind="error">{error}</Alert>}
        {locked && <Alert kind="success">Already added to your kitchen.</Alert>}
        <div className="grid grid-cols-2 gap-3">
          {input('quantity', `Need${item.unit ? ` (${item.unit})` : ''}`, locked)}
          {input('actual_quantity', `Bought${item.unit ? ` (${item.unit})` : ''}`, locked || !item.unit)}
        </div>
        {input('price', 'Price (₹)')}
        <Button type="submit" loading={busy}>
          Save
        </Button>
        {!locked && (
          <button type="button" onClick={() => run(() => api(`/grocery/items/${item.id}`, { method: 'DELETE' }))} className="w-full py-2 text-sm font-bold text-red-700">
            Remove from list
          </button>
        )}
      </form>
    </Sheet>
  )
}

function ConfirmPantrySheet({ items, onClose, onDone }: { items: GroceryItem[]; onClose: () => void; onDone: (r: GroceryResponse) => void }) {
  const addable = items.filter((i) => (i.actual_quantity ?? i.quantity) && i.unit)
  const skipped = items.filter((i) => !addable.includes(i))
  const [error, setError] = useState('')
  const [busy, setBusy] = useState(false)

  async function confirm() {
    setBusy(true)
    setError('')
    try {
      onDone(await api<GroceryResponse>('/grocery/add-to-pantry', { method: 'POST', body: { item_ids: addable.map((i) => i.id) } }))
    } catch (e) {
      setError((e as ApiError).message)
      setBusy(false)
    }
  }

  return (
    <Sheet open onClose={onClose} title="Add to kitchen?">
      <div className="space-y-4">
        {error && <Alert kind="error">{error}</Alert>}
        <ul className="divide-y divide-line rounded-2xl bg-white px-4">
          {addable.map((i) => (
            <li key={i.id} className="flex justify-between py-3">
              <span className="font-semibold">{i.name}</span>
              <span className="font-bold text-leaf">
                +{fmtQty(i.actual_quantity ?? i.quantity ?? 0)} {i.unit}
              </span>
            </li>
          ))}
        </ul>
        {skipped.length > 0 && (
          <p className="text-sm text-muted">Skipped (no quantity entered): {skipped.map((i) => i.name).join(', ')}. Tap them in the list to add how much you bought.</p>
        )}
        {addable.length > 0 ? (
          <Button onClick={confirm} loading={busy}>
            Add {addable.length} item{addable.length > 1 ? 's' : ''} to kitchen
          </Button>
        ) : (
          <Button onClick={onClose}>OK</Button>
        )}
      </div>
    </Sheet>
  )
}
