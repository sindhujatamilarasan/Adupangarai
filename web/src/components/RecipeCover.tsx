import type { MealType } from '../api'
import Kolam from './Kolam'
import { assetUrl } from '../lib/native'

const looks: Record<MealType, { emoji: string; bg: string }> = {
  breakfast: { emoji: '🍳', bg: 'from-[#fff4c2] to-[#ffd36b]' },
  lunch: { emoji: '🍛', bg: 'from-[#ffe1cf] to-[#ffb38a]' },
  snack: { emoji: '☕', bg: 'from-[#ffe0ec] to-[#f9b3cf]' },
  dinner: { emoji: '🍲', bg: 'from-[#dcf0f8] to-[#a9d6ea]' },
}

/** Recipe photo, or a warm meal-type illustration when there is none. */
export default function RecipeCover({
  recipe,
  className = '',
  big = false,
  framed = big,
}: {
  recipe: { name: string; image_url: string | null; meal_type?: MealType }
  className?: string
  big?: boolean
  /** Kolam strip along the bottom; only for larger covers. */
  framed?: boolean
}) {
  const look = looks[recipe.meal_type ?? 'lunch']
  if (recipe.image_url) {
    return <img src={assetUrl(recipe.image_url) ?? undefined} alt={recipe.name} loading="lazy" className={`object-cover ${className}`} />
  }
  return (
    <div className={`relative grid place-items-center bg-gradient-to-br ${look.bg} ${className}`} role="img" aria-label={recipe.name}>
      {framed && <Kolam classic colorful className="absolute right-1 bottom-1 size-10 opacity-50" strokeWidth={0.08} />}
      <span className={big ? 'text-7xl drop-shadow-sm' : 'text-3xl'}>{look.emoji}</span>
    </div>
  )
}

export function IconTile({ icon, className = '' }: { icon: string; className?: string }) {
  return <span className={`grid shrink-0 place-items-center rounded-2xl bg-cream ${className || 'size-11 text-2xl'}`} aria-hidden>{icon}</span>
}
