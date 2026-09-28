import type { ButtonHTMLAttributes, InputHTMLAttributes, ReactNode } from 'react'

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
