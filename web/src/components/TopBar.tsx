import { Link } from 'react-router-dom'
import { useAuth } from '../auth'

/** App name + quick actions, shown on every signed-in screen. */
export default function TopBar() {
  const { user } = useAuth()
  return (
    <header className="sticky top-0 z-20 bg-brand-dark pt-[env(safe-area-inset-top)] text-kolam shadow-md">
      <div className="mx-auto flex max-w-lg items-center justify-between px-4 pt-2.5 pb-1.5">
        <Link to="/" className="flex items-center gap-2" aria-label="Adupangarai home">
          <img src="/logo.svg" alt="" className="size-10 rounded-xl ring-1 ring-kolam/40" />
          <span className="leading-tight">
            <span className="block text-lg font-extrabold tracking-tight text-kolam">Adupangarai</span>
            <span className="block text-[11px] font-semibold text-kolam-muted">அடுப்பங்கரை</span>
          </span>
        </Link>
        <div className="flex items-center gap-2">
          <Link to="/ai" className="rounded-full bg-kolam px-3.5 py-2 text-sm font-extrabold text-brand shadow-sm">
            ✨ AI
          </Link>
          <Link to="/profile" aria-label="Profile" className="grid size-9 place-items-center rounded-full border-2 border-kolam text-sm font-bold text-kolam">
            {user?.name[0]?.toUpperCase()}
          </Link>
        </div>
      </div>
      <div className="kolam-band-white" aria-hidden />
    </header>
  )
}
