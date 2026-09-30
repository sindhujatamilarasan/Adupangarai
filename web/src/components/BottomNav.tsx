import { NavLink, useLocation } from 'react-router-dom'
import { useI18n } from '../i18n'

const tabs = [
  { to: '/', label: 'Home', icon: 'M3 11l9-7 9 7v9a1 1 0 01-1 1h-5v-6H9v6H4a1 1 0 01-1-1z' },
  { to: '/kitchen', label: 'Kitchen', icon: 'M5 3h14v6H5zM5 9h14v12H5zM9 6h1M9 13v4' },
  { to: '/cook', label: 'Cook', icon: 'M4 12h16a0 0 0 010 0 8 8 0 01-16 0zM12 4v3M8 5v2M16 5v2M2 12h2M20 12h2' },
  { to: '/planner', label: 'Planner', icon: 'M4 5h16v16H4zM4 10h16M9 3v4M15 3v4' },
  { to: '/coach', label: 'Coach', icon: 'M3 12h4l2.5-6 4 12 2.5-6H21' },
  { to: '/groceries', label: 'Groceries', icon: 'M3 4h2l2.5 11h11L21 7H6.5M9 20a1 1 0 100-2 1 1 0 000 2zM18 20a1 1 0 100-2 1 1 0 000 2z' },
]

export default function BottomNav() {
  const { pathname } = useLocation()
  const { t: tr } = useI18n()
  return (
    <nav className="fixed inset-x-0 bottom-0 z-10 border-t border-line bg-white/90 pb-[env(safe-area-inset-bottom)] backdrop-blur-md">
      <ul className="mx-auto flex max-w-lg justify-around">
        {tabs.map((t) => (
          <li key={t.to}>
            <NavLink
              to={t.to}
              end={t.to === '/'}
              className={({ isActive }) =>
                `relative flex flex-col items-center gap-1 px-2 pt-2.5 pb-2 text-[11px] font-semibold transition-colors ${isActive || (t.to === '/cook' && pathname.startsWith('/recipes')) ? 'text-brand' : 'text-muted'}`
              }
            >
              <svg viewBox="0 0 24 24" className="size-[22px]" fill="none" stroke="currentColor" strokeWidth={1.6} strokeLinecap="round" strokeLinejoin="round" aria-hidden>
                <path d={t.icon} />
              </svg>
              {tr(t.label)}
              {(pathname === t.to || (t.to !== '/' && pathname.startsWith(t.to)) || (t.to === '/cook' && pathname.startsWith('/recipes'))) && (
                <span className="absolute bottom-0.5 size-1 rounded-full bg-brand" aria-hidden />
              )}
            </NavLink>
          </li>
        ))}
      </ul>
    </nav>
  )
}
