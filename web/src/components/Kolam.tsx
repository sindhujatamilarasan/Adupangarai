import { useMemo } from 'react'
import { pulliDots, sikkuPath } from '../lib/kolam'

type Props = {
  m?: number
  n?: number
  /** Diamond (45°) like a doorstep kolam, or square like a coaster. */
  diamond?: boolean
  /** Draw the line in, like a hand drawing it. */
  animate?: boolean
  className?: string
  strokeWidth?: number
}

/** A real one-line sikku kolam, generated (see lib/kolam.ts). Colours follow currentColor. */
export default function Kolam({ m = 3, n = 4, diamond = false, animate = false, className = '', strokeWidth = 0.07 }: Props) {
  const d = useMemo(() => sikkuPath(m, n), [m, n])
  const dots = useMemo(() => pulliDots(m, n), [m, n])
  const r = Math.hypot(m, n) / 2 + 0.75
  // Diamonds need a square frame; square kolams (incl. 1-row strips) get a tight one.
  const viewBox = diamond ? `${m / 2 - r} ${n / 2 - r} ${2 * r} ${2 * r}` : `-0.8 -0.8 ${m + 1.6} ${n + 1.6}`
  return (
    <svg viewBox={viewBox} className={className} aria-hidden>
      <g transform={diamond ? `rotate(45 ${m / 2} ${n / 2})` : undefined}>
        <path
          d={d}
          pathLength={1}
          fill="none"
          stroke="currentColor"
          strokeWidth={strokeWidth}
          strokeLinecap="round"
          strokeLinejoin="round"
          className={animate ? 'kolam-draw' : undefined}
        />
        {dots.map(([x, y]) => (
          <circle key={`${x}-${y}`} cx={x} cy={y} r={strokeWidth * 1.3} className="fill-accent" />
        ))}
      </g>
    </svg>
  )
}
