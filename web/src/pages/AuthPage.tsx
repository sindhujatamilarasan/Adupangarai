import { useState, type FormEvent } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { ApiError } from '../api'
import { useAuth } from '../auth'
import { useI18n } from '../i18n'
import Kolam from '../components/Kolam'
import { Alert, Button, Field } from '../components/ui'

export default function AuthPage({ mode }: { mode: 'login' | 'register' }) {
  const { login, register } = useAuth()
  const { t, lang, setLang } = useI18n()
  const navigate = useNavigate()
  const [form, setForm] = useState({ name: '', email: '', password: '', household_name: '' })
  const [errors, setErrors] = useState<Record<string, string[]>>({})
  const [message, setMessage] = useState('')
  const [busy, setBusy] = useState(false)
  const isRegister = mode === 'register'

  const set = (k: keyof typeof form) => (e: { target: { value: string } }) => setForm({ ...form, [k]: e.target.value })

  async function submit(e: FormEvent) {
    e.preventDefault()
    setBusy(true)
    setErrors({})
    setMessage('')
    try {
      if (isRegister) {
        await register({ ...form, household_name: form.household_name || undefined })
      } else {
        await login(form.email, form.password)
      }
      navigate('/', { replace: true })
    } catch (err) {
      const e = err as ApiError
      setErrors(e.errors ?? {})
      setMessage(e.message)
    } finally {
      setBusy(false)
    }
  }

  return (
    <main className="mx-auto flex min-h-svh max-w-sm flex-col justify-center px-6 py-10">
      <div className="mb-6 flex justify-center">
        <div className="flex gap-1 rounded-full border border-line bg-white p-1 text-sm" role="group" aria-label="Language / மொழி">
          {(['en', 'ta'] as const).map((l) => (
            <button
              key={l}
              type="button"
              onClick={() => setLang(l)}
              aria-pressed={lang === l}
              className={`rounded-full px-4 py-1.5 font-semibold ${lang === l ? 'bg-ink text-white' : 'text-muted'} ${l === 'ta' ? 'font-tamil' : ''}`}
            >
              {l === 'en' ? 'English' : 'தமிழ்'}
            </button>
          ))}
        </div>
      </div>
      <div className="mb-8 text-center">
        <img src="/logo.svg" alt="" className="mx-auto size-20 rounded-[1.4rem] shadow-[0_8px_24px_rgb(122_67_32/0.25)]" />
        <p className="mt-5 font-display text-[2rem] font-semibold tracking-tight">Adupangarai</p>
        <p className="font-tamil text-sm text-muted">அடுப்பங்கரை</p>
        <div className="mx-auto my-3 flex w-60 items-center gap-3" aria-hidden>
          <span className="h-px flex-1 bg-line" />
          <Kolam classic className="size-16 text-brand/80" strokeWidth={0.06} />
          <span className="h-px flex-1 bg-line" />
        </div>
        <p className="text-muted">{isRegister ? t('Set up your kitchen') : t('Welcome back to your kitchen')}</p>
      </div>

      <form onSubmit={submit} className="space-y-4" noValidate>
        {message && !Object.keys(errors).length && <Alert kind="error">{message}</Alert>}
        {isRegister && <Field label={t('Your name')} value={form.name} onChange={set('name')} error={errors.name?.[0]} autoComplete="name" required />}
        <Field label={t('Email')} type="email" value={form.email} onChange={set('email')} error={errors.email?.[0]} autoComplete="email" required />
        <Field
          label={t('Password')}
          type="password"
          value={form.password}
          onChange={set('password')}
          error={errors.password?.[0]}
          autoComplete={isRegister ? 'new-password' : 'current-password'}
          required
        />
        {isRegister && (
          <Field label={t('Kitchen name (optional)')} placeholder={t("e.g. Amma's Kitchen")} value={form.household_name} onChange={set('household_name')} error={errors.household_name?.[0]} />
        )}
        <Button type="submit" loading={busy}>
          {isRegister ? t('Create account') : t('Log in')}
        </Button>
      </form>

      <p className="mt-6 text-center text-sm text-muted">
        {isRegister ? t('Already have an account?') : t('New here?')}{' '}
        <Link to={isRegister ? '/login' : '/register'} className="font-bold text-brand">
          {isRegister ? t('Log in') : t('Create an account')}
        </Link>
      </p>
    </main>
  )
}
