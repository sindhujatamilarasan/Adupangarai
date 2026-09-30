import { useState, type FormEvent } from 'react'
import { Link, useSearchParams } from 'react-router-dom'
import { api, ApiError } from '../api'
import { useI18n } from '../i18n'
import { Alert, Button, Field } from '../components/ui'

/** "Forgot password?" (email me a link) and the page that link opens (choose a new password). */
export default function PasswordPage({ mode }: { mode: 'forgot' | 'reset' }) {
  const { t } = useI18n()
  const [params] = useSearchParams()
  const [email, setEmail] = useState(params.get('email') ?? '')
  const [password, setPassword] = useState('')
  const [status, setStatus] = useState<{ kind: 'error' | 'success'; text: string } | null>(null)
  const [errors, setErrors] = useState<Record<string, string[]>>({})
  const [done, setDone] = useState(false)
  const [busy, setBusy] = useState(false)
  const token = params.get('token') ?? ''

  async function submit(e: FormEvent) {
    e.preventDefault()
    setBusy(true)
    setStatus(null)
    setErrors({})
    try {
      const r =
        mode === 'forgot'
          ? await api<{ message: string }>('/forgot-password', { method: 'POST', body: { email } })
          : await api<{ message: string }>('/reset-password', { method: 'POST', body: { token, email, password } })
      setStatus({ kind: 'success', text: r.message })
      setDone(true)
    } catch (err) {
      const e = err as ApiError
      setErrors(e.errors ?? {})
      setStatus({ kind: 'error', text: e.message })
    } finally {
      setBusy(false)
    }
  }

  return (
    <main className="mx-auto flex min-h-svh max-w-sm flex-col justify-center px-6 py-10">
      <div className="mb-8 text-center">
        <img src="/logo.svg" alt="" className="mx-auto size-16 rounded-[1.2rem]" />
        <h1 className="mt-5 font-display text-[1.75rem] font-semibold tracking-tight">{mode === 'forgot' ? t('Forgot your password?') : t('Choose a new password')}</h1>
        <p className="mt-1 text-sm text-muted">{mode === 'forgot' ? t('Enter your email and we’ll send you a link to set a new one.') : t('At least 8 characters.')}</p>
      </div>

      {status && <Alert kind={status.kind}>{status.text}</Alert>}

      {!done && (
        <form onSubmit={submit} className="mt-4 space-y-4" noValidate>
          <Field label={t('Email')} type="email" value={email} onChange={(e) => setEmail(e.target.value)} error={errors.email?.[0]} autoComplete="email" required />
          {mode === 'reset' && (
            <Field label={t('New password')} type="password" value={password} onChange={(e) => setPassword(e.target.value)} error={errors.password?.[0]} autoComplete="new-password" required />
          )}
          <Button type="submit" loading={busy} disabled={!email || (mode === 'reset' && password.length < 8)}>
            {mode === 'forgot' ? t('Send reset link') : t('Save new password')}
          </Button>
        </form>
      )}

      <p className="mt-6 text-center text-sm">
        <Link to="/login" className="font-bold text-brand">
          ← {t('Back to log in')}
        </Link>
      </p>
    </main>
  )
}
