import { useEffect, useState } from 'react'
import { Link, useNavigate, useSearchParams } from 'react-router-dom'
import { api, ApiError, fmtQty, useApi, type PantryItem, type PantryView, type PlanNeed, type UnitInfo } from '../api'
import AddPantrySheet from '../components/AddPantrySheet'
import { AiPantrySheet } from '../components/AiSheets'
import PantryItemSheet, { StatusBadges } from '../components/PantryItemSheet'
import { IconTile } from '../components/RecipeCover'
import { Alert, EmptyState, ErrorState, Spinner } from '../components/ui'
import { nm, tk, unitLabel, useI18n } from '../i18n'

type View = PantryView | 'plan'
const views: { key: View; label: string }[] = [
  { key: 'all', label: tk('All') },
  { key: 'plan', label: tk('📅 For my plan') },
  { key: 'low_stock', label: tk('Low stock') },
  { key: 'expiring_soon', label: tk('Use soon') },
  { key: 'expired', label: tk('Expired') },
]

const emptyText: Record<PantryView, [string, string, string]> = {
  all: ['🧺', tk('Your kitchen is empty'), tk('Add what you have at home to see what you can cook.')],
  low_stock: ['👍', tk('Nothing running low'), tk('Set a minimum stock on items to get reminders here.')],
  expiring_soon: ['🌿', tk('Nothing expiring soon'), tk('Items expiring in the next 3 days show up here.')],
  expired: ['✨', tk('No expired items'), tk('Nice — nothing has gone past its date.')],
}

const locationIcon = { pantry: '🗄️', fridge: '🧊', freezer: '❄️' }

export default function KitchenPage() {
  const { t, lang } = useI18n()
  const [view, setView] = useState<View>('all')
  // "For my plan" has its own list; the shelf counts still come from the whole kitchen.
  const pantry = useApi<{ data: PantryItem[]; counts: Record<PantryView, number> }>(`/pantry?view=${view === 'plan' ? 'all' : view}`)
  const units = useApi<{ data: UnitInfo[] }>('/units').data?.data ?? []
  const [params] = useSearchParams()
  const [adding, setAdding] = useState(params.get('add') === '1') // ?add=1: opened from the welcome screen
  const [speaking, setSpeaking] = useState(false)
  const [openId, setOpenId] = useState<number | null>(null)
  const [flash, setFlash] = useState<{ kind: 'error' | 'success'; text: string } | null>(null)

  useEffect(() => {
    if (!flash) return
    const timer = setTimeout(() => setFlash(null), 3000)
    return () => clearTimeout(timer)
  }, [flash])

  const [writingOff, setWritingOff] = useState(false)
  async function writeOffExpired() {
    if (!confirm(t('Write off all expired stock? This records it as expired and sets those items to zero.'))) return
    setWritingOff(true)
    try {
      changed((await api<{ message: string }>('/pantry/discard-expired', { method: 'POST' })).message)
    } catch (e) {
      setFlash({ kind: 'error', text: (e as ApiError).message })
    } finally {
      setWritingOff(false)
    }
  }

  const changed = (message: string) => {
    setFlash({ kind: 'success', text: message })
    pantry.reload()
  }

  return (
    <div>
      <div className="flex flex-wrap items-center justify-between gap-x-3 gap-y-2">
        <h1 className="font-display text-[1.75rem] font-semibold tracking-tight">{t('Kitchen')}</h1>
        <div className="flex gap-2">
          <button onClick={() => setSpeaking(true)} className="rounded-full border border-line bg-white px-4 py-2 text-sm font-semibold text-brand whitespace-nowrap" aria-label={t('Add by voice')}>
            🎙️ {t('Speak')}
          </button>
          <button onClick={() => setAdding(true)} className="rounded-full bg-brand px-4 py-2 text-sm font-semibold text-white whitespace-nowrap">
            + {t('Add')}
          </button>
        </div>
      </div>

      <div className="-mx-4 mt-4 flex gap-2 overflow-x-auto px-4 pb-1">
        {views.map((v) => (
          <button
            key={v.key}
            onClick={() => setView(v.key)}
            className={`shrink-0 rounded-full px-4 py-2 text-sm font-bold ${view === v.key ? 'border border-ink bg-ink text-white' : 'border border-line bg-white text-muted'}`}
          >
            {t(v.label)}
            {pantry.data && v.key !== 'plan' && <span className="ml-1 opacity-70">{pantry.data.counts[v.key]}</span>}
          </button>
        ))}
      </div>

      <div className="mt-4 space-y-2">
        {flash && <Alert kind={flash.kind}>{flash.text}</Alert>}
        {view === 'expired' && pantry.data?.data.some((i) => i.quantity > 0) && (
          <button onClick={writeOffExpired} disabled={writingOff} className="w-full rounded-2xl border border-red-200 bg-white py-3 text-sm font-bold text-red-700 disabled:opacity-60">
            {writingOff ? t('Writing off…') : t('Write off all expired stock')}
          </button>
        )}
        {view === 'plan' && <PlanNeeds />}
        {view !== 'plan' && pantry.loading && !pantry.data && <Spinner label={t('Checking your shelves…')} />}
        {pantry.error && <ErrorState message={pantry.error} onRetry={pantry.reload} />}
        {view !== 'plan' && pantry.data && pantry.data.data.length === 0 && (
          <EmptyState emoji={emptyText[view][0]} art={view === 'all' ? 'jars' : undefined} title={t(emptyText[view][1])}>
            {t(emptyText[view][2])}
          </EmptyState>
        )}
        {view !== 'plan' && pantry.data?.data.map((item) => (
          <button
            key={item.id}
            onClick={() => setOpenId(item.id)}
            className="flex w-full items-center gap-3 card p-4 text-left active:scale-[.99]"
          >
            <IconTile icon={item.ingredient.display_icon} className="size-12 text-2xl" />
            <div className="min-w-0 flex-1">
              <p className="truncate font-semibold">{nm(item.ingredient)}</p>
              <div className="mt-0.5 flex flex-wrap gap-1.5">
                <StatusBadges item={item} />
                {!item.expiry_status && !item.is_low_stock && (
                  <span className="text-xs text-muted">
                    {item.storage_location ? `${locationIcon[item.storage_location]} ${t(item.storage_location)}` : nm(item.ingredient.category)}
                  </span>
                )}
              </div>
            </div>
            <p className={`shrink-0 text-lg font-semibold ${item.quantity === 0 ? 'text-muted' : ''}`}>
              {fmtQty(item.quantity)} <span className="text-sm font-semibold text-muted">{unitLabel(lang, item.unit)}</span>
            </p>
          </button>
        ))}
      </div>

      {speaking && (
        <AiPantrySheet
          onClose={() => setSpeaking(false)}
          onDone={(m) => {
            setSpeaking(false)
            changed(m)
          }}
        />
      )}
      <AddPantrySheet open={adding} onClose={() => setAdding(false)} onSaved={changed} units={units} />
      <PantryItemSheet itemId={openId} onClose={() => setOpenId(null)} onChanged={changed} units={units} />
    </div>
  )
}

