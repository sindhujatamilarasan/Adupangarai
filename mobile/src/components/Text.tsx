import { Text, type TextProps, type TextStyle } from 'react-native'
import { colors, fonts } from '../theme'
import { useI18n } from '../lib/i18n'

type Weight = 'regular' | 'medium' | 'semibold' | 'bold' | 'display'

/** App text: picks the brand font, switching to Noto Sans Tamil when the app is in Tamil. */
export function T({ weight = 'regular', size = 15, color = colors.ink, style, ...props }: TextProps & { weight?: Weight; size?: number; color?: string }) {
  const { lang } = useI18n()
  const family =
    weight === 'display'
      ? lang === 'ta' ? fonts.taBold : fonts.display
      : lang === 'ta'
        ? { regular: fonts.taRegular, medium: fonts.taMedium, semibold: fonts.taSemibold, bold: fonts.taBold }[weight]
        : fonts[weight]
  const base: TextStyle = { fontFamily: family, fontSize: size, color, lineHeight: Math.round(size * (lang === 'ta' ? 1.55 : 1.35)) }
  return <Text {...props} style={[base, style]} />
}
