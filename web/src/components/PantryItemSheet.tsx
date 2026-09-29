import { useState, type FormEvent } from 'react'
import { api, ApiError, fmtQty, useApi, type PantryItem, type PantryTransaction, type UnitInfo } from '../api'
import { nm, tk, unitLabel, useI18n } from '../i18n'
import { Alert, Badge, Button, ErrorState, Field, Select, Sheet, Spinner } from './ui'

const actions = [
  { type: 'ADD', label: tk('Add') },
  { type: 'DISCARDED', label: tk('Discard') },
  { type: 'EXPIRED', label: tk('Expired') },
  { type: 'ADJUSTMENT', label: tk('Set count') },
] as const

const txLabels: Record<PantryTransaction['type'], string> = {
  PURCHASE: tk('Bought'),
  ADD: tk('Added'),
  COOKED: tk('Cooked'),
  ADJUSTMENT: tk('Stock count'),
  EXPIRED: tk('Expired'),
  DISCARDED: tk('Discarded'),
}

export function StatusBadges({ item }: { item: PantryItem }) {
  const { t } = useI18n()
  const d = item.days_to_expiry
  return (
    <>
      {item.expiry_status === 'expired' && <Badge color="red">{t('Expired')}</Badge>}
      {item.expiry_status === 'expiring_soon' && (
        <Badge color="amber">{d === 0 ? t('Expires today') : d === 1 ? t('Expires tomorrow') : t('{n} days left', { n: d ?? 0 })}</Badge>
      )}
      {item.is_low_stock && <Badge color="gray">{t('Low stock')}</Badge>}
    </>
  )
}

type Props = { itemId: number | null; onClose: () => void; onChanged: (message: string) => void; units: UnitInfo[] }

export default function PantryItemSheet(props: Props) {
  const res = useApi<{ data: PantryItem; transactions: PantryTransaction[] }>(props.itemId ? `/pantry/${props.itemId}` : null)
  const item = res.data?.data?.id === props.itemId ? res.data.data : undefined
  const { t } = useI18n()

  return (
    <Sheet open={props.itemId !== null} onClose={props.onClose} title={item ? nm(item.ingredient) : t('Item')}>
      {!item && res.loading && <Spinner />}
      {!item && res.error && <ErrorState message={res.error} onRetry={res.reload} />}
      {item && <ItemBody key={item.id} {...props} item={item} transactions={res.data!.transactions} reload={res.reload} />}
    </Sheet>
  )
}

type BodyProps = Props & { item: PantryItem; transactions: PantryTransaction[]; reload: () => void }

