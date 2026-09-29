import type { MealType } from '../api'

const looks: Record<MealType, { emoji: string; bg: string }> = {
  breakfast: { emoji: '🍳', bg: 'from-amber-100 via-yellow-100 to-amber-200' },
  lunch: { emoji: '🍛', bg: 'from-teal-100 via-emerald-50 to-amber-100' },
  snack: { emoji: '☕', bg: 'from-stone-100 via-amber-50 to-yellow-100' },
  dinner: { emoji: '🍲', bg: 'from-cyan-100 via-teal-100 to-teal-200' },
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
