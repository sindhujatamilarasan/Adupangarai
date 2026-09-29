import { useState } from 'react'
import { api, ApiError, fmtQty, useApi, type CookRow } from '../api'
import { unitLabel, useI18n } from '../i18n'
import { Alert, Button, ErrorState, Sheet, Spinner } from './ui'

type Props = { recipeId: number; servings: number; mealPlanId: number | null; onClose: () => void; onCooked: (message: string) => void }

export default function CookSheet({ recipeId, servings, mealPlanId, onClose, onCooked }: Props) {
  const { t, lang } = useI18n()
  const preview = useApi<{ data: CookRow[] }>(`/recipes/${recipeId}/cook?servings=${servings}`)
  const [error, setError] = useState('')
  const [busy, setBusy] = useState(false)
  const rows = preview.data?.data ?? []
  const deducted = rows.filter((r) => r.deduct > 0)
  const short = rows.filter((r) => r.short > 0 && !r.optional)

  async function confirm() {
    setBusy(true)
    setError('')
    try {
      const r = await api<{ message: string }>(`/recipes/${recipeId}/cook`, { method: 'POST', body: { servings, meal_plan_id: mealPlanId } })
      onCooked(r.message)
    } catch (e) {
      setError((e as ApiError).message)
      setBusy(false)
    }
  }

  return (
    <Sheet open onClose={onClose} title={t('Cook for {n}', { n: servings })}>
      {preview.loading && !preview.data && <Spinner label={t('Checking your kitchen…')} />}
      {preview.error && <ErrorState message={preview.error} onRetry={preview.reload} />}
      {preview.data && (
        <div className="space-y-4">
          {error && <Alert kind="error">{error}</Alert>}
          <p className="text-sm text-muted">{t('This will be taken out of your kitchen:')}</p>
          {deducted.length === 0 ? (
            <p className="rounded-2xl border border-line bg-white p-4 text-sm">{t('Nothing from your kitchen is used — none of these ingredients are in stock.')}</p>
          ) : (
            <ul className="divide-y divide-line rounded-2xl bg-white px-4">
              {deducted.map((r) => (
                <li key={r.ingredient_id} className="flex items-center justify-between py-3">
                  <span className="font-semibold">{r.name}</span>
                  <span className="text-right">
                    <span className="block font-bold text-red-700">
                      −{fmtQty(r.deduct)} {unitLabel(lang, r.pantry_unit)}
                    </span>
                    <span className="block text-xs text-muted">
                      {t('{n} left', { n: `${fmtQty(r.pantry_after ?? 0)} ${unitLabel(lang, r.pantry_unit)}` })}
                    </span>
                  </span>
                </li>
              ))}
            </ul>
          )}
          {short.length > 0 && (
            <div className="rounded-2xl bg-amber-50 p-4 text-sm text-amber-900">
              <p className="font-bold">{t('Not enough in your kitchen:')}</p>
              <p>{short.map((r) => `${r.name} (${t('short {n}', { n: `${fmtQty(r.short)} ${unitLabel(lang, r.unit)}` })})`).join(', ')}</p>
              <p className="mt-1 text-xs">{t('Only what you have will be deducted — stock never goes below zero.')}</p>
            </div>
          )}
          <Button onClick={confirm} loading={busy}>
            {t('Confirm — I cooked this')}
          </Button>
        </div>
      )}
    </Sheet>
  )
}
