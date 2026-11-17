import { useState, type FormEvent } from 'react'
import { api, ApiError, fmtQty, useApi, type GroceryItem, type GroceryResponse, type User } from '../api'
import { useAuth } from '../auth'
import { Alert, Button, EmptyState, ErrorState, Sheet, Spinner } from '../components/ui'
import { translate, unitLabel, useI18n, type Lang } from '../i18n'

const qty = (lang: Lang, i: GroceryItem) => (i.quantity ? `${fmtQty(i.quantity)} ${unitLabel(lang, i.unit)}` : '')
/** "1 × 100 g" when the item is bought in shop packs, else the plain quantity. */
const toBuy = (lang: Lang, i: GroceryItem) => (i.packs && i.ingredient?.pack_label ? `${i.packs} × ${translate(lang, i.ingredient.pack_label)}` : qty(lang, i))

export default function GroceriesPage() {
  const { t, lang, fmtDate: fmtD } = useI18n()
  const fmtDate = (iso: string) => fmtD(iso, { day: 'numeric', month: 'short' })
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

  const generate = () => call('generate', () => api('/grocery/generate', { method: 'POST', body: {} }), t('List updated from your meal plan for the next 7 days.'))
  const toggle = (i: GroceryItem) => call(`item-${i.id}`, () => api(`/grocery/items/${i.id}`, { method: 'PATCH', body: { purchased: !i.purchased } }))
  const clear = () => call('clear', () => api('/grocery/clear-purchased', { method: 'POST' }), t('Cleared purchased items.'))

  async function add(e: FormEvent) {
    e.preventDefault()
    if (!newItem.trim()) return
    if (await call('add', () => api('/grocery/items', { method: 'POST', body: { name: newItem.trim() } }))) setNewItem('')
  }

  if (!list && res.loading) return <Spinner label={t('Loading your list…')} />
  if (!list) return <ErrorState message={res.error ?? t('Could not load groceries.')} onRetry={res.reload} />

  const groups = new Map<string, GroceryItem[]>()
  list.data.forEach((i) => groups.set(i.category, [...(groups.get(i.category) ?? []), i]))
  const pending = list.data.filter((i) => i.purchased && i.ingredient_id && !i.added_to_pantry_at)
  const open = list.data.find((i) => i.id === openId)

  return (
    <div>
      <div className="flex flex-wrap items-center justify-between gap-x-3 gap-y-2">
        <h1 className="font-display text-[1.75rem] font-semibold tracking-tight">{t('Groceries')}</h1>
        {list.summary.total > 0 && (
          <span className="text-sm font-bold text-muted">
            {t('{n} left', { n: list.summary.remaining })}{list.summary.spent > 0 && ` · ₹${fmtQty(list.summary.spent)}`}
          </span>
        )}
      </div>

      <div className="mt-4 space-y-3">
        <button
          onClick={generate}
          disabled={!!busy}
          className="w-full card p-4 text-left active:scale-[.99] disabled:opacity-60"
        >
          <p className="font-bold text-brand">{busy === 'generate' ? t('Calculating…') : `🧮 ${t('Update from meal plan')}`}</p>
          <p className="text-sm text-muted">
            {list.list.planned_from
              ? t('Last calculated for {from} – {to}. Recalculate for the next 7 days.', { from: fmtDate(list.list.planned_from), to: fmtDate(list.list.planned_to!) })
              : t('Adds what your planned meals for the next 7 days need, minus what’s in your kitchen.')}
          </p>
        </button>

        {flash && <Alert kind={flash.kind}>{flash.text}</Alert>}

        {list.summary.estimated_cost !== undefined && (list.summary.estimated_cost > 0 || list.summary.weekly_budget) && (
          <BudgetBar cost={list.summary.estimated_cost} budget={list.summary.weekly_budget ?? null} onSaved={() => call('budget', () => api<GroceryResponse>('/grocery'))} />
        )}

        {pending.length > 0 && (
          <div className="flex items-center gap-3 rounded-2xl bg-green-50 p-4">
            <p className="flex-1 text-sm font-semibold text-green-900">
              {t('Add {n} purchased food item(s) to your kitchen?', { n: pending.length })}
            </p>
            <button onClick={() => setConfirming(true)} className="rounded-full bg-leaf px-4 py-2 text-sm font-bold text-white">
              {t('Review')}
            </button>
          </div>
        )}

        <form onSubmit={add} className="flex gap-2">
          <input
            value={newItem}
            onChange={(e) => setNewItem(e.target.value)}
            placeholder={t('Add item, e.g. Soap')}
            className="flex-1 rounded-2xl border border-line bg-white px-4 py-3 outline-none focus:border-brand"
          />
          <button disabled={!newItem.trim() || !!busy} className="rounded-2xl bg-brand px-5 font-bold text-white disabled:opacity-50">
            {t('Add')}
          </button>
        </form>

        {list.data.length === 0 && (
          <EmptyState emoji="🛒" title={t('Your list is empty')}>
            {t('Plan some meals, then tap “Update from meal plan” — or add items yourself.')}
          </EmptyState>
        )}

        {[...groups].map(([category, items]) => (
          <section key={category} className="card p-2">
            <h2 className="px-3 pt-2 pb-1 text-xs font-bold uppercase tracking-wide text-muted">{category}</h2>
            <ul>
              {items.map((i) => (
                <li key={i.id} className="flex items-center gap-3 rounded-2xl px-3 py-2.5">
                  <button
                    onClick={() => toggle(i)}
                    disabled={!!i.added_to_pantry_at || busy === `item-${i.id}`}
                    aria-label={i.purchased ? t('Unmark {name}', { name: i.label }) : t('Mark {name} purchased', { name: i.label })}
                    className={`grid size-7 shrink-0 place-items-center rounded-full border-2 text-sm font-bold ${i.purchased ? 'border-leaf bg-leaf text-white' : 'border-line'}`}
                  >
                    {i.purchased && '✓'}
                  </button>
                  <button onClick={() => setOpenId(i.id)} className="flex min-w-0 flex-1 items-center justify-between gap-2 text-left">
                    <span className={`flex min-w-0 items-center gap-2 font-semibold ${i.purchased ? 'text-muted line-through opacity-70' : ''}`}>
                      <span className="text-xl" aria-hidden>
                        {i.ingredient?.display_icon ?? '🧴'}
                      </span>
                      <span className="min-w-0">
                        <span className="block truncate">{i.label}</span>
                        {i.packs && i.need_quantity !== null && !i.purchased && (
                          <span className="block text-xs font-medium text-muted">{t('plan needs {qty}', { qty: `${fmtQty(i.need_quantity)} ${unitLabel(lang, i.unit)}` })}</span>
                        )}
                      </span>
                    </span>
                    <span className="shrink-0 text-sm text-muted">
                      {i.actual_quantity ? `${fmtQty(i.actual_quantity)} ${unitLabel(lang, i.unit)}` : toBuy(lang, i)}
                      {i.price !== null && ` · ₹${fmtQty(i.price)}`}
                      {i.added_to_pantry_at && ` · ${t('in kitchen')}`}
                    </span>
                  </button>
                </li>
              ))}
            </ul>
          </section>
        ))}

        {list.data.some((i) => i.purchased) && (
          <button onClick={clear} disabled={!!busy} className="w-full py-2 text-sm font-bold text-muted">
            {t('Clear purchased items')}
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
            setFlash({ kind: 'success', text: t('Added to your kitchen.') })
          }}
        />
      )}
    </div>
  )
}

