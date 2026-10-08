import { router } from 'expo-router'
import type { ReactNode } from 'react'
import { Pressable, ScrollView, View } from 'react-native'
import { IconTile, RecipeCover, VegDot } from '../../components/RecipeCover'
import { Kolam } from '../../components/Kolam'
import { T } from '../../components/Text'
import { Badge, Card, ErrorState, Screen, Spinner } from '../../components/ui'
import { fmtQty, useApi, type CoachResponse, type Dashboard } from '../../lib/api'
import { useAuth } from '../../lib/auth'
import { nm, unitLabel, useI18n } from '../../lib/i18n'
import { colors } from '../../theme'

function Section({ title, link, linkLabel, children }: { title: string; link?: () => void; linkLabel?: string; children: ReactNode }) {
  const { t } = useI18n()
  return (
    <Card>
      <View style={{ flexDirection: 'row', justifyContent: 'space-between', alignItems: 'flex-start', gap: 12, marginBottom: 8 }}>
        <T weight="display" size={18} style={{ flex: 1 }}>
          {title}
        </T>
        {link && (
          <Pressable onPress={link} hitSlop={8} style={{ paddingTop: 2 }}>
            <T weight="bold" size={14} color={colors.brand}>
              {linkLabel ?? t('See all')} →
            </T>
          </Pressable>
        )}
      </View>
      {children}
    </Card>
  )
}

const Muted = ({ children }: { children: ReactNode }) => (
  <T size={14} color={colors.muted}>
    {children}
  </T>
)

