import { GoogleSignin, isErrorWithCode, isSuccessResponse, statusCodes } from '@react-native-google-signin/google-signin'
import { Pressable, View } from 'react-native'
import Svg, { Path } from 'react-native-svg'
import { useApi } from '../lib/api'
import { useI18n } from '../lib/i18n'
import { colors } from '../theme'
import { T } from './Text'

/** Android's own Google account picker. Hidden when the server has no Google client ID. The server verifies the token. */
export function GoogleButton({ onToken, onError }: { onToken: (idToken: string) => void; onError: (message: string) => void }) {
  const { t } = useI18n()
  const config = useApi<{ google_client_id: string | null }>('/auth/config')
  const clientId = config.data?.google_client_id
  if (!clientId) return null

  async function signIn() {
    try {
      GoogleSignin.configure({ webClientId: clientId! })
      await GoogleSignin.hasPlayServices({ showPlayServicesUpdateDialog: true })
      await GoogleSignin.signOut().catch(() => {}) // always let people pick the account
      const res = await GoogleSignin.signIn()
      if (isSuccessResponse(res) && res.data.idToken) onToken(res.data.idToken)
    } catch (e) {
      if (isErrorWithCode(e) && (e.code === statusCodes.SIGN_IN_CANCELLED || e.code === statusCodes.IN_PROGRESS)) return
      onError(t('Google sign-in failed. Please try again.'))
    }
  }

  return (
    <View style={{ gap: 12 }}>
      <View style={{ flexDirection: 'row', alignItems: 'center', gap: 12 }}>
        <View style={{ flex: 1, height: 1, backgroundColor: colors.line }} />
        <T size={13} color={colors.muted}>
          {t('or')}
        </T>
        <View style={{ flex: 1, height: 1, backgroundColor: colors.line }} />
      </View>
      <Pressable onPress={signIn} accessibilityRole="button" style={({ pressed }) => ({ flexDirection: 'row', alignItems: 'center', justifyContent: 'center', gap: 10, borderRadius: 999, borderWidth: 1, borderColor: '#dadce0', backgroundColor: colors.white, paddingVertical: 12, opacity: pressed ? 0.8 : 1 })}>
        <Svg width={18} height={18} viewBox="0 0 48 48">
          <Path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z" />
          <Path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z" />
          <Path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z" />
          <Path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z" />
        </Svg>
        <T weight="semibold" color="#3c4043">
          {t('Continue with Google')}
        </T>
      </Pressable>
    </View>
  )
}
