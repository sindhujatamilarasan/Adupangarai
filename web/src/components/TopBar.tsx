import { Link } from 'react-router-dom'
import { useAuth } from '../auth'

/** App name + quick actions, shown on every signed-in screen. */
export default function TopBar() {
  const { user } = useAuth()
  return (
    <header className="sticky top-0 z-20 border-b border-line bg-cream/85 pt-[env(safe-area-inset-top)] backdrop-blur-md">
      <div className="mx-auto flex max-w-lg items-center justify-between px-5 py-3">
        <Link to="/" className="flex items-center gap-2.5" aria-label="Adupangarai home">
          <img src="/logo.svg" alt="" className="size-9 rounded-[10px]" />
          <span className="leading-none">
            <span className="block font-display text-[1.2rem] font-semibold tracking-tight text-ink">Adupangarai</span>
            <span className="mt-1 block font-tamil text-[10.5px] text-muted">அடுப்பங்கரை</span>
          </span>
        </Link>
        <div className="flex items-center gap-2">
          <Link to="/ai" className="rounded-full border border-line bg-white px-3.5 py-1.5 text-sm font-semibold text-brand">
            ✦ AI
          </Link>
          <Link to="/profile" aria-label="Profile" className="grid size-9 place-items-center rounded-full bg-brand/10 text-sm font-semibold text-brand">
            {user?.name[0]?.toUpperCase()}
          </Link>
        </div>
      </div>
    </header>
  )
}
