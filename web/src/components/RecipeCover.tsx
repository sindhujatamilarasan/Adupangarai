import type { MealType } from '../api'

const looks: Record<MealType, { emoji: string; bg: string }> = {
  breakfast: { emoji: '🍳', bg: 'from-[#fbf1e1] to-[#f5e2c4]' },
  lunch: { emoji: '🍛', bg: 'from-[#f9ede3] to-[#f1d9c4]' },
  snack: { emoji: '☕', bg: 'from-[#f5f1ea] to-[#e9e1d4]' },
  dinner: { emoji: '🍲', bg: 'from-[#f6e9df] to-[#ead3c1]' },
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
      {framed && <img src="/kolam-mark.svg" alt="" className="absolute right-2 bottom-2 size-7 opacity-25" aria-hidden />}
      <span className={big ? 'text-7xl drop-shadow-sm' : 'text-3xl'}>{look.emoji}</span>
    </div>
  )
}

export function IconTile({ icon, className = '' }: { icon: string; className?: string }) {
  return <span className={`grid shrink-0 place-items-center rounded-2xl bg-cream ${className || 'size-11 text-2xl'}`} aria-hidden>{icon}</span>
}
