import type { MealType } from '../api'

const looks: Record<MealType, { emoji: string; bg: string }> = {
  breakfast: { emoji: '🍳', bg: 'from-amber-200 via-orange-200 to-rose-200' },
  lunch: { emoji: '🍛', bg: 'from-orange-300 via-amber-200 to-yellow-100' },
  snack: { emoji: '☕', bg: 'from-stone-200 via-amber-100 to-orange-100' },
  dinner: { emoji: '🍲', bg: 'from-rose-300 via-orange-200 to-amber-100' },
}

/** Recipe photo, or a warm meal-type illustration when there is none. */
export default function RecipeCover({ recipe, className = '', big = false }: { recipe: { name: string; image_url: string | null; meal_type?: MealType }; className?: string; big?: boolean }) {
  const look = looks[recipe.meal_type ?? 'lunch']
  if (recipe.image_url) {
    return <img src={recipe.image_url} alt={recipe.name} loading="lazy" className={`object-cover ${className}`} />
  }
  return (
    <div className={`grid place-items-center bg-gradient-to-br ${look.bg} ${className}`} role="img" aria-label={recipe.name}>
      <span className={big ? 'text-7xl drop-shadow-sm' : 'text-3xl'}>{look.emoji}</span>
    </div>
  )
}

export function IconTile({ icon, className = '' }: { icon: string; className?: string }) {
  return <span className={`grid shrink-0 place-items-center rounded-2xl bg-cream ${className || 'size-11 text-2xl'}`} aria-hidden>{icon}</span>
}
