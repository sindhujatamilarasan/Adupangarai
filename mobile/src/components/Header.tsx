import { router } from 'expo-router'
import { Pressable, View } from 'react-native'
import { useSafeAreaInsets } from 'react-native-safe-area-context'
import { useI18n } from '../lib/i18n'
import { colors } from '../theme'
import { Icon } from './Icon'
import { T } from './Text'

/** Back arrow + title, for screens opened on top of the tabs. */
export function Header({ title }: { title: string }) {
  const insets = useSafeAreaInsets()
  const { t } = useI18n()
  return (
    <View style={{ paddingTop: insets.top, backgroundColor: colors.cream, borderBottomWidth: 1, borderBottomColor: colors.line }}>
      <View style={{ flexDirection: 'row', alignItems: 'center', gap: 8, paddingHorizontal: 12, paddingVertical: 10 }}>
        <Pressable onPress={() => (router.canGoBack() ? router.back() : router.replace('/'))} accessibilityLabel={t('Back')} hitSlop={8} style={{ padding: 6 }}>
          <Icon name="back" color={colors.ink} />
        </Pressable>
        <T weight="display" size={20}>
          {title}
        </T>
      </View>
    </View>
  )
}
