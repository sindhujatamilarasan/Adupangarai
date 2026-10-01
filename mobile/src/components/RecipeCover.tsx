import { Image, View, type ImageStyle, type ViewStyle } from 'react-native'
import { API_BASE, type MealType } from '../lib/api'
import { colors } from '../theme'
import { T } from './Text'

const looks: Record<MealType, { emoji: string; bg: string }> = {
  breakfast: { emoji: '🍳', bg: '#f8ead3' },
  lunch: { emoji: '🍛', bg: '#f5e3d3' },
  snack: { emoji: '☕', bg: '#efe8dd' },
  dinner: { emoji: '🍲', bg: '#f0e0d2' },
}

/** Recipe photo, or a warm meal-type tile when there is none. */
export function RecipeCover({ recipe, size = 48, style }: { recipe: { image_url: string | null; meal_type?: MealType }; size?: number | ViewStyle['width']; style?: ViewStyle }) {
  const look = looks[recipe.meal_type ?? 'lunch']
  const box: ViewStyle = { width: size as number, height: typeof size === 'number' ? size : 96, borderRadius: 14, overflow: 'hidden', backgroundColor: look.bg, alignItems: 'center', justifyContent: 'center' }
  if (recipe.image_url) {
    const uri = recipe.image_url.startsWith('/') ? `${API_BASE}${recipe.image_url}` : recipe.image_url
    return <Image source={{ uri }} style={[box as ImageStyle, style as ImageStyle]} accessibilityIgnoresInvertColors />
  }
  return (
    <View style={[box, style]}>
      <T size={typeof size === 'number' ? size * 0.45 : 36}>{look.emoji}</T>
    </View>
  )
}

/** Emoji icon for an ingredient or category. */
export function IconTile({ icon, size = 32 }: { icon: string; size?: number }) {
  return (
    <View style={{ width: size, height: size, borderRadius: 10, backgroundColor: colors.cream, alignItems: 'center', justifyContent: 'center' }}>
      <T size={size * 0.5}>{icon}</T>
    </View>
  )
}

/** Indian veg / non-veg mark. */
export function VegDot({ veg }: { veg: boolean }) {
  const c = veg ? '#15803d' : '#b91c1c'
  return (
    <View style={{ width: 14, height: 14, borderWidth: 1.5, borderColor: c, borderRadius: 3, alignItems: 'center', justifyContent: 'center' }}>
      <View style={{ width: 6, height: 6, borderRadius: 3, backgroundColor: c }} />
    </View>
  )
}
