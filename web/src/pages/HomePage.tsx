import type { ReactNode } from 'react'
import { Link } from 'react-router-dom'
import { fmtQty, useApi, type Dashboard } from '../api'
import { useAuth } from '../auth'
import { nm, unitLabel, useI18n } from '../i18n'
import { Badge, ErrorState, Spinner } from '../components/ui'
import Kolam from '../components/Kolam'
import RecipeCover, { IconTile } from '../components/RecipeCover'
import { VegDot } from './RecipesPage'

function Card({ title, link, linkLabel, children }: { title: string; link?: string; linkLabel?: string; children: ReactNode }) {
  const { t } = useI18n()
  return (
    <section className="card p-5">
      <div className="mb-2 flex items-center justify-between">
        <h2 className="font-display text-lg font-semibold">{title}</h2>
        {link && (
          <Link to={link} className="text-sm font-bold text-brand">
            {linkLabel ?? t('See all')} →
          </Link>
        )}
      </div>
      {children}
    </section>
  )
}

const muted = (text: string) => <p className="py-1 text-sm text-muted">{text}</p>

export default function HomePage() {
  const { user } = useAuth()
  const { t, lang } = useI18n()
  const days = (n: number | null) => (n === 0 ? t('Expires today') : n === 1 ? t('Expires tomorrow') : t('Expires in {n} days', { n: n ?? 0 }))
  const res = useApi<Dashboard>('/dashboard')
  const d = res.data
  const hour = new Date().getHours()
  const greeting = t(hour < 12 ? 'Good morning' : hour < 17 ? 'Good afternoon' : 'Good evening')

  return (
    <div className="space-y-4">
      <header className="relative overflow-hidden pt-2 pb-1">
        <Kolam classic className="pointer-events-none absolute -top-3 -right-4 size-40 text-brand opacity-[.13]" strokeWidth={0.05} />
        <p className="text-sm text-muted">{greeting}</p>
        <h1 className="font-display text-[2rem] leading-tight font-semibold tracking-tight">{user!.name}</h1>
        <p className="mt-0.5 text-sm text-muted">{user!.household.name}</p>
      </header>

      {res.loading && !d && <Spinner label={t('Checking your kitchen…')} />}
      {res.error && !d && <ErrorState message={res.error} onRetry={res.reload} />}

      {d && d.pantry_count === 0 && (
        <Link to="/kitchen" className="block rounded-[1.25rem] bg-brand p-5 text-white">
          <p className="text-lg font-semibold">{t('Start by stocking your kitchen')} 🧺</p>
          <p className="text-sm opacity-90">{t('Add what you have at home and we’ll show what you can cook.')}</p>
        </Link>
      )}

      {d && (
        <>
          <Card title={t('Today’s meals')} link="/planner" linkLabel={t('Planner')}>
            {d.today_meals.length === 0
              ? muted(t('Nothing planned for today.'))
              : (
                <ul className="divide-y divide-line">
                  {d.today_meals.map((m) => (
                    <li key={m.id}>
                      <Link to={`/recipes/${m.recipe.id}?servings=${m.servings}&plan=${m.id}`} className="flex items-center gap-3 py-2.5">
                        <RecipeCover recipe={m.recipe} className="size-12 shrink-0 rounded-xl" />
                        <span className="min-w-0 flex-1">
                          <span className="block text-xs font-semibold tracking-wide text-muted uppercase">{t(m.meal_type)}</span>
                          <span className="flex items-center gap-1.5 font-semibold">
                            <VegDot veg={m.recipe.is_veg} />
                            <span className="truncate">{nm(m.recipe)}</span>
                          </span>
                        </span>
                        {m.cooked_at ? <Badge color="green">{t('Cooked')}</Badge> : m.can_cook_now ? <Badge color="green">{t('Ready')}</Badge> : <Badge color="amber">{t('Missing items')}</Badge>}
                      </Link>
                    </li>
                  ))}
                </ul>
              )}
          </Card>

          <Card title={t('What can I cook now?')} link="/cook">
            {d.cook_now.length === 0
              ? muted(d.almost_count > 0 ? t('Nothing fully ready — {n} recipe(s) almost there.', { n: d.almost_count }) : t('Add more to your kitchen to unlock recipes.'))
              : (
                <div className="-mx-4 flex gap-3 overflow-x-auto px-4 pb-1">
                  {d.cook_now.map(({ recipe }) => (
                    <Link key={recipe.id} to={`/recipes/${recipe.id}`} className="w-36 shrink-0">
                      <RecipeCover recipe={recipe} framed className="h-24 w-36 rounded-2xl" />
                      <span className="mt-1.5 flex items-center gap-1.5 text-sm font-bold">
                        <VegDot veg={recipe.is_veg} />
                        <span className="truncate">{nm(recipe)}</span>
                      </span>
                      <span className="block text-xs text-muted">
                        {t('{n} min', { n: recipe.total_time })}{recipe.calories !== null && ` · ${recipe.calories} kcal`}
                      </span>
                    </Link>
                  ))}
                </div>
              )}
          </Card>

          {(d.expiring.length > 0 || d.expired_count > 0) && (
            <Card title={t('Use soon')} link="/kitchen" linkLabel={t('Kitchen')}>
              <ul className="space-y-1.5">
                {d.expiring.map((i) => (
                  <li key={i.id} className="flex items-center justify-between gap-2 text-sm">
                    <span className="flex items-center gap-2 font-semibold">
                      <IconTile icon={i.ingredient.display_icon} className="size-8 text-base" />
                      {nm(i.ingredient)} <span className="text-muted">· {fmtQty(i.quantity)} {unitLabel(lang, i.unit)}</span>
                    </span>
                    <Badge color="amber">{days(i.days_to_expiry)}</Badge>
                  </li>
                ))}
              </ul>
              {d.expired_count > 0 && (
                <p className="mt-2 text-sm font-semibold text-red-700">
                  {t('{n} item(s) have expired.', { n: d.expired_count })}
                </p>
              )}
              {d.use_soon.length > 0 && (
                <div className="mt-3 border-t border-line pt-3">
                  <p className="mb-2 text-xs font-semibold tracking-wide text-muted uppercase">{t('Cook these first')}</p>
                  <div className="flex flex-wrap gap-2">
                    {d.use_soon.map(({ recipe, match }) => (
                      <Link key={recipe.id} to={`/recipes/${recipe.id}`} className="rounded-full bg-amber-50 px-3 py-2 text-sm font-bold text-amber-900">
                        {nm(recipe)} · {match.match_percent}%
                      </Link>
                    ))}
                  </div>
                </div>
              )}
            </Card>
          )}

          <div className="grid grid-cols-2 gap-4">
            <Link to="/kitchen" className="card p-5">
              <p className="text-3xl font-semibold">{d.low_stock.length}</p>
              <p className="text-sm font-semibold text-muted">{t('Low stock')}</p>
              {d.low_stock.length > 0 && <p className="mt-1 truncate text-xs text-muted">{d.low_stock.map((i) => nm(i.ingredient)).join(', ')}</p>}
            </Link>
            <Link to="/groceries" className="card p-5">
              <p className="text-3xl font-semibold">{d.grocery_remaining}</p>
              <p className="text-sm font-semibold text-muted">{t('Groceries to buy')}</p>
            </Link>
          </div>
        </>
      )}
    </div>
  )
}