const needStyle = {
  missing: { icon: '❌', badge: 'bg-red-50 text-red-800', label: tk('Missing') },
  short: { icon: '⚠️', badge: 'bg-amber-50 text-amber-900', label: tk('Not enough') },
  enough: { icon: '✅', badge: 'bg-green-50 text-green-800', label: tk('Enough') },
}

/** What the next 7 days of planned meals need, against what's on the shelves. */
function PlanNeeds() {
  const { t, lang } = useI18n()
  const navigate = useNavigate()
  const res = useApi<{ data: PlanNeed[]; meals: number; summary: Partial<Record<PlanNeed['status'], number>> }>('/pantry/plan')
  const [busy, setBusy] = useState(false)

  if (res.loading && !res.data) return <Spinner label={t('Checking your plan…')} />
  if (!res.data) return <ErrorState message={res.error ?? ''} onRetry={res.reload} />
  const { data: rows, meals, summary } = res.data
  if (meals === 0) {
    return (
      <EmptyState art="tiffin" title={t('No meals planned for the next 7 days')}>
        {t('Plan your week and this shows exactly what you need from the kitchen.')}{' '}
        <Link to="/planner" className="font-bold text-brand">{t('Open planner')} →</Link>
      </EmptyState>
    )
  }
  const toBuy = (summary.missing ?? 0) + (summary.short ?? 0)

  async function addMissing() {
    setBusy(true)
    try {
      await api('/grocery/generate', { method: 'POST' })
      navigate('/groceries')
    } finally {
      setBusy(false)
    }
  }

  return (
    <div className="space-y-2">
      <div className="card p-4">
        <p className="font-semibold">{t('Your {n} planned meals for the next 7 days need {count} ingredients.', { n: meals, count: rows.length })}</p>
        <p className="mt-1 flex flex-wrap gap-x-3 text-sm text-muted">
          <span>✅ {t('{n} enough', { n: summary.enough ?? 0 })}</span>
          <span>⚠️ {t('{n} not enough', { n: summary.short ?? 0 })}</span>
          <span>❌ {t('{n} missing', { n: summary.missing ?? 0 })}</span>
        </p>
        {toBuy > 0 && (
          <button onClick={addMissing} disabled={busy} className="mt-3 w-full rounded-xl bg-brand py-2.5 text-sm font-semibold text-white disabled:opacity-60">
            🛒 {t('Put the {n} missing items on my grocery list', { n: toBuy })}
          </button>
        )}
      </div>
      {rows.map((r) => (
        <div key={r.ingredient.id} className="card flex items-center gap-3 p-4">
          <IconTile icon={r.ingredient.display_icon} className="size-11 text-xl" />
          <div className="min-w-0 flex-1">
            <p className="flex items-center gap-2 font-semibold">
              <span className="truncate">{nm(r.ingredient)}</span>
              <span className={`shrink-0 rounded-full px-2 py-0.5 text-xs font-bold ${needStyle[r.status].badge}`}>
                {needStyle[r.status].icon} {t(needStyle[r.status].label)}
              </span>
            </p>
            <p className="truncate text-xs text-muted">{t('For: {dishes}', { dishes: r.used_in.join(', ') })}</p>
          </div>
          <p className="shrink-0 text-right text-sm">
            <span className="block font-semibold">
              {t('need {qty}', { qty: `${fmtQty(r.need)} ${unitLabel(lang, r.unit)}` })}
            </span>
            <span className="block text-muted">{t('have {qty}', { qty: `${fmtQty(r.have)} ${unitLabel(lang, r.unit)}` })}</span>
          </p>
        </div>
      ))}
    </div>
  )
}
