import { Link } from 'react-router-dom'
import { useAuth } from '../auth'

/** App name + quick actions, shown on every signed-in screen. */
export default function TopBar() {
  const { user } = useAuth()
  return (
    <header className="sticky top-0 z-20 border-b border-line/70 bg-cream/90 pt-[env(safe-area-inset-top)] backdrop-blur">
      <div className="mx-auto flex max-w-lg items-center justify-between px-4 py-2.5">
        <Link to="/" className="flex items-center gap-2" aria-label="Adupangarai home">
          <span className="grid size-9 place-items-center rounded-xl bg-gradient-to-br from-brand to-amber-500 text-lg shadow-sm">🍲</span>
          <span className="leading-tight">
            <span className="block text-lg font-extrabold tracking-tight text-brand">Adupangarai</span>
            <span className="block text-[11px] font-semibold text-muted">அடுப்பங்கரை</span>
          </span>
        </Link>
        <div className="flex items-center gap-2">
          <Link to="/ai" className="rounded-full bg-gradient-to-br from-brand to-amber-500 px-3.5 py-2 text-sm font-extrabold text-white shadow-sm">
            ✨ AI
          </Link>
          <Link to="/profile" aria-label="Profile" className="grid size-9 place-items-center rounded-full bg-ink text-sm font-bold text-white">
            {user?.name[0]?.toUpperCase()}
          </Link>
        </div>
      </div>
    </header>
  )
}
