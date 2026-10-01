import { router, useLocalSearchParams } from 'expo-router'
import { useState } from 'react'
import { View } from 'react-native'
import { api, type ApiError } from '../lib/api'
import { useI18n } from '../lib/i18n'
import { colors } from '../theme'
import { Logo } from './Kolam'
import { T } from './Text'
import { Alert, Button, Field, Link, Screen } from './ui'

/** "Forgot password?" (email me a link) and the screen the link opens (choose a new password). */
export function PasswordForm({ mode }: { mode: 'forgot' | 'reset' }) {
  const { t } = useI18n()
  const params = useLocalSearchParams<{ token?: string; email?: string }>()
  const [email, setEmail] = useState(params.email ?? '')
  const [password, setPassword] = useState('')
  const [status, setStatus] = useState<{ kind: 'error' | 'success'; text: string } | null>(null)
  const [errors, setErrors] = useState<Record<string, string[]>>({})
  const [done, setDone] = useState(false)
  const [busy, setBusy] = useState(false)

  async function submit() {
    setBusy(true)
    setStatus(null)
    setErrors({})
    try {
      const r =
        mode === 'forgot'
          ? await api<{ message: string }>('/forgot-password', { method: 'POST', body: { email: email.trim() } })
          : await api<{ message: string }>('/reset-password', { method: 'POST', body: { token: params.token, email: email.trim(), password } })
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
    <Screen padTop>
      <View style={{ alignItems: 'center', marginTop: 40, gap: 8 }}>
        <Logo size={64} />
        <T weight="display" size={26} style={{ textAlign: 'center', marginTop: 12 }}>
          {mode === 'forgot' ? t('Forgot your password?') : t('Choose a new password')}
        </T>
        <T size={14} color={colors.muted} style={{ textAlign: 'center' }}>
          {mode === 'forgot' ? t('Enter your email and we’ll send you a link to set a new one.') : t('At least 8 characters.')}
        </T>
      </View>
      {status && <Alert kind={status.kind}>{status.text}</Alert>}
      {!done && (
        <>
          <Field label={t('Email')} value={email} onChangeText={setEmail} error={errors.email?.[0]} keyboardType="email-address" autoCapitalize="none" autoComplete="email" />
          {mode === 'reset' && <Field label={t('New password')} secure value={password} onChangeText={setPassword} error={errors.password?.[0]} autoComplete="new-password" />}
          <Button title={mode === 'forgot' ? t('Send reset link') : t('Save new password')} onPress={submit} loading={busy} disabled={!email || (mode === 'reset' && password.length < 8)} />
        </>
      )}
      <View style={{ alignItems: 'center' }}>
        <Link title={`← ${t('Back to log in')}`} onPress={() => (router.canGoBack() ? router.back() : router.replace('/login'))} />
      </View>
    </Screen>
  )
}
