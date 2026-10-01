import { useState, type ReactNode } from 'react'
import { ActivityIndicator, Pressable, RefreshControl, ScrollView, StyleSheet, TextInput, View, type TextInputProps, type ViewStyle } from 'react-native'
import { useSafeAreaInsets } from 'react-native-safe-area-context'
import { useI18n } from '../lib/i18n'
import { colors, fonts, radius } from '../theme'
import { Icon } from './Icon'
import { Kolam } from './Kolam'
import { T } from './Text'

/** A scrolling page with pull-to-refresh and safe-area padding. */
export function Screen({ children, onRefresh, refreshing = false, padTop = false }: { children: ReactNode; onRefresh?: () => void; refreshing?: boolean; padTop?: boolean }) {
  const insets = useSafeAreaInsets()
  return (
    <ScrollView
      style={{ flex: 1, backgroundColor: colors.cream }}
      contentContainerStyle={{ padding: 16, paddingTop: padTop ? insets.top + 16 : 16, paddingBottom: 32, gap: 16 }}
      keyboardShouldPersistTaps="handled"
      refreshControl={onRefresh ? <RefreshControl refreshing={refreshing} onRefresh={onRefresh} colors={[colors.brand]} /> : undefined}
    >
      {children}
    </ScrollView>
  )
}

export function Card({ children, style }: { children: ReactNode; style?: ViewStyle }) {
  return <View style={[s.card, style]}>{children}</View>
}

export function Button({ title, onPress, loading, disabled, variant = 'primary' }: { title: string; onPress: () => void; loading?: boolean; disabled?: boolean; variant?: 'primary' | 'outline' | 'danger' }) {
  const { t } = useI18n()
  const off = disabled || loading
  const bg = variant === 'primary' ? colors.brand : variant === 'danger' ? colors.red : colors.white
  return (
    <Pressable
      onPress={onPress}
      disabled={off}
      accessibilityRole="button"
      style={({ pressed }) => [s.button, { backgroundColor: bg, opacity: off ? 0.5 : pressed ? 0.85 : 1 }, variant === 'outline' && { borderWidth: 1, borderColor: colors.brand }]}
    >
      <T weight="semibold" size={16} color={variant === 'outline' ? colors.brand : colors.white}>
        {loading ? t('Please wait…') : title}
      </T>
    </Pressable>
  )
}

export function Field({ label, error, secure, ...props }: TextInputProps & { label: string; error?: string; secure?: boolean }) {
  const { t, lang } = useI18n()
  const [shown, setShown] = useState(false)
  return (
    <View style={{ gap: 4 }}>
      <T weight="semibold" size={13} color={colors.muted}>
        {label}
      </T>
      <View>
        <TextInput
          placeholderTextColor="#a8a29e"
          {...props}
          secureTextEntry={secure && !shown}
          style={[s.input, { fontFamily: lang === 'ta' ? fonts.taRegular : fonts.regular }, error ? { borderColor: '#f87171' } : null, secure ? { paddingRight: 48 } : null]}
        />
        {secure && (
          <Pressable onPress={() => setShown(!shown)} accessibilityLabel={shown ? t('Hide password') : t('Show password')} style={s.eye} hitSlop={8}>
            <Icon name={shown ? 'eyeOff' : 'eye'} size={20} color={colors.muted} />
          </Pressable>
        )}
      </View>
      {error ? (
        <T size={13} color={colors.red}>
          {error}
        </T>
      ) : null}
    </View>
  )
}

export function Alert({ kind, children }: { kind: 'error' | 'success'; children: ReactNode }) {
  return (
    <View accessibilityRole="alert" style={[s.alert, { backgroundColor: kind === 'error' ? colors.redBg : colors.greenBg }]}>
      <T weight="semibold" size={14} color={kind === 'error' ? colors.red : colors.green}>
        {children}
      </T>
    </View>
  )
}

const badge = { red: [colors.redBg, colors.red], amber: [colors.amberBg, colors.amber], green: ['#dcfce7', colors.green], gray: ['#f5f5f4', '#44403c'] } as const
export function Badge({ color, children }: { color: keyof typeof badge; children: ReactNode }) {
  return (
    <View style={{ backgroundColor: badge[color][0], borderRadius: radius.pill, paddingHorizontal: 9, paddingVertical: 2 }}>
      <T weight="bold" size={12} color={badge[color][1]}>
        {children}
      </T>
    </View>
  )
}

export function Spinner({ label }: { label?: string }) {
  const { t } = useI18n()
  return (
    <View style={{ alignItems: 'center', paddingVertical: 56, gap: 12 }} accessibilityRole="progressbar">
      <Kolam size={64} />
      <ActivityIndicator color={colors.brand} />
      <T color={colors.muted} size={14}>
        {label ?? t('Loading…')}
      </T>
    </View>
  )
}

export function ErrorState({ message, onRetry }: { message: string; onRetry?: () => void }) {
  const { t } = useI18n()
  return (
    <View style={[s.alert, { backgroundColor: colors.redBg, alignItems: 'center', padding: 20, gap: 6 }]}>
      <T weight="semibold" color={colors.red}>
        {t('Something went wrong')}
      </T>
      <T size={14} color={colors.red} style={{ textAlign: 'center' }}>
        {message}
      </T>
      {onRetry && (
        <Pressable onPress={onRetry} hitSlop={8}>
          <T weight="bold" size={14} color={colors.red} style={{ textDecorationLine: 'underline' }}>
            {t('Try again')}
          </T>
        </Pressable>
      )}
    </View>
  )
}

/** Small text link / button. */
export function Link({ title, onPress, color = colors.brand }: { title: string; onPress: () => void; color?: string }) {
  return (
    <Pressable onPress={onPress} hitSlop={8} accessibilityRole="link">
      <T weight="bold" size={14} color={color}>
        {title}
      </T>
    </Pressable>
  )
}

export const s = StyleSheet.create({
  card: { backgroundColor: colors.white, borderRadius: radius.card, borderWidth: 1, borderColor: colors.line, padding: 20, shadowColor: '#5c3118', shadowOpacity: 0.04, shadowRadius: 8, elevation: 1 },
  button: { borderRadius: radius.input, paddingVertical: 14, paddingHorizontal: 16, alignItems: 'center' },
  input: { backgroundColor: colors.white, borderWidth: 1, borderColor: colors.line, borderRadius: radius.input, paddingHorizontal: 16, paddingVertical: 12, fontSize: 16, color: colors.ink },
  eye: { position: 'absolute', right: 0, top: 0, bottom: 0, width: 48, alignItems: 'center', justifyContent: 'center' },
  alert: { borderRadius: 12, paddingHorizontal: 16, paddingVertical: 12 },
})
