import { useState, type FormEvent } from 'react'
import { api, ApiError, type User } from '../api'
import { useAuth } from '../auth'
import { useI18n } from '../i18n'
import { Alert, Button, Field } from '../components/ui'

export default function ProfilePage() {
  const { user, setUser, logout } = useAuth()
  const { t, lang, setLang } = useI18n()
  const [form, setForm] = useState({
    name: user!.name,
    email: user!.email,
    household_name: user!.household.name,
    current_password: '',
    password: '',
  })
  const [errors, setErrors] = useState<Record<string, string[]>>({})
  const [status, setStatus] = useState<{ kind: 'error' | 'success'; text: string } | null>(null)
  const [busy, setBusy] = useState(false)

  const set = (k: keyof typeof form) => (e: { target: { value: string } }) => setForm({ ...form, [k]: e.target.value })

  async function submit(e: FormEvent) {
    e.preventDefault()
    setBusy(true)
    setErrors({})
    setStatus(null)
    const body: Record<string, string> = { name: form.name, email: form.email, household_name: form.household_name }
    if (form.password) Object.assign(body, { password: form.password, current_password: form.current_password })
    try {
      const r = await api<{ user: User }>('/profile', { method: 'PUT', body })
      setUser(r.user)
      setForm({ ...form, password: '', current_password: '' })
      setStatus({ kind: 'success', text: t('Profile saved.') })
    } catch (err) {
      const e = err as ApiError
      setErrors(e.errors ?? {})
      setStatus({ kind: 'error', text: e.message })
    } finally {
      setBusy(false)
    }
  }

  return (
    <div className="space-y-6">
      <h1 className="font-display text-[1.75rem] font-semibold tracking-tight">{t('Profile')}</h1>
      <form onSubmit={submit} className="space-y-4 card p-5" noValidate>
        {status && <Alert kind={status.kind}>{status.text}</Alert>}
        <Field label={t('Name')} value={form.name} onChange={set('name')} error={errors.name?.[0]} />
        <Field label={t('Email')} type="email" value={form.email} onChange={set('email')} error={errors.email?.[0]} />
        <Field label={t('Kitchen name')} value={form.household_name} onChange={set('household_name')} error={errors.household_name?.[0]} />
        <details className="rounded-xl border border-line p-3">
          <summary className="cursor-pointer text-sm font-semibold text-muted">{t('Change password')}</summary>
          <div className="mt-3 space-y-3">
            <Field label={t('Current password')} type="password" value={form.current_password} onChange={set('current_password')} error={errors.current_password?.[0]} autoComplete="current-password" />
            <Field label={t('New password')} type="password" value={form.password} onChange={set('password')} error={errors.password?.[0]} autoComplete="new-password" />
          </div>
        </details>
        <Button type="submit" loading={busy}>{t('Save changes')}</Button>
      </form>
      <section className="card p-5">
        <p className="mb-3 font-display text-lg font-semibold">{t('Language')}</p>
        <div className="grid grid-cols-2 gap-2">
          {(['en', 'ta'] as const).map((l) => (
            <button
              key={l}
              onClick={() => {
                setLang(l)
                api<{ user: User }>('/profile', { method: 'PUT', body: { locale: l } }).then((r) => setUser(r.user)).catch(() => {})
              }}
              aria-pressed={lang === l}
              className={`rounded-xl border py-3 font-semibold ${lang === l ? 'border-brand bg-brand/5 text-brand' : 'border-line bg-white text-muted'} ${l === 'ta' ? 'font-tamil' : ''}`}
            >
              {l === 'en' ? 'English' : 'தமிழ்'}
            </button>
          ))}
        </div>
      </section>
      <button onClick={logout} className="w-full rounded-2xl border border-line bg-white py-3 font-bold text-red-700">
        {t('Log out')}
      </button>
    </div>
  )
}
