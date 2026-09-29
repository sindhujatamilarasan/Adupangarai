import type { ButtonHTMLAttributes, InputHTMLAttributes, ReactNode, SelectHTMLAttributes } from 'react'

export function Spinner({ label = 'Loading…' }: { label?: string }) {
  return (
    <div className="flex flex-col items-center justify-center gap-3 py-16 text-muted" role="status">
      <div className="size-8 animate-spin rounded-full border-4 border-line border-t-brand" />
      <span className="text-sm">{label}</span>
    </div>
  )
}

export function ErrorState({ message, onRetry }: { message: string; onRetry?: () => void }) {
  return (
    <div className="mx-auto max-w-sm rounded-2xl bg-red-50 p-5 text-center text-red-800">
      <p className="font-semibold">Something went wrong</p>
      <p className="mt-1 text-sm">{message}</p>
      {onRetry && (
        <button onClick={onRetry} className="mt-3 text-sm font-bold underline">
          Try again
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
  return (
    <button
      {...props}
      disabled={loading || props.disabled}
      className={`w-full rounded-2xl bg-brand px-4 py-3.5 font-bold text-white shadow-sm transition active:scale-[.98] disabled:opacity-60 ${className}`}
    >
      {loading ? 'Please wait…' : children}
    </button>
  )
}

export function Field({ label, error, ...props }: InputHTMLAttributes<HTMLInputElement> & { label: string; error?: string }) {
  return (
    <label className="block">
      <span className="mb-1 block text-sm font-semibold text-muted">{label}</span>
      <input
        {...props}
        className={`w-full rounded-xl border bg-white px-4 py-3 outline-none focus:border-brand focus:ring-2 focus:ring-brand/20 ${error ? 'border-red-400' : 'border-line'}`}
      />
      {error && <span className="mt-1 block text-sm text-red-700">{error}</span>}
    </label>
  )
}

export function Sheet({ open, onClose, title, children }: { open: boolean; onClose: () => void; title: string; children: ReactNode }) {
  if (!open) return null
  return (
    <div className="fixed inset-0 z-30 flex items-end justify-center bg-ink/40 sm:items-center" onClick={onClose}>
      <div
        role="dialog"
        aria-modal="true"
        aria-label={title}
        onClick={(e) => e.stopPropagation()}
        className="max-h-[90svh] w-full max-w-lg overflow-y-auto rounded-t-3xl bg-cream p-5 pb-[calc(1.25rem+env(safe-area-inset-bottom))] sm:rounded-3xl"
      >
        <div className="mb-1 flex items-center justify-between">
          <h2 className="text-xl font-extrabold text-brand">{title}</h2>
          <button onClick={onClose} aria-label="Close" className="grid size-9 place-items-center rounded-full bg-white text-xl text-muted">
            ×
          </button>
        </div>
        <div className="kolam-band-brown mb-4 opacity-70" aria-hidden />
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
