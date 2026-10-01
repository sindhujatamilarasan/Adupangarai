import { router } from 'expo-router'
import { useState } from 'react'
import { KeyboardAvoidingView, Linking, Pressable, ScrollView, View } from 'react-native'
import { useSafeAreaInsets } from 'react-native-safe-area-context'
import { API_BASE, type ApiError } from '../lib/api'
import { useAuth } from '../lib/auth'
import { useI18n } from '../lib/i18n'
import { colors, fonts } from '../theme'
import { GoogleButton } from './GoogleButton'
import { Kolam, Logo } from './Kolam'
import { T } from './Text'
import { Alert, Button, Field, Link } from './ui'

/** Login and sign-up (same look as the web app). */
export function AuthForm({ mode }: { mode: 'login' | 'register' }) {
  const { login, register, loginWithGoogle } = useAuth()
  const { t, lang, setLang } = useI18n()
  const insets = useSafeAreaInsets()
  const [form, setForm] = useState({ name: '', email: '', password: '', household_name: '' })
  const [errors, setErrors] = useState<Record<string, string[]>>({})
  const [message, setMessage] = useState('')
  const [busy, setBusy] = useState(false)
  const isRegister = mode === 'register'
  const set = (k: keyof typeof form) => (v: string) => setForm({ ...form, [k]: v })

  async function submit() {
    setBusy(true)
    setErrors({})
    setMessage('')
    try {
      if (isRegister) await register({ ...form, household_name: form.household_name || undefined })
      else await login(form.email.trim(), form.password)
    } catch (err) {
      const e = err as ApiError
      setErrors(e.errors ?? {})
      setMessage(e.message)
      setBusy(false)
    }
  }

  return (
    <KeyboardAvoidingView behavior="height" style={{ flex: 1, backgroundColor: colors.cream }}>
      <ScrollView contentContainerStyle={{ flexGrow: 1, justifyContent: 'center', padding: 24, paddingTop: insets.top + 24, gap: 16 }} keyboardShouldPersistTaps="handled">
        <View style={{ flexDirection: 'row', alignSelf: 'center', backgroundColor: colors.white, borderRadius: 999, borderWidth: 1, borderColor: colors.line, padding: 4 }}>
          {(['en', 'ta'] as const).map((l) => (
            <Pressable key={l} onPress={() => setLang(l)} accessibilityState={{ selected: lang === l }} style={{ borderRadius: 999, paddingHorizontal: 16, paddingVertical: 6, backgroundColor: lang === l ? colors.ink : 'transparent' }}>
              <T weight="semibold" size={14} color={lang === l ? colors.white : colors.muted} style={{ fontFamily: l === 'ta' ? fonts.taSemibold : fonts.semibold }}>
                {l === 'en' ? 'English' : 'தமிழ்'}
              </T>
            </Pressable>
          ))}
        </View>

        <View style={{ alignItems: 'center', marginBottom: 8 }}>
          <Logo size={80} />
          <T weight="display" size={32} style={{ fontFamily: fonts.display, marginTop: 16, lineHeight: 38 }}>
            Adupangarai
          </T>
          <T size={14} color={colors.muted} style={{ fontFamily: fonts.taRegular }}>
            அடுப்பங்கரை
          </T>
          <View style={{ flexDirection: 'row', alignItems: 'center', gap: 12, marginVertical: 8, width: 240 }}>
            <View style={{ flex: 1, height: 1, backgroundColor: colors.line }} />
            <Kolam size={60} />
            <View style={{ flex: 1, height: 1, backgroundColor: colors.line }} />
          </View>
          <T color={colors.muted}>{isRegister ? t('Set up your kitchen') : t('Welcome back to your kitchen')}</T>
        </View>

        {message && !Object.keys(errors).length ? <Alert kind="error">{message}</Alert> : null}
        {isRegister && <Field label={t('Your name')} value={form.name} onChangeText={set('name')} error={errors.name?.[0]} autoComplete="name" />}
        <Field label={t('Email')} value={form.email} onChangeText={set('email')} error={errors.email?.[0]} autoComplete="email" keyboardType="email-address" autoCapitalize="none" />
        <Field label={t('Password')} secure value={form.password} onChangeText={set('password')} error={errors.password?.[0]} autoComplete={isRegister ? 'new-password' : 'current-password'} />
        {isRegister && <Field label={t('Kitchen name (optional)')} placeholder={t("e.g. Amma's Kitchen")} value={form.household_name} onChangeText={set('household_name')} />}
        {!isRegister && (
          <View style={{ alignItems: 'flex-end', marginTop: -6 }}>
            <Link title={t('Forgot password?')} onPress={() => router.push('/forgot-password')} />
          </View>
        )}
        <Button title={isRegister ? t('Create account') : t('Log in')} onPress={submit} loading={busy} />
        {isRegister && (
          <T size={12} color={colors.muted} style={{ textAlign: 'center' }}>
            {t('By creating an account you agree to the')}{' '}
            <T size={12} color={colors.muted} style={{ textDecorationLine: 'underline' }} onPress={() => Linking.openURL(`${API_BASE}/terms`)}>
              {t('Terms of use')}
            </T>{' '}
            {t('and')}{' '}
            <T size={12} color={colors.muted} style={{ textDecorationLine: 'underline' }} onPress={() => Linking.openURL(`${API_BASE}/privacy`)}>
              {t('Privacy policy')}
            </T>
            .
          </T>
        )}

        <GoogleButton
          onError={setMessage}
          onToken={async (idToken) => {
            setBusy(true)
            setMessage('')
            try {
              await loginWithGoogle(idToken)
            } catch (err) {
              const e = err as ApiError
              setErrors({})
              setMessage(e.errors?.google?.[0] ?? e.message)
              setBusy(false)
            }
          }}
        />

        <View style={{ flexDirection: 'row', justifyContent: 'center', gap: 6, marginTop: 8 }}>
          <T size={14} color={colors.muted}>
            {isRegister ? t('Already have an account?') : t('New here?')}
          </T>
          <Link title={isRegister ? t('Log in') : t('Create an account')} onPress={() => (isRegister ? router.replace('/login') : router.push('/register'))} />
        </View>
      </ScrollView>
    </KeyboardAvoidingView>
  )
}
