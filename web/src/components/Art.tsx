/**
 * Cartoon kitchen friends: flat, bright, smiling. Drawn in SVG so they're crisp on every phone
 * and need no downloads. Each fits a 120 x 120 box.
 */
export type ArtName = 'basket' | 'kadai' | 'tiffin' | 'jars' | 'idli' | 'mic'

const INK = '#2b1a10'

/** Two dot eyes, a smile and pink cheeks centred on (x, y); s scales it. */
function Face({ x, y, s = 1 }: { x: number; y: number; s?: number }) {
  return (
    <g transform={`translate(${x} ${y}) scale(${s})`}>
      <circle cx={-5} cy={-1} r={1.9} fill={INK} />
      <circle cx={5} cy={-1} r={1.9} fill={INK} />
      <circle cx={-4.3} cy={-1.7} r={0.6} fill="#fff" />
      <circle cx={5.7} cy={-1.7} r={0.6} fill="#fff" />
      <path d="M-3 3 Q0 6 3 3" fill="none" stroke={INK} strokeWidth={1.5} strokeLinecap="round" />
      <ellipse cx={-8.5} cy={3} rx={2.6} ry={1.6} fill="#ff7aa2" opacity={0.7} />
      <ellipse cx={8.5} cy={3} rx={2.6} ry={1.6} fill="#ff7aa2" opacity={0.7} />
    </g>
  )
}

const Sparkle = ({ x, y, c = '#f2b705', s = 1 }: { x: number; y: number; c?: string; s?: number }) => (
  <path transform={`translate(${x} ${y}) scale(${s})`} d="M0 -5 Q1 -1 5 0 Q1 1 0 5 Q-1 1 -5 0 Q-1 -1 0 -5Z" fill={c} />
)

const Basket = () => (
  <>
    <ellipse cx={60} cy={110} rx={40} ry={5} fill={INK} opacity={0.1} />
    <path d="M30 60 Q60 14 90 60" fill="none" stroke="#a5561c" strokeWidth={6} strokeLinecap="round" />
    {/* brinjal */}
    <g transform="rotate(22 82 50)">
      <ellipse cx={82} cy={52} rx={12} ry={17} fill="#7b3fa0" />
      <path d="M74 36 Q82 30 90 36 L86 40 L82 36 L78 40Z" fill="#4f8a3c" />
      <ellipse cx={78} cy={46} rx={3} ry={6} fill="#fff" opacity={0.25} />
    </g>
    {/* carrot */}
    <path d="M52 62 L60 22 L68 62Z" fill="#f28c28" />
    <path d="M60 22 Q54 12 56 6 M60 22 Q60 10 63 5 M60 22 Q66 13 70 10" stroke="#4f8a3c" strokeWidth={3} strokeLinecap="round" fill="none" />
    {/* tomato with a face */}
    <circle cx={40} cy={52} r={16} fill="#e4572e" />
    <path d="M33 38 L40 42 L47 38 L44 44 L40 41 L36 44Z" fill="#4f8a3c" />
    <ellipse cx={34} cy={46} rx={3} ry={5} fill="#fff" opacity={0.3} />
    <Face x={41} y={54} s={0.8} />
    {/* basket */}
    <path d="M18 62 H102 L94 104 Q93 108 88 108 H32 Q27 108 26 104Z" fill="#e3a23b" />
    <path d="M24 74 H96 M27 86 H93 M29 98 H91" stroke="#b97818" strokeWidth={2.5} />
    <path d="M40 62 L42 108 M60 62 V108 M80 62 L78 108" stroke="#b97818" strokeWidth={2.5} />
    <rect x={14} y={57} width={92} height={10} rx={5} fill="#a5561c" />
    <Sparkle x={100} y={20} />
    <Sparkle x={16} y={30} c="#2e86ab" s={0.7} />
  </>
)