function ItemBody({ itemId, onClose, onChanged, units, item, transactions, reload }: BodyProps) {
  const { t, lang } = useI18n()
  const [action, setAction] = useState<(typeof actions)[number]['type']>('ADD')
  const [move, setMove] = useState({ quantity: '', unit: item.unit as string, note: '' })
  const [details, setDetails] = useState({
    expiry_date: item.expiry_date ?? '',
    minimum_stock: item.minimum_stock === null ? '' : fmtQty(item.minimum_stock),
    storage_location: item.storage_location ?? '',
  })
  const [errors, setErrors] = useState<Record<string, string[]>>({})
  const [status, setStatus] = useState<{ kind: 'error' | 'success'; text: string } | null>(null)
  const [busy, setBusy] = useState(false)

  const dim = units.find((u) => u.value === item.unit)?.dimension
  const unitOptions = units.filter((u) => u.dimension === dim)

  async function run(fn: () => Promise<unknown>, success: string) {
    setBusy(true)
    setErrors({})
    setStatus(null)
    try {
      await fn()
      setStatus({ kind: 'success', text: success })
      reload()
      onChanged(success)
    } catch (err) {
      const e = err as ApiError
      setErrors(e.errors ?? {})
      setStatus({ kind: 'error', text: e.message })
    } finally {
      setBusy(false)
    }
  }

  function submitMove(e: FormEvent) {
    e.preventDefault()
    run(
      () =>
        api(`/pantry/${itemId}/adjust`, {
          method: 'POST',
          body: { type: action, quantity: Number(move.quantity), unit: move.unit, note: move.note || null },
        }),
      t('Stock updated.'),
    ).then(() => setMove((m) => ({ ...m, quantity: '', note: '' })))
  }

  function submitDetails(e: FormEvent) {
    e.preventDefault()
    run(
      () =>
        api(`/pantry/${itemId}`, {
          method: 'PATCH',
          body: {
            expiry_date: details.expiry_date || null,
            minimum_stock: details.minimum_stock === '' ? null : Number(details.minimum_stock),
            storage_location: details.storage_location || null,
          },
        }),
      t('Details saved.'),
    )
  }

  async function remove() {
    if (!confirm(t('Remove {name} from your kitchen?', { name: nm(item.ingredient) }))) return
    setBusy(true)
    try {
      await api(`/pantry/${itemId}`, { method: 'DELETE' })
      onChanged(t('{name} removed.', { name: nm(item.ingredient) }))
      onClose()
    } catch (e) {
      setStatus({ kind: 'error', text: (e as ApiError).message })
    } finally {
      setBusy(false)
    }
  }

  return (
    <div className="space-y-5">
      <div className="card p-5 text-center">
        <p className="text-4xl font-semibold">
          {fmtQty(item.quantity)} <span className="text-xl text-muted">{unitLabel(lang, item.unit)}</span>
        </p>
        <div className="mt-2 flex flex-wrap justify-center gap-1.5">
          <StatusBadges item={item} />
        </div>
      </div>

      {status && <Alert kind={status.kind}>{status.text}</Alert>}

      <form onSubmit={submitMove} className="space-y-3 card p-5" noValidate>
        <div className="grid grid-cols-4 gap-1 rounded-2xl bg-cream p-1">
          {actions.map((a) => (
            <button
              key={a.type}
              type="button"
              onClick={() => setAction(a.type)}
              className={`rounded-xl py-2 text-xs font-bold ${action === a.type ? 'bg-brand text-white' : 'text-muted'}`}
            >
              {t(a.label)}
            </button>
          ))}
        </div>
        <div className="grid grid-cols-[1fr_6rem] gap-3">
          <Field
            label={action === 'ADJUSTMENT' ? t('Actual amount now') : t('Quantity')}
            type="number"
            inputMode="decimal"
            min="0"
            step="any"
            value={move.quantity}
            onChange={(e) => setMove({ ...move, quantity: e.target.value })}
            error={errors.quantity?.[0]}
          />
          <Select label={t('Unit')} value={move.unit} onChange={(e) => setMove({ ...move, unit: e.target.value })} error={errors.unit?.[0]}>
            {unitOptions.map((u) => (
              <option key={u.value} value={u.value}>{unitLabel(lang, u.value)}</option>
            ))}
          </Select>
        </div>
        <Field label={t('Note (optional)')} value={move.note} onChange={(e) => setMove({ ...move, note: e.target.value })} />
        <Button type="submit" loading={busy} disabled={move.quantity === ''}>
          {t('Update stock')}
        </Button>
      </form>

      <form onSubmit={submitDetails} className="space-y-3 card p-5" noValidate>
        <p className="font-display text-lg font-semibold">{t('Details')}</p>
        <Field
          label={t('Expiry date')}
          type="date"
          value={details.expiry_date}
          onChange={(e) => setDetails({ ...details, expiry_date: e.target.value })}
          error={errors.expiry_date?.[0]}
        />
        <div className="grid grid-cols-2 gap-3">
          <Field
            label={t('Min. stock ({unit})', { unit: unitLabel(lang, item.unit) })}
            type="number"
            inputMode="decimal"
            min="0"
            step="any"
            value={details.minimum_stock}
            onChange={(e) => setDetails({ ...details, minimum_stock: e.target.value })}
            error={errors.minimum_stock?.[0]}
          />
          <Select label={t('Stored in')} value={details.storage_location} onChange={(e) => setDetails({ ...details, storage_location: e.target.value })}>
            <option value="">—</option>
            <option value="pantry">{t('pantry')}</option>
            <option value="fridge">{t('fridge')}</option>
            <option value="freezer">{t('freezer')}</option>
          </Select>
        </div>
        <button type="submit" disabled={busy} className="w-full rounded-2xl border border-brand py-3 font-bold text-brand disabled:opacity-60">
          {t('Save details')}
        </button>
      </form>

      <div className="card p-5">
        <p className="mb-2 font-display text-lg font-semibold">{t('History')}</p>
        {transactions.length === 0 ? (
          <p className="text-sm text-muted">{t('No stock changes yet.')}</p>
        ) : (
          <ul className="divide-y divide-line text-sm">
            {transactions.map((tx) => (
              <li key={tx.id} className="flex items-center justify-between py-2">
                <div>
                  <p className="font-semibold">{t(txLabels[tx.type])}</p>
                  <p className="text-xs text-muted">
                    {new Date(tx.created_at).toLocaleString(lang === 'ta' ? 'ta-IN' : 'en-IN', { dateStyle: 'medium', timeStyle: 'short' })}
                    {tx.note && ` · ${tx.note}`}
                  </p>
                </div>
                <div className="text-right">
                  <p className={`font-bold ${tx.quantity_change < 0 ? 'text-red-700' : 'text-leaf'}`}>
                    {tx.quantity_change > 0 ? '+' : ''}
                    {fmtQty(tx.quantity_change)} {unitLabel(lang, tx.unit)}
                  </p>
                  <p className="text-xs text-muted">
                    → {fmtQty(tx.balance_after)} {unitLabel(lang, tx.unit)}
                  </p>
                </div>
              </li>
            ))}
          </ul>
        )}
      </div>

      <button onClick={remove} disabled={busy} className="w-full py-2 text-sm font-bold text-red-700">
        {t('Remove from kitchen')}
      </button>
    </div>
  )
}
