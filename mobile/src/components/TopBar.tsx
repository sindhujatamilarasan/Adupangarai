import { router } from 'expo-router'
import { Pressable, View } from 'react-native'
import { useSafeAreaInsets } from 'react-native-safe-area-context'
import { api } from '../lib/api'
import { useAuth } from '../lib/auth'
import { useI18n } from '../lib/i18n'
import { colors, fonts } from '../theme'
import { Logo } from './Kolam'
import { T } from './Text'

/** App name + language switch + profile, on every tab. */
export function TopBar() {
  const insets = useSafeAreaInsets()
  const { user } = useAuth()
  const { lang, setLang, t } = useI18n()
  const toggle = () => {
    const next = lang === 'ta' ? 'en' : 'ta'
    setLang(next)
    api('/profile', { method: 'PUT', body: { locale: next } }).catch(() => {})
  }
  return (
    <View style={{ paddingTop: insets.top, backgroundColor: colors.cream, borderBottomWidth: 1, borderBottomColor: colors.line }}>
      <View style={{ flexDirection: 'row', alignItems: 'center', paddingHorizontal: 20, paddingVertical: 10, gap: 10 }}>
        <Logo size={36} />
        <View style={{ flex: 1 }}>
          <T weight="display" size={19} style={{ fontFamily: fonts.display, lineHeight: 22 }}>
            Adupangarai
          </T>
          <T size={10.5} color={colors.muted} style={{ fontFamily: fonts.taRegular, lineHeight: 14 }}>
            அடுப்பங்கரை
          </T>
        </View>
        <Pressable onPress={toggle} accessibilityLabel={lang === 'ta' ? 'Switch to English' : 'தமிழுக்கு மாற்று'} style={pill}>
          <T weight="semibold" size={14} style={{ fontFamily: lang === 'ta' ? fonts.semibold : fonts.taSemibold }}>
            {lang === 'ta' ? 'EN' : 'த'}
          </T>
        </Pressable>
        <Pressable onPress={() => router.push('/profile')} accessibilityLabel={t('Profile')} style={[pill, { width: 36, height: 36, paddingHorizontal: 0, alignItems: 'center', justifyContent: 'center', backgroundColor: '#7a432019', borderWidth: 0 }]}>
          <T weight="semibold" color={colors.brand} style={{ fontFamily: fonts.semibold }}>
            {user?.name[0]?.toUpperCase()}
          </T>
        </Pressable>
      </View>
    </View>
  )
}

const pill = { borderRadius: 999, borderWidth: 1, borderColor: colors.line, backgroundColor: colors.white, paddingHorizontal: 12, paddingVertical: 5 } as const
