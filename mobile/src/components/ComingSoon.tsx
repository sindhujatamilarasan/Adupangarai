import { Linking, View } from 'react-native'
import { API_BASE } from '../lib/api'
import { useI18n } from '../lib/i18n'
import { colors } from '../theme'
import { Kolam } from './Kolam'
import { T } from './Text'
import { Button, Screen } from './ui'

/** Tabs that are still being moved to the new app. */
export function ComingSoon({ title, emoji }: { title: string; emoji: string }) {
  const { t } = useI18n()
  return (
    <Screen>
      <View style={{ alignItems: 'center', paddingVertical: 48, gap: 12 }}>
        <T size={48}>{emoji}</T>
        <T weight="display" size={24}>
          {t(title)}
        </T>
        <T color={colors.muted} style={{ textAlign: 'center', maxWidth: 280 }}>
          {t('This screen is coming in the next update of the app. Meanwhile you can use it on the website.')}
        </T>
        <Kolam size={72} opacity={0.5} />
      </View>
      <Button variant="outline" title={t('Open the website')} onPress={() => Linking.openURL(API_BASE)} />
    </Screen>
  )
}
