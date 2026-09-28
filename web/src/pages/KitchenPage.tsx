import { useEffect, useState } from 'react'
import { api, ApiError, fmtQty, useApi, type PantryItem, type PantryView, type UnitInfo } from '../api'
import AddPantrySheet from '../components/AddPantrySheet'
import PantryItemSheet, { StatusBadges } from '../components/PantryItemSheet'
import { Alert, EmptyState, ErrorState, Spinner } from '../components/ui'

const views: { key: PantryView; label: string }[] = [
  { key: 'all', label: 'All' },
  { key: 'low_stock', label: 'Low stock' },
  { key: 'expiring_soon', label: 'Use soon' },
  { key: 'expired', label: 'Expired' },
]

const emptyText: Record<PantryView, [string, string, string]> = {
  all: ['🧺', 'Your kitchen is empty', 'Add what you have at home to see what you can cook.'],
  low_stock: ['👍', 'Nothing running low', 'Set a minimum stock on items to get reminders here.'],
  expiring_soon: ['🌿', 'Nothing expiring soon', 'Items expiring in the next 3 days show up here.'],
  expired: ['✨', 'No expired items', 'Nice — nothing has gone past its date.'],
}

const locationIcon = { pantry: '🗄️', fridge: '🧊', freezer: '❄️' }

export default function KitchenPage() {
  const [view, setView] = useState<PantryView>('all')
  const pantry = useApi<{ data: PantryItem[]; counts: Record<PantryView, number> }>(`/pantry?view=${view}`)
  const units = useApi<{ data: UnitInfo[] }>('/units').data?.data ?? []
  const [adding, setAdding] = useState(false)
  const [openId, setOpenId] = useState<number | null>(null)
  const [flash, setFlash] = useState('')

  useEffect(() => {
    if (!flash) return
    const t = setTimeout(() => setFlash(''), 3000)
    return () => clearTimeout(t)
  }, [flash])

  const [writingOff, setWritingOff] = useState(false)
  async function writeOffExpired() {
    if (!confirm('Write off all expired stock? This records it as expired and sets those items to zero.')) return
    setWritingOff(true)
    try {
      changed((await api<{ message: string }>('/pantry/discard-expired', { method: 'POST' })).message)
    } catch (e) {
      setFlash((e as ApiError).message)
    } finally {
      setWritingOff(false)
    }
  }

  const changed = (message: string) => {
    setFlash(message)
    pantry.reload()
  }

  return (
    <div>
      <div className="flex items-center justify-between">
        <h1 className="text-2xl font-extrabold">Kitchen</h1>
        <button onClick={() => setAdding(true)} className="rounded-full bg-brand px-4 py-2 font-bold text-white shadow-sm">
          + Add
        </button>
      </div>

      <div className="-mx-4 mt-4 flex gap-2 overflow-x-auto px-4 pb-1">
        {views.map((v) => (
          <button
            key={v.key}
            onClick={() => setView(v.key)}
            className={`shrink-0 rounded-full px-4 py-2 text-sm font-bold ${view === v.key ? 'bg-ink text-white' : 'bg-white text-muted'}`}
          >
            {v.label}
            {pantry.data && <span className="ml-1 opacity-70">{pantry.data.counts[v.key]}</span>}
          </button>
        ))}
      </div>

      <div className="mt-4 space-y-2">
        {flash && <Alert kind="success">{flash}</Alert>}
        {view === 'expired' && pantry.data?.data.some((i) => i.quantity > 0) && (
          <button onClick={writeOffExpired} disabled={writingOff} className="w-full rounded-2xl border border-red-200 bg-white py-3 text-sm font-bold text-red-700 disabled:opacity-60">
            {writingOff ? 'Writing off…' : 'Write off all expired stock'}
          </button>
        )}
        {pantry.loading && !pantry.data && <Spinner label="Checking your shelves…" />}
        {pantry.error && <ErrorState message={pantry.error} onRetry={pantry.reload} />}
        {pantry.data && pantry.data.data.length === 0 && (
          <EmptyState emoji={emptyText[view][0]} title={emptyText[view][1]}>
            {emptyText[view][2]}
          </EmptyState>
        )}
        {pantry.data?.data.map((item) => (
          <button
            key={item.id}
            onClick={() => setOpenId(item.id)}
            className="flex w-full items-center gap-3 rounded-2xl bg-white p-4 text-left shadow-sm active:scale-[.99]"
          >
            <span className="text-2xl" aria-hidden>
              {item.storage_location ? locationIcon[item.storage_location] : '🥄'}
            </span>
            <div className="min-w-0 flex-1">
              <p className="truncate font-bold">{item.ingredient.name}</p>
              <div className="mt-0.5 flex flex-wrap gap-1.5">
                <StatusBadges item={item} />
                {!item.expiry_status && !item.is_low_stock && <span className="text-xs text-muted">{item.ingredient.category.name}</span>}
              </div>
            </div>
            <p className={`shrink-0 text-lg font-extrabold ${item.quantity === 0 ? 'text-muted' : ''}`}>
              {fmtQty(item.quantity)} <span className="text-sm font-semibold text-muted">{item.unit}</span>
            </p>
          </button>
        ))}
      </div>

      <AddPantrySheet open={adding} onClose={() => setAdding(false)} onSaved={changed} units={units} />
      <PantryItemSheet itemId={openId} onClose={() => setOpenId(null)} onChanged={changed} units={units} />
    </div>
  )
}