export default function Home() {
  const { user } = useAuth()
  const { t, lang } = useI18n()
  const res = useApi<Dashboard>('/dashboard')
  const coach = useApi<CoachResponse>('/coach')
  const d = res.data
  const hour = new Date().getHours()
  const greeting = t(hour < 12 ? 'Good morning' : hour < 17 ? 'Good afternoon' : 'Good evening')
  const days = (n: number | null) => (n === 0 ? t('Expires today') : n === 1 ? t('Expires tomorrow') : t('Expires in {n} days', { n: n ?? 0 }))
  const refresh = () => {
    res.reload()
    coach.reload()
  }

  return (
    <Screen onRefresh={refresh} refreshing={res.loading && !!d}>
      <View style={{ paddingTop: 4 }}>
        <View style={{ position: 'absolute', right: -16, top: -12 }}>
          <Kolam size={150} opacity={0.13} strokeWidth={0.05} />
        </View>
        <T size={14} color={colors.muted}>
          {greeting}
        </T>
        <T weight="display" size={32} style={{ lineHeight: 40 }}>
          {user!.name}
        </T>
        <T size={14} color={colors.muted}>
          {user!.household.name}
        </T>
      </View>

      {res.loading && !d && <Spinner label={t('Checking your kitchen…')} />}
      {res.error && !d && <ErrorState message={res.error} onRetry={res.reload} />}

      {coach.data && <CoachCard c={coach.data} />}

      {d && d.pantry_count === 0 && (
        <Pressable onPress={() => router.navigate('/kitchen')} style={{ backgroundColor: colors.brand, borderRadius: 20, padding: 20 }}>
          <T weight="semibold" size={17} color={colors.white}>
            {t('Start by stocking your kitchen')} 🧺
          </T>
          <T size={14} color={colors.white}>
            {t('Add what you have at home and we’ll show what you can cook.')}
          </T>
        </Pressable>
      )}

      {d && (
        <>
          <Section title={t('Today’s meals')} link={() => router.navigate('/planner')} linkLabel={t('Planner')}>
            {d.today_meals.length === 0 ? (
              <Muted>{t('Nothing planned for today.')}</Muted>
            ) : (
              d.today_meals.map((m, i) => (
                <View key={m.id} style={{ flexDirection: 'row', alignItems: 'center', gap: 12, paddingVertical: 10, borderTopWidth: i ? 1 : 0, borderTopColor: colors.line }}>
                  <RecipeCover recipe={m.recipe} size={48} />
                  <View style={{ flex: 1 }}>
                    <T weight="semibold" size={11} color={colors.muted} style={{ textTransform: 'uppercase', letterSpacing: 0.5 }}>
                      {t(m.meal_type)}
                    </T>
                    <View style={{ flexDirection: 'row', alignItems: 'center', gap: 6 }}>
                      <VegDot veg={m.recipe.is_veg} />
                      <T weight="semibold" numberOfLines={1} style={{ flex: 1 }}>
                        {nm(m.recipe)}
                      </T>
                    </View>
                  </View>
                  {m.cooked_at ? <Badge color="green">{t('Cooked')}</Badge> : m.can_cook_now ? <Badge color="green">{t('Ready')}</Badge> : <Badge color="amber">{t('Missing items')}</Badge>}
                </View>
              ))
            )}
          </Section>

          <Section title={t('What can I cook now?')} link={() => router.navigate('/cook')}>
            {d.cook_now.length === 0 ? (
              <Muted>{d.almost_count > 0 ? t('Nothing fully ready — {n} recipe(s) almost there.', { n: d.almost_count }) : t('Add more to your kitchen to unlock recipes.')}</Muted>
            ) : (
              <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={{ gap: 12 }}>
                {d.cook_now.map(({ recipe }) => (
                  <View key={recipe.id} style={{ width: 144 }}>
                    <RecipeCover recipe={recipe} size={144} style={{ height: 96, borderRadius: 16 }} />
                    <View style={{ flexDirection: 'row', alignItems: 'center', gap: 6, marginTop: 6 }}>
                      <VegDot veg={recipe.is_veg} />
                      <T weight="bold" size={14} numberOfLines={1} style={{ flex: 1 }}>
                        {nm(recipe)}
                      </T>
                    </View>
                    <T size={12} color={colors.muted}>
                      {t('{n} min', { n: recipe.total_time })}
                      {recipe.calories !== null ? ` · ${recipe.calories} kcal` : ''}
                    </T>
                  </View>
                ))}
              </ScrollView>
            )}
          </Section>

          {(d.expiring.length > 0 || d.expired_count > 0) && (
            <Section title={t('Use soon')} link={() => router.navigate('/kitchen')} linkLabel={t('Kitchen')}>
              {d.expiring.map((i) => (
                <View key={i.id} style={{ flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', paddingVertical: 4, gap: 8 }}>
                  <View style={{ flexDirection: 'row', alignItems: 'center', gap: 8, flex: 1 }}>
                    <IconTile icon={i.ingredient.display_icon} />
                    <T weight="semibold" size={14} numberOfLines={1} style={{ flex: 1 }}>
                      {nm(i.ingredient)}{' '}
                      <T size={14} color={colors.muted}>
                        · {fmtQty(i.quantity)} {unitLabel(lang, i.unit)}
                      </T>
                    </T>
                  </View>
                  <Badge color="amber">{days(i.days_to_expiry)}</Badge>
                </View>
              ))}
              {d.expired_count > 0 && (
                <T weight="semibold" size={14} color={colors.red} style={{ marginTop: 6 }}>
                  {t('{n} item(s) have expired.', { n: d.expired_count })}
                </T>
              )}
            </Section>
          )}

          <View style={{ flexDirection: 'row', gap: 16 }}>
            <Pressable style={{ flex: 1 }} onPress={() => router.navigate('/kitchen')}>
              <Card>
                <T weight="semibold" size={28}>
                  {d.low_stock.length}
                </T>
                <T weight="semibold" size={14} color={colors.muted}>
                  {t('Low stock')}
                </T>
              </Card>
            </Pressable>
            <Pressable style={{ flex: 1 }} onPress={() => router.navigate('/groceries')}>
              <Card>
                <T weight="semibold" size={28}>
                  {d.grocery_remaining}
                </T>
                <T weight="semibold" size={14} color={colors.muted}>
                  {t('Groceries to buy')}
                </T>
              </Card>
            </Pressable>
          </View>
        </>
      )}
    </Screen>
  )
}

/** Today's calories and steps, or an invite to set a goal. */
function CoachCard({ c }: { c: CoachResponse }) {
  const { t } = useI18n()
  if (!c.profile) {
    return (
      <Pressable onPress={() => router.navigate('/coach')}>
        <Card style={{ flexDirection: 'row', alignItems: 'center', gap: 16 }}>
          <View style={{ width: 48, height: 48, borderRadius: 16, backgroundColor: colors.amberBg, alignItems: 'center', justifyContent: 'center' }}>
            <T size={24}>🏅</T>
          </View>
          <View style={{ flex: 1 }}>
            <T weight="display" size={18}>
              {t('Your health coach')}
            </T>
            <T size={14} color={colors.muted}>
              {t('Set a calorie goal, take the 10k steps challenge and earn badges.')}
            </T>
          </View>
        </Card>
      </Pressable>
    )
  }
  const share = Math.min(c.today.calories / c.target.calories, 1)
  return (
    <Pressable onPress={() => router.navigate('/coach')}>
      <Card>
        <View style={{ flexDirection: 'row', justifyContent: 'space-between', alignItems: 'baseline' }}>
          <T weight="display" size={18}>
            {t('Coach')}
          </T>
          <T weight="bold" size={14} color={colors.brand}>
            {c.streaks.on_target > 0 ? `🔥 ${t('{n}-day streak', { n: c.streaks.on_target })}` : `${t('Open')} →`}
          </T>
        </View>
        <T size={14} color={colors.muted} style={{ marginTop: 4 }}>
          <T weight="semibold" size={20}>
            {Math.round(c.today.calories).toLocaleString('en-IN')}
          </T>{' '}
          / {c.target.calories.toLocaleString('en-IN')} kcal · 👟 {c.today.steps.toLocaleString('en-IN')}
        </T>
        <View style={{ height: 8, borderRadius: 4, backgroundColor: colors.line, marginTop: 8, overflow: 'hidden' }}>
          <View style={{ width: `${share * 100}%`, height: 8, backgroundColor: colors.leaf }} />
        </View>
      </Card>
    </Pressable>
  )
}
