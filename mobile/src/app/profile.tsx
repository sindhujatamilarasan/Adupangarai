import { useState } from 'react'
import { Linking, Pressable, View } from 'react-native'
import { Header } from '../components/Header'
import { T } from '../components/Text'
import { Alert, Button, Card, Field, Link, Screen } from '../components/ui'
import { api, API_BASE, type ApiError, type User } from '../lib/api'
import { useAuth } from '../lib/auth'
import { useI18n } from '../lib/i18n'
import { colors, fonts } from '../theme'

export default function Profile() {
  const { user, setUser, logout } = useAuth()
  const { t, lang, setLang } = useI18n()
  const [form, setForm] = useState({ name: user!.name, email: user!.email, household_name: user!.household.name, current_password: '', password: '' })
  const [showPassword, setShowPassword] = useState(false)
  const [errors, setErrors] = useState<Record<string, string[]>>({})
  const [status, setStatus] = useState<{ kind: 'error' | 'success'; text: string } | null>(null)
  const [busy, setBusy] = useState(false)
  const set = (k: keyof typeof form) => (v: string) => setForm({ ...form, [k]: v })

  async function save() {
    setBusy(true)
    setErrors({})
    setStatus(null)
    const body: Record<string, string> = { name: form.name, email: form.email.trim(), household_name: form.household_name }
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
    <View style={{ flex: 1 }}>
      <Header title={t('Profile')} />
      <Screen>
        <Card style={{ gap: 16 }}>
          {status && <Alert kind={status.kind}>{status.text}</Alert>}
          <Field label={t('Name')} value={form.name} onChangeText={set('name')} error={errors.name?.[0]} />
          <Field label={t('Email')} value={form.email} onChangeText={set('email')} error={errors.email?.[0]} keyboardType="email-address" autoCapitalize="none" />
          <Field label={t('Kitchen name')} value={form.household_name} onChangeText={set('household_name')} error={errors.household_name?.[0]} />
          <Link title={`${showPassword ? '−' : '+'} ${t('Change password')}`} color={colors.muted} onPress={() => setShowPassword(!showPassword)} />
          {showPassword && (
            <>
              <Field label={t('Current password')} secure value={form.current_password} onChangeText={set('current_password')} error={errors.current_password?.[0]} />
              <Field label={t('New password')} secure value={form.password} onChangeText={set('password')} error={errors.password?.[0]} />
            </>
          )}
          <Button title={t('Save changes')} onPress={save} loading={busy} />
        </Card>

        <Card>
          <T weight="display" size={18} style={{ marginBottom: 12 }}>
            {t('Language')}
          </T>
          <View style={{ flexDirection: 'row', gap: 8 }}>
            {(['en', 'ta'] as const).map((l) => (
              <Pressable
                key={l}
                onPress={() => {
                  setLang(l)
                  api<{ user: User }>('/profile', { method: 'PUT', body: { locale: l } }).then((r) => setUser(r.user), () => {})
                }}
                accessibilityState={{ selected: lang === l }}
                style={{ flex: 1, alignItems: 'center', paddingVertical: 12, borderRadius: 12, borderWidth: 1, borderColor: lang === l ? colors.brand : colors.line, backgroundColor: lang === l ? '#7a43200d' : colors.white }}
              >
                <T weight="semibold" color={lang === l ? colors.brand : colors.muted} style={{ fontFamily: l === 'ta' ? fonts.taSemibold : fonts.semibold }}>
                  {l === 'en' ? 'English' : 'தமிழ்'}
                </T>
              </Pressable>
            ))}
          </View>
        </Card>

        <Button variant="outline" title={t('Log out')} onPress={logout} />
        <DeleteAccount />
        <View style={{ flexDirection: 'row', justifyContent: 'center', gap: 16 }}>
          <Link title={t('Privacy policy')} color={colors.muted} onPress={() => Linking.openURL(`${API_BASE}/privacy`)} />
          <Link title={t('Terms of use')} color={colors.muted} onPress={() => Linking.openURL(`${API_BASE}/terms`)} />
        </View>
      </Screen>
    </View>
  )
}

/** Permanently delete the account and all its data (typing the email confirms it). */
function DeleteAccount() {
  const { user, logout } = useAuth()
  const { t } = useI18n()
  const [open, setOpen] = useState(false)
  const [confirm, setConfirm] = useState('')
  const [error, setError] = useState('')
  const [busy, setBusy] = useState(false)

  async function destroy() {
    setBusy(true)
    setError('')
    try {
      await api('/profile', { method: 'DELETE', body: { confirm } })
      await logout()
    } catch (e) {
      setError((e as ApiError).message)
      setBusy(false)
    }
  }

  return (
    <Card style={{ borderColor: '#fecaca', gap: 12 }}>
      <Link title={t('Delete account')} color={colors.red} onPress={() => setOpen(!open)} />
      {open && (
        <>
          <T size={14} color={colors.muted}>
            {t('This permanently deletes your account, kitchen, recipes, photos, plans, grocery lists and coach data. It cannot be undone.')}
          </T>
          {error ? <Alert kind="error">{error}</Alert> : null}
          <Field label={t('Type {email} to confirm', { email: user!.email })} value={confirm} onChangeText={setConfirm} autoCapitalize="none" autoComplete="off" />
          <Button variant="danger" title={t('Delete forever')} onPress={destroy} loading={busy} disabled={confirm.trim().toLowerCase() !== user!.email} />
        </>
      )}
    </Card>
  )
}
