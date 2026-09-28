import type { ReactNode } from 'react'
import { Link } from 'react-router-dom'
import { fmtQty, useApi, type Dashboard } from '../api'
import { useAuth } from '../auth'
import { Badge, ErrorState, Spinner } from '../components/ui'
import { VegDot } from './RecipesPage'

function Card({ title, link, linkLabel, children }: { title: string; link?: string; linkLabel?: string; children: ReactNode }) {
  return (
    <section className="rounded-3xl bg-white p-4 shadow-sm">
      <div className="mb-2 flex items-center justify-between">
        <h2 className="font-extrabold">{title}</h2>
        {link && (
          <Link to={link} className="text-sm font-bold text-brand">
            {linkLabel ?? 'See all'} →
          </Link>
        )}
      </div>
      {children}
    </section>
  )
}

const muted = (text: string) => <p className="py-1 text-sm text-muted">{text}</p>
const days = (d: number | null) => (d === 0 ? 'today' : d === 1 ? 'tomorrow' : `in ${d} days`)

export default function HomePage() {
  const { user } = useAuth()
  const res = useApi<Dashboard>('/dashboard')
  const d = res.data
  const hour = new Date().getHours()
  const greeting = hour < 12 ? 'Good morning' : hour < 17 ? 'Good afternoon' : 'Good evening'

  return (
    <div className="space-y-4">
      <header className="flex items-center justify-between">
        <div>
          <p className="text-sm text-muted">{greeting},</p>
          <h1 className="text-2xl font-extrabold">{user!.name} 👋</h1>
          <p className="text-sm font-semibold text-brand">{user!.household.name}</p>
        </div>
        <div className="flex items-center gap-2">
          <Link to="/ai" className="rounded-full bg-gradient-to-br from-brand to-amber-500 px-4 py-2.5 text-sm font-extrabold text-white shadow-sm">
            ✨ AI
          </Link>
          <Link to="/profile" aria-label="Profile" className="grid size-11 place-items-center rounded-full bg-brand text-lg font-bold text-white">
            {user!.name[0]?.toUpperCase()}
          </Link>
        </div>
      </header>

      {res.loading && !d && <Spinner label="Checking your kitchen…" />}
      {res.error && !d && <ErrorState message={res.error} onRetry={res.reload} />}

      {d && d.pantry_count === 0 && (
        <Link to="/kitchen" className="block rounded-3xl bg-brand p-5 text-white shadow-sm">
          <p className="text-lg font-extrabold">Start by stocking your kitchen 🧺</p>
          <p className="text-sm opacity-90">Add what you have at home and we’ll show what you can cook.</p>
        </Link>
      )}

      {d && (
        <>
          <Card title="Today’s meals" link="/planner" linkLabel="Planner">
            {d.today_meals.length === 0
              ? muted('Nothing planned for today.')
              : (
                <ul className="divide-y divide-line">
                  {d.today_meals.map((m) => (
                    <li key={m.id}>
                      <Link to={`/recipes/${m.recipe.id}?servings=${m.servings}&plan=${m.id}`} className="flex items-center gap-2 py-2.5">
                        <span className="w-20 text-xs font-bold uppercase text-muted">{m.meal_type}</span>
                        <VegDot veg={m.recipe.is_veg} />
                        <span className="flex-1 truncate font-semibold">{m.recipe.name}</span>
                        {m.cooked_at ? <Badge color="green">Cooked</Badge> : m.can_cook_now ? <Badge color="green">Ready</Badge> : <Badge color="amber">Missing items</Badge>}
                      </Link>
                    </li>
                  ))}
                </ul>
              )}
          </Card>

          <Card title="What can I cook now?" link="/cook">
            {d.cook_now.length === 0
              ? muted(d.almost_count > 0 ? `Nothing fully ready — ${d.almost_count} recipe${d.almost_count > 1 ? 's are' : ' is'} almost there.` : 'Add more to your kitchen to unlock recipes.')
              : (
                <div className="flex flex-wrap gap-2">
                  {d.cook_now.map(({ recipe }) => (
                    <Link key={recipe.id} to={`/recipes/${recipe.id}`} className="flex items-center gap-1.5 rounded-full bg-green-50 px-3 py-2 text-sm font-bold text-leaf">
                      <VegDot veg={recipe.is_veg} />
                      {recipe.name}
                    </Link>
                  ))}
                </div>
              )}
          </Card>

          {(d.expiring.length > 0 || d.expired_count > 0) && (
            <Card title="Use soon" link="/kitchen" linkLabel="Kitchen">
              <ul className="space-y-1.5">
                {d.expiring.map((i) => (
                  <li key={i.id} className="flex justify-between text-sm">
                    <span className="font-semibold">
                      {i.ingredient.name} <span className="text-muted">· {fmtQty(i.quantity)} {i.unit}</span>
                    </span>
                    <Badge color="amber">Expires {days(i.days_to_expiry)}</Badge>
                  </li>
                ))}
              </ul>
              {d.expired_count > 0 && (
                <p className="mt-2 text-sm font-semibold text-red-700">
                  {d.expired_count} item{d.expired_count > 1 ? 's have' : ' has'} expired.
                </p>
              )}
              {d.use_soon.length > 0 && (
                <div className="mt-3 border-t border-line pt-3">
                  <p className="mb-2 text-xs font-bold uppercase text-muted">Cook these first</p>
                  <div className="flex flex-wrap gap-2">
                    {d.use_soon.map(({ recipe, match }) => (
                      <Link key={recipe.id} to={`/recipes/${recipe.id}`} className="rounded-full bg-amber-50 px-3 py-2 text-sm font-bold text-amber-900">
                        {recipe.name} · {match.match_percent}%
                      </Link>
                    ))}
                  </div>
                </div>
              )}
            </Card>
          )}

          <div className="grid grid-cols-2 gap-4">
            <Link to="/kitchen" className="rounded-3xl bg-white p-4 shadow-sm">
              <p className="text-3xl font-extrabold">{d.low_stock.length}</p>
              <p className="text-sm font-semibold text-muted">Low stock</p>
              {d.low_stock.length > 0 && <p className="mt-1 truncate text-xs text-muted">{d.low_stock.map((i) => i.ingredient.name).join(', ')}</p>}
            </Link>
            <Link to="/groceries" className="rounded-3xl bg-white p-4 shadow-sm">
              <p className="text-3xl font-extrabold">{d.grocery_remaining}</p>
              <p className="text-sm font-semibold text-muted">Groceries to buy</p>
            </Link>
          </div>
        </>
      )}
    </div>
  )
}
