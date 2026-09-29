import { useState, type FormEvent } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { ApiError } from '../api'
import { useAuth } from '../auth'
import { Alert, Button, Field } from '../components/ui'

export default function AuthPage({ mode }: { mode: 'login' | 'register' }) {
  const { login, register } = useAuth()
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
      <div className="mb-8 text-center">
        <img src="/logo.svg" alt="" className="mx-auto size-24 rounded-3xl shadow-lg" />
        <p className="mt-3 text-3xl font-extrabold text-brand">Adupangarai</p>
        <p className="text-sm font-semibold text-muted">அடுப்பங்கரை</p>
        <div className="kolam-band-brown mx-auto my-3 w-48" aria-hidden />
        <p className="text-muted">{isRegister ? 'Set up your kitchen' : 'Welcome back to your kitchen'}</p>
      </div>

      <form onSubmit={submit} className="space-y-4" noValidate>
        {message && !Object.keys(errors).length && <Alert kind="error">{message}</Alert>}
        {isRegister && <Field label="Your name" value={form.name} onChange={set('name')} error={errors.name?.[0]} autoComplete="name" required />}
        <Field label="Email" type="email" value={form.email} onChange={set('email')} error={errors.email?.[0]} autoComplete="email" required />
        <Field
          label="Password"
          type="password"
          value={form.password}
          onChange={set('password')}
          error={errors.password?.[0]}
          autoComplete={isRegister ? 'new-password' : 'current-password'}
          required
        />
        {isRegister && (
          <Field label="Kitchen name (optional)" placeholder="e.g. Amma's Kitchen" value={form.household_name} onChange={set('household_name')} error={errors.household_name?.[0]} />
        )}
        <Button type="submit" loading={busy}>
          {isRegister ? 'Create account' : 'Log in'}
        </Button>
      </form>

      <p className="mt-6 text-center text-sm text-muted">
        {isRegister ? 'Already have an account? ' : 'New here? '}
        <Link to={isRegister ? '/login' : '/register'} className="font-bold text-brand">
          {isRegister ? 'Log in' : 'Create an account'}
        </Link>
      </p>
    </main>
  )
}
