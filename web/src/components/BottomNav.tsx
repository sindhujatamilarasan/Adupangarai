import { NavLink, useLocation } from 'react-router-dom'

const tabs = [
  { to: '/', label: 'Home', icon: 'M3 11l9-7 9 7v9a1 1 0 01-1 1h-5v-6H9v6H4a1 1 0 01-1-1z' },
  { to: '/kitchen', label: 'Kitchen', icon: 'M5 3h14v6H5zM5 9h14v12H5zM9 6h1M9 13v4' },
  { to: '/cook', label: 'Cook', icon: 'M4 12h16a0 0 0 010 0 8 8 0 01-16 0zM12 4v3M8 5v2M16 5v2M2 12h2M20 12h2' },
  { to: '/planner', label: 'Planner', icon: 'M4 5h16v16H4zM4 10h16M9 3v4M15 3v4' },
  { to: '/groceries', label: 'Groceries', icon: 'M3 4h2l2.5 11h11L21 7H6.5M9 20a1 1 0 100-2 1 1 0 000 2zM18 20a1 1 0 100-2 1 1 0 000 2z' },
]

export default function BottomNav() {
  const { pathname } = useLocation()
  return (
    <nav className="fixed inset-x-0 bottom-0 z-10 border-t border-line bg-white/95 pb-[env(safe-area-inset-bottom)] backdrop-blur">
      <ul className="mx-auto flex max-w-lg justify-around">
        {tabs.map((t) => (
          <li key={t.to}>
            <NavLink
              to={t.to}
              end={t.to === '/'}
              className={({ isActive }) =>
                `my-1 flex flex-col items-center gap-0.5 rounded-2xl px-3 py-1.5 text-xs font-bold transition ${isActive || (t.to === '/cook' && pathname.startsWith('/recipes')) ? 'bg-brand/10 text-brand' : 'text-muted'}`
              }
            >
              <svg viewBox="0 0 24 24" className="size-6" fill="none" stroke="currentColor" strokeWidth={1.8} strokeLinecap="round" strokeLinejoin="round" aria-hidden>
                <path d={t.icon} />
              </svg>
              {t.label}
            </NavLink>
          </li>
        ))}
      </ul>
    </nav>
  )
}
