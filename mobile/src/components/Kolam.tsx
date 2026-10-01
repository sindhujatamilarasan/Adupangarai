import { useMemo } from 'react'
import Svg, { Circle, G, Path, SvgXml } from 'react-native-svg'
import { inBetweenDots, pulliDots, sikkuLoopPaths } from '../../../shared/kolam'
import { LOGO_SVG } from '../../../shared/logo'
import { colors } from '../theme'

const [M, N] = [4, 4]

/** The classic 1-3-5-7-5-3-1 pulli kolam (a 4 x 4 sikku turned 45° with the in-between dots), as on the web. */
export function Kolam({ size = 64, color = colors.brand, strokeWidth = 0.07, opacity = 1 }: { size?: number; color?: string; strokeWidth?: number; opacity?: number }) {
  const loops = useMemo(() => sikkuLoopPaths(M, N), [])
  const dots = useMemo(() => [...pulliDots(M, N), ...inBetweenDots(M, N)], [])
  const [m, n] = [M, N]
  const r = Math.hypot(m, n) / 2 + 0.75
  return (
    <Svg width={size} height={size} viewBox={`${m / 2 - r} ${n / 2 - r} ${2 * r} ${2 * r}`} opacity={opacity}>
      <G rotation={45} origin={`${m / 2}, ${n / 2}`}>
        {loops.map((d, i) => (
          <Path key={i} d={d} fill="none" stroke={color} strokeWidth={strokeWidth} strokeLinecap="round" strokeLinejoin="round" />
        ))}
        {dots.map(([x, y]) => (
          <Circle key={`${x}-${y}`} cx={x} cy={y} r={strokeWidth * 1.3} fill={colors.accent} />
        ))}
      </G>
    </Svg>
  )
}

export function Logo({ size = 40 }: { size?: number }) {
  return <SvgXml xml={LOGO_SVG} width={size} height={size} />
}
