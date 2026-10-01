import type { ColorValue } from 'react-native'
import Svg, { Path } from 'react-native-svg'

/** Line icons (same drawings as the web app's bottom bar). */
export type IconName = keyof typeof ICONS
export const ICONS = {
  home: 'M3 11l9-7 9 7v9a1 1 0 01-1 1h-5v-6H9v6H4a1 1 0 01-1-1z',
  kitchen: 'M5 3h14v6H5zM5 9h14v12H5zM9 6h1M9 13v4',
  cook: 'M4 12h16a0 0 0 010 0 8 8 0 01-16 0zM12 4v3M8 5v2M16 5v2M2 12h2M20 12h2',
  planner: 'M4 5h16v16H4zM4 10h16M9 3v4M15 3v4',
  coach: 'M3 12h4l2.5-6 4 12 2.5-6H21',
  groceries: 'M3 4h2l2.5 11h11L21 7H6.5M9 20a1 1 0 100-2 1 1 0 000 2zM18 20a1 1 0 100-2 1 1 0 000 2z',
  eye: 'M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12zM12 9a3 3 0 100 6 3 3 0 000-6z',
  eyeOff: 'M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12zM12 9a3 3 0 100 6 3 3 0 000-6zM4 4l16 16',
  back: 'M15 5l-7 7 7 7',
  chevron: 'M9 5l7 7-7 7',
}

export function Icon({ name, size = 22, color, strokeWidth = 1.6 }: { name: keyof typeof ICONS; size?: number; color: ColorValue; strokeWidth?: number }) {
  return (
    <Svg width={size} height={size} viewBox="0 0 24 24" fill="none" stroke={color} strokeWidth={strokeWidth} strokeLinecap="round" strokeLinejoin="round">
      <Path d={ICONS[name]} />
    </Svg>
  )
}