const Kadai = () => (
  <>
    <ellipse cx={60} cy={110} rx={42} ry={5} fill={INK} opacity={0.1} />
    {/* steam */}
    <path d="M44 34 Q38 26 44 18 Q50 10 44 2 M60 30 Q54 22 60 14 Q66 6 60 -2 M76 34 Q70 26 76 18 Q82 10 76 2" fill="none" stroke="#c9b8a8" strokeWidth={3.5} strokeLinecap="round" />
    {/* handles */}
    <path d="M14 56 Q4 56 6 64 Q8 70 18 66" fill="none" stroke="#3a2a20" strokeWidth={6} strokeLinecap="round" />
    <path d="M106 56 Q116 56 114 64 Q112 70 102 66" fill="none" stroke="#3a2a20" strokeWidth={6} strokeLinecap="round" />
    {/* pan */}
    <path d="M12 52 H108 Q106 104 60 104 Q14 104 12 52Z" fill="#3a2a20" />
    <ellipse cx={60} cy={52} rx={48} ry={9} fill="#5a4030" />
    <ellipse cx={60} cy={53} rx={42} ry={6} fill="#f2a516" />
    <circle cx={46} cy={52} r={3} fill="#4f8a3c" />
    <circle cx={70} cy={54} r={2.5} fill="#e4572e" />
    <circle cx={58} cy={51} r={2} fill="#4f8a3c" />
    <path d="M22 66 Q26 86 40 94" stroke="#fff" strokeWidth={3} opacity={0.15} fill="none" strokeLinecap="round" />
    <g transform="translate(60 80) scale(1.25)">
      <circle cx={-5} cy={-1} r={1.9} fill="#fff" />
      <circle cx={5} cy={-1} r={1.9} fill="#fff" />
      <path d="M-3 3 Q0 6 3 3" fill="none" stroke="#fff" strokeWidth={1.5} strokeLinecap="round" />
      <ellipse cx={-8.5} cy={3} rx={2.6} ry={1.6} fill="#ff7aa2" opacity={0.8} />
      <ellipse cx={8.5} cy={3} rx={2.6} ry={1.6} fill="#ff7aa2" opacity={0.8} />
    </g>
    <Sparkle x={104} y={24} />
  </>
)

const Tiffin = () => (
  <>
    <ellipse cx={60} cy={112} rx={32} ry={4.5} fill={INK} opacity={0.1} />
    {/* clip handle */}
    <path d="M36 30 V18 Q36 8 46 8 H74 Q84 8 84 18 V30" fill="none" stroke="#8d97a3" strokeWidth={5} />
    {[0, 1, 2].map((k) => (
      <g key={k}>
        <rect x={30} y={30 + k * 26} width={60} height={24} rx={6} fill={k === 1 ? '#e8ecf0' : '#d5dbe2'} />
        <rect x={30} y={30 + k * 26} width={60} height={5} rx={2.5} fill={['#e4572e', '#f2b705', '#2e86ab'][k]} />
        <rect x={34} y={38 + k * 26} width={4} height={12} rx={2} fill="#fff" opacity={0.7} />
      </g>
    ))}
    <rect x={26} y={28} width={6} height={80} rx={3} fill="#8d97a3" />
    <rect x={88} y={28} width={6} height={80} rx={3} fill="#8d97a3" />
    <Face x={60} y={68} s={1.1} />
    {/* little heart */}
    <path d="M100 30 C100 25 107 25 107 30 C107 25 114 25 114 30 C114 36 107 40 107 42 C107 40 100 36 100 30Z" fill="#e4572e" />
    <Sparkle x={14} y={40} c="#4f8a3c" s={0.8} />
  </>
)

