import { useState, type FormEvent } from 'react'
import { api, ApiError, type User } from '../api'
import { useAuth } from '../auth'
import { Alert, Button, Field } from '../components/ui'

export default function ProfilePage() {
  const { user, setUser, logout } = useAuth()
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
      setStatus({ kind: 'success', text: 'Profile saved.' })
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
      <h1 className="font-display text-[1.75rem] font-semibold tracking-tight">Profile</h1>
      <form onSubmit={submit} className="space-y-4 card p-5" noValidate>
        {status && <Alert kind={status.kind}>{status.text}</Alert>}
        <Field label="Name" value={form.name} onChange={set('name')} error={errors.name?.[0]} />
        <Field label="Email" type="email" value={form.email} onChange={set('email')} error={errors.email?.[0]} />
        <Field label="Kitchen name" value={form.household_name} onChange={set('household_name')} error={errors.household_name?.[0]} />
        <details className="rounded-xl border border-line p-3">
          <summary className="cursor-pointer text-sm font-semibold text-muted">Change password</summary>
          <div className="mt-3 space-y-3">
            <Field label="Current password" type="password" value={form.current_password} onChange={set('current_password')} error={errors.current_password?.[0]} autoComplete="current-password" />
            <Field label="New password" type="password" value={form.password} onChange={set('password')} error={errors.password?.[0]} autoComplete="new-password" />
          </div>
        </details>
        <Button type="submit" loading={busy}>Save changes</Button>
      </form>
      <button onClick={logout} className="w-full rounded-2xl border border-line bg-white py-3 font-bold text-red-700">
        Log out
      </button>
    </div>
  )
}
