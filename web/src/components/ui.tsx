import { useI18n } from '../i18n'
import Kolam from './Kolam'
import { useState, type ButtonHTMLAttributes, type InputHTMLAttributes, type ReactNode, type SelectHTMLAttributes } from 'react'

export function Spinner({ label }: { label?: string }) {
  const { t } = useI18n()
  return (
    <div className="flex flex-col items-center justify-center gap-3 py-16 text-muted" role="status">
      <Kolam classic animate className="size-16 text-brand" strokeWidth={0.07} />
      <span className="text-sm">{label ?? t('Loading…')}</span>
    </div>
  )
}

export function ErrorState({ message, onRetry }: { message: string; onRetry?: () => void }) {
  const { t } = useI18n()
  return (
    <div className="mx-auto max-w-sm rounded-2xl bg-red-50 p-5 text-center text-red-800">
      <p className="font-semibold">{t('Something went wrong')}</p>
      <p className="mt-1 text-sm">{message}</p>
      {onRetry && (
        <button onClick={onRetry} className="mt-3 text-sm font-bold underline">
          {t('Try again')}
        </button>
      )}
    </div>
  )
}

export function EmptyState({ emoji, title, children }: { emoji: string; title: string; children?: ReactNode }) {
  return (
    <div className="flex flex-col items-center py-14 text-center">
      <div className="mb-3 text-5xl">{emoji}</div>
      <p className="text-lg font-bold">{title}</p>
      {children && <div className="mt-1 max-w-xs text-sm text-muted">{children}</div>}
    </div>
  )
}

export function Alert({ kind, children }: { kind: 'error' | 'success'; children: ReactNode }) {
  const cls = kind === 'error' ? 'bg-red-50 text-red-800' : 'bg-green-50 text-green-800'
  return (
    <div role="alert" className={`rounded-xl px-4 py-3 text-sm font-semibold ${cls}`}>
      {children}
    </div>
  )
}

export function Button({ loading, children, className = '', ...props }: ButtonHTMLAttributes<HTMLButtonElement> & { loading?: boolean }) {
  const { t } = useI18n()
  return (
    <button
      {...props}
      disabled={loading || props.disabled}
      className={`w-full rounded-xl bg-brand px-4 py-3.5 font-semibold text-white transition hover:bg-brand-dark active:scale-[.99] disabled:opacity-50 ${className}`}
    >
      {loading ? t('Please wait…') : children}
    </button>
  )
}

export function Field({ label, error, ...props }: InputHTMLAttributes<HTMLInputElement> & { label: string; error?: string }) {
  const { t } = useI18n()
  const [shown, setShown] = useState(false)
  const isPassword = props.type === 'password'
  return (
    <label className="block">
      <span className="mb-1 block text-sm font-semibold text-muted">{label}</span>
      <span className="relative block">
        <input
          {...props}
          type={isPassword && shown ? 'text' : props.type}
          className={`w-full rounded-xl border bg-white px-4 py-3 outline-none transition focus:border-brand focus:ring-4 focus:ring-brand/10 ${isPassword ? 'pr-12' : ''} ${error ? 'border-red-400' : 'border-line'}`}
        />
        {isPassword && (
          <button
            type="button"
            onClick={() => setShown(!shown)}
            aria-label={shown ? t('Hide password') : t('Show password')}
            aria-pressed={shown}
            className="absolute inset-y-0 right-0 grid w-12 place-items-center text-muted hover:text-ink"
          >
            <svg viewBox="0 0 24 24" className="size-5" fill="none" stroke="currentColor" strokeWidth={1.7} strokeLinecap="round" strokeLinejoin="round" aria-hidden>
              <path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12z" />
              <circle cx="12" cy="12" r="3" />
              {shown && <path d="M4 4l16 16" />}
            </svg>
          </button>
        )}
      </span>
      {error && <span className="mt-1 block text-sm text-red-700">{error}</span>}
    </label>
  )
}

export function Sheet({ open, onClose, title, children }: { open: boolean; onClose: () => void; title: string; children: ReactNode }) {
  const { t } = useI18n()
  if (!open) return null
  return (
    <div className="fixed inset-0 z-30 flex items-end justify-center bg-ink/40 sm:items-center" onClick={onClose}>
      <div
        role="dialog"
        aria-modal="true"
        aria-label={title}
        onClick={(e) => e.stopPropagation()}
        className="max-h-[90svh] w-full max-w-lg overflow-y-auto rounded-t-[1.75rem] bg-cream p-5 pb-[calc(1.25rem+env(safe-area-inset-bottom))] sm:rounded-3xl"
      >
        <div className="mb-4 flex items-center justify-between border-b border-line pb-3">
          <h2 className="font-display text-xl font-semibold">{title}</h2>
          <button onClick={onClose} aria-label={t('Close')} className="grid size-9 place-items-center rounded-full bg-white text-xl text-muted">
            ×
          </button>
        </div>
        {children}
      </div>
    </div>
  )
}

export function Select({ label, error, children, ...props }: SelectHTMLAttributes<HTMLSelectElement> & { label: string; error?: string }) {
  return (
    <label className="block">
      <span className="mb-1 block text-sm font-semibold text-muted">{label}</span>
      <select
        {...props}
        className={`w-full rounded-xl border bg-white px-3 py-3 outline-none focus:border-brand ${error ? 'border-red-400' : 'border-line'}`}
      >
        {children}
      </select>
      {error && <span className="mt-1 block text-sm text-red-700">{error}</span>}
    </label>
  )
}

const badgeStyles = {
  red: 'bg-red-100 text-red-800',
  amber: 'bg-amber-100 text-amber-800',
  green: 'bg-green-100 text-green-800',
  gray: 'bg-stone-100 text-stone-700',
}

export function Badge({ color, children }: { color: keyof typeof badgeStyles; children: ReactNode }) {
  return <span className={`rounded-full px-2 py-0.5 text-xs font-bold ${badgeStyles[color]}`}>{children}</span>
}