const Jars = () => (
  <>
    <ellipse cx={60} cy={110} rx={46} ry={5} fill={INK} opacity={0.1} />
    {/* shelf */}
    <rect x={8} y={100} width={104} height={8} rx={3} fill="#a5561c" />
    {/* rice jar */}
    <rect x={14} y={44} width={34} height={56} rx={8} fill="#d5dbe2" />
    <rect x={14} y={60} width={34} height={40} rx={8} fill="#fff8ec" />
    <rect x={12} y={38} width={38} height={10} rx={4} fill="#8d97a3" />
    <Face x={31} y={78} s={0.9} />
    {/* dal jar */}
    <rect x={54} y={56} width={28} height={44} rx={7} fill="#d5dbe2" />
    <rect x={54} y={68} width={28} height={32} rx={7} fill="#f2b705" />
    <rect x={52} y={50} width={32} height={9} rx={4} fill="#e4572e" />
    <Face x={68} y={84} s={0.75} />
    {/* chilli jar */}
    <rect x={86} y={66} width={24} height={34} rx={6} fill="#d5dbe2" />
    <rect x={86} y={76} width={24} height={24} rx={6} fill="#c2410c" />
    <rect x={84} y={60} width={28} height={8} rx={4} fill="#4f8a3c" />
    <Sparkle x={98} y={40} />
    <Sparkle x={70} y={30} c="#a23b72" s={0.7} />
  </>
)

const Idli = () => (
  <>
    {/* banana leaf */}
    <path d="M6 82 Q40 50 114 62 Q96 104 30 104 Q10 100 6 82Z" fill="#4f8a3c" />
    <path d="M10 84 Q60 74 110 64" stroke="#7cb85f" strokeWidth={2} fill="none" />
    <path d="M30 80 L34 96 M52 76 L56 96 M74 72 L78 92 M94 68 L96 84" stroke="#7cb85f" strokeWidth={1.2} />
    {/* sambar bowl */}
    <ellipse cx={92} cy={66} rx={16} ry={6} fill="#8d97a3" />
    <path d="M76 66 Q78 84 92 84 Q106 84 108 66Z" fill="#b8c0c9" />
    <ellipse cx={92} cy={66} rx={14} ry={4.5} fill="#e07a1f" />
    <circle cx={88} cy={65} r={1.6} fill="#4f8a3c" />
    {/* idlis */}
    <ellipse cx={34} cy={74} rx={20} ry={13} fill="#fffdf6" stroke="#eadfc8" strokeWidth={2} />
    <Face x={34} y={74} s={0.85} />
    <ellipse cx={60} cy={66} rx={18} ry={12} fill="#fffdf6" stroke="#eadfc8" strokeWidth={2} />
    <Face x={60} y={66} s={0.75} />
    {/* chutney */}
    <circle cx={58} cy={90} r={8} fill="#f4efe0" />
    <circle cx={58} cy={90} r={2} fill="#e4572e" />
    <path d="M44 40 Q40 32 44 24 M60 36 Q56 28 60 20" fill="none" stroke="#c9b8a8" strokeWidth={3} strokeLinecap="round" />
    <Sparkle x={104} y={30} />
  </>
)

const Mic = () => (
  <>
    <ellipse cx={60} cy={112} rx={26} ry={4} fill={INK} opacity={0.1} />
    <rect x={42} y={14} width={36} height={58} rx={18} fill="#e4572e" />
    <path d="M48 26 H72 M48 34 H72" stroke="#fff" strokeWidth={2} opacity={0.35} />
    <Face x={60} y={50} s={1} />
    <path d="M32 54 Q32 86 60 86 Q88 86 88 54" fill="none" stroke="#8d97a3" strokeWidth={5} strokeLinecap="round" />
    <path d="M60 86 V104 M46 106 H74" stroke="#8d97a3" strokeWidth={5} strokeLinecap="round" />
    {/* speech bubbles: Tamil and English */}
    <rect x={88} y={14} width={28} height={20} rx={8} fill="#2e86ab" />
    <text x={102} y={28} fontSize={12} textAnchor="middle" fill="#fff" fontWeight={700}>த</text>
    <rect x={4} y={22} width={28} height={20} rx={8} fill="#f2b705" />
    <text x={18} y={36} fontSize={11} textAnchor="middle" fill={INK} fontWeight={700}>Hi</text>
  </>
)

const ART: Record<ArtName, () => React.JSX.Element> = { basket: Basket, kadai: Kadai, tiffin: Tiffin, jars: Jars, idli: Idli, mic: Mic }

export default function Art({ name, className = 'size-28' }: { name: ArtName; className?: string }) {
  const Pic = ART[name]
  return (
    <svg viewBox="0 -4 120 124" className={className} aria-hidden>
      <Pic />
    </svg>
  )
}
