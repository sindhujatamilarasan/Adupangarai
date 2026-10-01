import { Tabs } from 'expo-router'
import { Icon, type IconName } from '../../components/Icon'
import { TopBar } from '../../components/TopBar'
import { useI18n } from '../../lib/i18n'
import { colors, fonts } from '../../theme'

const TABS: { name: string; title: string; icon: IconName }[] = [
  { name: 'index', title: 'Home', icon: 'home' },
  { name: 'kitchen', title: 'Kitchen', icon: 'kitchen' },
  { name: 'cook', title: 'Cook', icon: 'cook' },
  { name: 'planner', title: 'Planner', icon: 'planner' },
  { name: 'coach', title: 'Coach', icon: 'coach' },
  { name: 'groceries', title: 'Groceries', icon: 'groceries' },
]

export default function TabsLayout() {
  const { t, lang } = useI18n()
  return (
    <Tabs
      screenOptions={{
        header: () => <TopBar />,
        tabBarActiveTintColor: colors.brand,
        tabBarInactiveTintColor: colors.muted,
        tabBarStyle: { backgroundColor: colors.white, borderTopColor: colors.line },
        tabBarLabelStyle: { fontFamily: lang === 'ta' ? fonts.taSemibold : fonts.semibold, fontSize: lang === 'ta' ? 10 : 11 },
        sceneStyle: { backgroundColor: colors.cream },
      }}
    >
      {TABS.map((tab) => (
        <Tabs.Screen key={tab.name} name={tab.name} options={{ title: t(tab.title), tabBarIcon: ({ color }) => <Icon name={tab.icon} color={color} /> }} />
      ))}
    </Tabs>
  )
}
