import { useMemo } from 'react'
import { inBetweenDots, pulliDots, sikkuLoopPaths } from '../lib/kolam'

type Props = {
  m?: number
  n?: number
  /** Diamond (45°) like a doorstep kolam, or square like a coaster. */
  diamond?: boolean
  /**
   * The classic 1-3-5-7-5-3-1 pulli kolam: a 4 x 4 sikku turned 45° with the in-between dots.
   * Overrides m, n and diamond.
   */
  classic?: boolean
  /** Draw the line in, like a hand drawing it. */
  animate?: boolean
  className?: string
  strokeWidth?: number
}

/** A real sikku kolam, generated (see lib/kolam.ts). Colours follow currentColor. */
export default function Kolam({ m = 3, n = 4, diamond = false, classic = false, animate = false, className = '', strokeWidth = 0.07 }: Props) {
  if (classic) [m, n, diamond] = [4, 4, true]
  const loops = useMemo(() => sikkuLoopPaths(m, n), [m, n])
  const dots = useMemo(() => [...pulliDots(m, n), ...(classic ? inBetweenDots(m, n) : [])], [m, n, classic])
  const r = Math.hypot(m, n) / 2 + 0.75
  // Diamonds need a square frame; square kolams (incl. 1-row strips) get a tight one.
  const viewBox = diamond ? `${m / 2 - r} ${n / 2 - r} ${2 * r} ${2 * r}` : `-0.8 -0.8 ${m + 1.6} ${n + 1.6}`
  return (
    <svg viewBox={viewBox} className={className} aria-hidden>
      <g transform={diamond ? `rotate(45 ${m / 2} ${n / 2})` : undefined}>
        {loops.map((d, i) => (
          <path
            key={i}
            d={d}
            pathLength={1}
            fill="none"
            stroke="currentColor"
            strokeWidth={strokeWidth}
            strokeLinecap="round"
            strokeLinejoin="round"
            className={animate ? 'kolam-draw' : undefined}
          />
        ))}
        {dots.map(([x, y]) => (
          <circle key={`${x}-${y}`} cx={x} cy={y} r={strokeWidth * 1.3} className="fill-accent" />
        ))}
      </g>
    </svg>
  )
}
