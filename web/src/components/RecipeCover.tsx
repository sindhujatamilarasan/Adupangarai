import type { MealType } from '../api'

const looks: Record<MealType, { emoji: string; bg: string }> = {
  breakfast: { emoji: '🍳', bg: 'from-amber-50 via-orange-100 to-amber-200' },
  lunch: { emoji: '🍛', bg: 'from-orange-100 via-amber-100 to-orange-200' },
  snack: { emoji: '☕', bg: 'from-stone-100 via-amber-50 to-stone-200' },
  dinner: { emoji: '🍲', bg: 'from-orange-200 via-amber-200 to-orange-300' },
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
    return <img src={recipe.image_url} alt={recipe.name} loading="lazy" className={`object-cover ${className}`} />
  }
  return (
    <div className={`relative grid place-items-center bg-gradient-to-br ${look.bg} ${className}`} role="img" aria-label={recipe.name}>
      {framed && <span className="kolam-band-brown absolute inset-x-0 bottom-1.5 opacity-40" aria-hidden />}
      <span className={big ? 'text-7xl drop-shadow-sm' : 'text-3xl'}>{look.emoji}</span>
    </div>
  )
}

export function IconTile({ icon, className = '' }: { icon: string; className?: string }) {
  return <span className={`grid shrink-0 place-items-center rounded-2xl bg-cream ${className || 'size-11 text-2xl'}`} aria-hidden>{icon}</span>
}