function ItemSheet({ item, onClose, onSaved }: { item: GroceryItem; onClose: () => void; onSaved: (r: GroceryResponse) => void }) {
  const { t, lang } = useI18n()
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
    <Sheet open onClose={onClose} title={item.label}>
      <form onSubmit={save} className="space-y-4">
        {error && <Alert kind="error">{error}</Alert>}
        {locked && <Alert kind="success">{t('Already added to your kitchen.')}</Alert>}
        <div className="grid grid-cols-2 gap-3">
          {input('quantity', t('Need') + (item.unit ? ` (${unitLabel(lang, item.unit)})` : ''), locked)}
          {input('actual_quantity', t('Bought') + (item.unit ? ` (${unitLabel(lang, item.unit)})` : ''), locked || !item.unit)}
        </div>
        {input('price', t('Price (₹)'))}
        <Button type="submit" loading={busy}>
          {t('Save')}
        </Button>
        {!locked && (
          <button type="button" onClick={() => run(() => api(`/grocery/items/${item.id}`, { method: 'DELETE' }))} className="w-full py-2 text-sm font-bold text-red-700">
            {t('Remove from list')}
          </button>
        )}
      </form>
    </Sheet>
  )
}

/** Before stock goes into the kitchen: how much really came home (whole packets, not the 2 tsp the recipe needed). */
function ConfirmPantrySheet({ items, onClose, onDone }: { items: GroceryItem[]; onClose: () => void; onDone: (r: GroceryResponse) => void }) {
  const { t, lang } = useI18n()
  const addable = items.filter((i) => (i.actual_quantity ?? i.quantity) && i.unit)
  const skipped = items.filter((i) => !addable.includes(i))
  const packOf = (i: GroceryItem) => (i.ingredient?.pack_quantity && i.ingredient.pack_label ? i.ingredient.pack_quantity : null)
  const [amounts, setAmounts] = useState<Record<number, number>>(() => Object.fromEntries(addable.map((i) => [i.id, i.actual_quantity ?? i.quantity ?? 0])))
  const [error, setError] = useState('')
  const [busy, setBusy] = useState(false)
  const setAmount = (id: number, v: number) => setAmounts({ ...amounts, [id]: Math.max(0, Math.round(v * 1000) / 1000) })

  async function confirm() {
    setBusy(true)
    setError('')
    try {
      const ready = addable.filter((i) => amounts[i.id] > 0)
      onDone(await api<GroceryResponse>('/grocery/add-to-pantry', { method: 'POST', body: { item_ids: ready.map((i) => i.id), quantities: Object.fromEntries(ready.map((i) => [i.id, amounts[i.id]])) } }))
    } catch (e) {
      setError((e as ApiError).message)
      setBusy(false)
    }
  }

  return (
    <Sheet open onClose={onClose} title={t('How much did you buy?')}>
      <div className="space-y-4">
        <p className="text-sm text-muted">{t('Check the amounts — you usually buy a whole packet, more than one recipe needs. The extra stays in your kitchen for next time.')}</p>
        {error && <Alert kind="error">{error}</Alert>}
        <ul className="divide-y divide-line rounded-2xl bg-white px-4">
          {addable.map((i) => {
            const pack = packOf(i)
            const packs = pack ? Math.round((amounts[i.id] / pack) * 100) / 100 : 0
            return (
              <li key={i.id} className="space-y-2 py-3">
                <div className="flex items-baseline justify-between gap-2">
                  <span className="font-semibold">
                    {i.ingredient?.display_icon} {i.label}
                  </span>
                  <span className="font-bold whitespace-nowrap text-leaf">
                    +{fmtQty(amounts[i.id])} {unitLabel(lang, i.unit)}
                  </span>
                </div>
                {pack ? (
                  <div className="flex items-center gap-2">
                    <button type="button" onClick={() => setAmount(i.id, Math.max(0, Math.ceil(packs) - 1) * pack)} aria-label={t('Fewer')} className="grid size-9 place-items-center rounded-full bg-cream text-lg font-bold">
                      −
                    </button>
                    <span className="min-w-28 text-center text-sm font-semibold">
                      {fmtQty(packs)} × {translate(lang, i.ingredient!.pack_label!)}
                    </span>
                    <button type="button" onClick={() => setAmount(i.id, (Math.floor(packs) + 1) * pack)} aria-label={t('More')} className="grid size-9 place-items-center rounded-full bg-cream text-lg font-bold">
                      +
                    </button>
                    {i.need_quantity !== null && <span className="ml-auto text-xs text-muted">{t('plan needs {qty}', { qty: `${fmtQty(i.need_quantity)} ${unitLabel(lang, i.unit)}` })}</span>}
                  </div>
                ) : (
                  <label className="flex items-center gap-2 text-sm text-muted">
                    {t('Bought')}
                    <input
                      type="number"
                      inputMode="decimal"
                      min="0"
                      step="any"
                      value={amounts[i.id] || ''}
                      onChange={(e) => setAmount(i.id, Number(e.target.value))}
                      className="w-24 rounded-xl border border-line px-3 py-1.5 text-ink"
                    />
                    {unitLabel(lang, i.unit)}
                  </label>
                )}
              </li>
            )
          })}
        </ul>
        {skipped.length > 0 && (
          <p className="text-sm text-muted">{t('Skipped (no quantity entered): {names}. Tap them in the list to add how much you bought.', { names: skipped.map((i) => i.label).join(', ') })}</p>
        )}
        {addable.length > 0 ? (
          <Button onClick={confirm} loading={busy} disabled={!addable.some((i) => amounts[i.id] > 0)}>
            {t('Add {n} item(s) to kitchen', { n: addable.filter((i) => amounts[i.id] > 0).length })}
          </Button>
        ) : (
          <Button onClick={onClose}>{t('OK')}</Button>
        )}
      </div>
    </Sheet>
  )
}

