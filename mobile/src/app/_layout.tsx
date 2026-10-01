import { Fraunces_600SemiBold } from '@expo-google-fonts/fraunces'
import { NotoSansTamil_400Regular, NotoSansTamil_500Medium, NotoSansTamil_600SemiBold, NotoSansTamil_700Bold } from '@expo-google-fonts/noto-sans-tamil'
import { PlusJakartaSans_400Regular, PlusJakartaSans_500Medium, PlusJakartaSans_600SemiBold, PlusJakartaSans_700Bold } from '@expo-google-fonts/plus-jakarta-sans'
import { useFonts } from 'expo-font'
import { Stack } from 'expo-router'
import * as SplashScreen from 'expo-splash-screen'
import { StatusBar } from 'expo-status-bar'
import { useEffect, useState } from 'react'
import { AuthProvider, useAuth } from '../lib/auth'
import { I18nProvider, loadLang, useI18n, type Lang } from '../lib/i18n'
import { colors } from '../theme'

SplashScreen.preventAutoHideAsync().catch(() => {})

export default function RootLayout() {
  const [fontsLoaded] = useFonts({
    Fraunces_600SemiBold,
    PlusJakartaSans_400Regular, PlusJakartaSans_500Medium, PlusJakartaSans_600SemiBold, PlusJakartaSans_700Bold,
    NotoSansTamil_400Regular, NotoSansTamil_500Medium, NotoSansTamil_600SemiBold, NotoSansTamil_700Bold,
  })
  const [lang, setLang] = useState<Lang | null>(null)
  useEffect(() => {
    loadLang().then(setLang)
  }, [])

  if (!fontsLoaded || !lang) return null
  return (
    <I18nProvider initial={lang}>
      <AuthProvider>
        <StatusBar style="dark" />
        <Routes />
      </AuthProvider>
    </I18nProvider>
  )
}

/** Signed-in screens and sign-in screens; switching login state moves between them. */
function Routes() {
  const { user, loading } = useAuth()
  const { lang } = useI18n()
  useEffect(() => {
    if (!loading) SplashScreen.hideAsync().catch(() => {})
  }, [loading])
  if (loading) return null

  return (
    // Remount on language change so server text (dish names, messages) reloads in that language.
    <Stack key={lang} screenOptions={{ headerShown: false, contentStyle: { backgroundColor: colors.cream }, animation: 'slide_from_right' }}>
      <Stack.Protected guard={!!user}>
        <Stack.Screen name="(tabs)" />
        <Stack.Screen name="profile" />
      </Stack.Protected>
      <Stack.Protected guard={!user}>
        <Stack.Screen name="login" />
        <Stack.Screen name="register" />
        <Stack.Screen name="forgot-password" />
      </Stack.Protected>
      <Stack.Screen name="reset-password" />
    </Stack>
  )
}
