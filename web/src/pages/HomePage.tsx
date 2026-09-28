import { Link } from 'react-router-dom'
import { useAuth } from '../auth'
import { EmptyState } from '../components/ui'

export default function HomePage() {
  const { user } = useAuth()
  return (
    <div>
      <header className="flex items-center justify-between">
        <div>
          <p className="text-sm text-muted">Vanakkam,</p>
          <h1 className="text-2xl font-extrabold">{user!.name} 👋</h1>
        </div>
        <Link to="/profile" aria-label="Profile" className="grid size-11 place-items-center rounded-full bg-brand text-lg font-bold text-white">
          {user!.name[0]?.toUpperCase()}
        </Link>
      </header>
      <p className="mt-1 text-sm font-semibold text-brand">{user!.household.name}</p>
      <EmptyState emoji="🥘" title="Your kitchen is ready">
        Today's meals, expiring items and grocery reminders will show up here.
      </EmptyState>
    </div>
  )
}