/** "≈ ₹850 to buy · budget ₹1,000": what's left on the list (usual shop prices) against the kitchen's weekly budget. */
function BudgetBar({ cost, budget, onSaved }: { cost: number; budget: number | null; onSaved: () => void }) {
  const { t } = useI18n()
  const { setUser } = useAuth()
  const [editing, setEditing] = useState(false)
  const [value, setValue] = useState(budget ? String(budget) : '')
  const [busy, setBusy] = useState(false)
  const over = budget !== null && cost > budget
  const share = budget ? Math.min(cost / budget, 1) : 0

  async function save(e: FormEvent) {
    e.preventDefault()
    setBusy(true)
    try {
      const r = await api<{ user: User }>('/profile', { method: 'PUT', body: { weekly_budget: value ? Number(value) : null } })
      setUser(r.user)
      setEditing(false)
      onSaved()
    } finally {
      setBusy(false)
    }
  }

  return (
    <div className={`rounded-2xl p-4 ${over ? 'bg-amber-50' : 'bg-white card'}`}>
      <div className="flex items-baseline justify-between gap-3">
        <p className="font-semibold">
          💰 {t('≈ ₹{n} to buy', { n: cost.toLocaleString('en-IN') })}
        </p>
        <button onClick={() => setEditing(!editing)} className="text-sm font-bold text-brand">
          {budget ? t('Budget ₹{b}', { b: budget.toLocaleString('en-IN') }) : t('Set weekly budget')} ✏️
        </button>
      </div>
      {budget !== null && (
        <>
          <div className="mt-2 h-2 overflow-hidden rounded-full bg-line">
            <div className={`h-full rounded-full ${over ? 'bg-accent' : 'bg-leaf'}`} style={{ width: `${share * 100}%` }} />
          </div>
          <p className={`mt-1.5 text-xs ${over ? 'font-semibold text-amber-900' : 'text-muted'}`}>
            {over ? t('₹{n} over budget — plan with “💰 Save money” to bring it down.', { n: (cost - budget).toLocaleString('en-IN') }) : t('₹{n} left in this week’s budget.', { n: (budget - cost).toLocaleString('en-IN') })}
          </p>
        </>
      )}
      <p className="mt-1 text-[11px] text-muted">{t('Estimated from usual shop prices; your shop may differ.')}</p>
      {editing && (
        <form onSubmit={save} className="mt-3 flex items-center gap-2">
          <span className="font-semibold">₹</span>
          <input autoFocus inputMode="numeric" value={value} onChange={(e) => setValue(e.target.value.replace(/\D/g, ''))} placeholder="1500" aria-label={t('Weekly grocery budget')} className="w-28 rounded-xl border border-line bg-white px-3 py-2" />
          <span className="text-sm text-muted">{t('per week')}</span>
          <button disabled={busy} className="ml-auto rounded-xl bg-brand px-4 py-2 text-sm font-semibold text-white disabled:opacity-50">
            {t('Save')}
          </button>
        </form>
      )}
    </div>
  )
}
