/**
 * Sikku (kambi) kolam generator.
 *
 * A sikku kolam is a "mirror curve": on an m x n grid of pulli (dots) the line runs
 * diagonally between dots, crosses straight through in the middle and turns back at
 * the edge with a teardrop loop. When gcd(m, n) = 1 it is one unbroken line.
 * Units: dots sit at (i + 0.5, j + 0.5); the drawing fits in roughly [-0.55, m + 0.55].
 */
type Pt = [number, number]

const BULGE = 0.46 // how far each stretch bows out around its dot
const PETAL_R = 0.2 // radius of the round loop at the rim

/** Points of a round loop just outside rim point b (normal n, swinging first towards t). */
function petal(b: Pt, nx: number, ny: number, tx: number, ty: number): Pt[] {
  const c: Pt = [b[0] + nx * PETAL_R * 1.15, b[1] + ny * PETAL_R * 1.15]
  return [-115, -60, 0, 60, 115].map((deg) => {
    const a = (deg * Math.PI) / 180
    return [c[0] + PETAL_R * (Math.cos(a) * nx + Math.sin(a) * tx), c[1] + PETAL_R * (Math.cos(a) * ny + Math.sin(a) * ty)] as Pt
  })
}

/**
 * Sikku kolam over any set of pulli. `cells` holds the dot positions as "i,j" (dot at i+0.5, j+0.5).
 * The line crosses straight over edges shared by two dots and turns back (with a petal) at the rim.
 */
export function sikkuRegion(cells: Set<string>): Pt[][] {
  const has = (i: number, j: number) => cells.has(`${i},${j}`)
  const seen = new Set<string>()
  const key = (a: Pt, b: Pt) => [a, b].map((p) => p.join(',')).sort().join('|')
  const loops: Pt[][] = []

  // Outward normal at an edge point if the line would leave the region there, else null.
  const rim = (q: Pt, dx: number, dy: number): Pt | null => {
    if (Number.isInteger(q[0])) {
      const next = dx > 0 ? q[0] : q[0] - 1
      return has(next, Math.floor(q[1])) ? null : [dx, 0]
    }
    const next = dy > 0 ? q[1] : q[1] - 1
    return has(Math.floor(q[0]), next) ? null : [0, dy]
  }

  const starts: [Pt, number, number][] = []
  for (const c of cells) {
    const [i, j] = c.split(',').map(Number)
    for (const d of [1, -1]) starts.push([[i + 0.5, j] as Pt, d, 1])
  }

  for (const [start, d0x, d0y] of starts) {
    if (!has(Math.floor(start[0] + d0x * 0.25), Math.floor(start[1] + d0y * 0.25))) continue
    if (seen.has(key(start, [start[0] + d0x * 0.5, start[1] + d0y * 0.5]))) continue

    let p = start
    let [dx, dy] = [d0x, d0y]
    const steps: [Pt, Pt, Pt | null][] = []
    do {
      const q: Pt = [p[0] + dx * 0.5, p[1] + dy * 0.5]
      seen.add(key(p, q))
      const n = rim(q, dx, dy)
      steps.push([p, q, n])
      if (n) [dx, dy] = n[0] ? [-dx, dy] : [dx, -dy]
      p = q
    } while (!(p[0] === start[0] && p[1] === start[1] && dx === d0x && dy === d0y))

    const pts: Pt[] = []
    for (const [a, b, n] of steps) {
      const cx = Math.floor((a[0] + b[0]) / 2) + 0.5
      const cy = Math.floor((a[1] + b[1]) / 2) + 0.5
      const mx = (a[0] + b[0]) / 2 - cx
      const my = (a[1] + b[1]) / 2 - cy
      const len = Math.hypot(mx, my)
      pts.push([cx + (mx / len) * BULGE, cy + (my / len) * BULGE])
      if (!n) {
        pts.push(b)
        continue
      }
      // Teardrop petal: cross at the rim point, swing out to the far side, round the tip, cross back.
      const [nx, ny] = n
      const [tx, ty] = [-ny, nx]
      const prev = pts[pts.length - 1]
      const s = (prev[0] - b[0]) * tx + (prev[1] - b[1]) * ty > 0 ? -1 : 1
      pts.push(b, ...petal(b, nx, ny, tx * s, ty * s), b)
    }
    loops.push(pts)
  }
  return loops
}

const rectCells = (m: number, n: number) =>
  new Set(Array.from({ length: m * n }, (_, k) => `${k % m},${Math.floor(k / m)}`))

/** Classic diamond pulli: rows 1-3-5-...-(2r+1)-...-5-3-1 (r = 3 gives the 1-3-5-7-5-3-1 kolam). */
export function diamondCells(r: number): Set<string> {
  const cells = new Set<string>()
  for (let i = 0; i <= 2 * r; i++) for (let j = 0; j <= 2 * r; j++) if (Math.abs(i - r) + Math.abs(j - r) <= r) cells.add(`${i},${j}`)
  return cells
}

export function sikkuLoops(m: number, n: number): Pt[][] {
  return sikkuRegion(rectCells(m, n))
}

/** Closed Catmull-Rom spline through the points, as an SVG path. */
function smoothPath(pts: Pt[]): string {
  const f = (v: number) => v.toFixed(3)
  const at = (i: number) => pts[(i + pts.length) % pts.length]
  let d = `M${f(pts[0][0])} ${f(pts[0][1])}`
  for (let i = 0; i < pts.length; i++) {
    const [p0, p1, p2, p3] = [at(i - 1), at(i), at(i + 1), at(i + 2)]
    const c1 = [p1[0] + (p2[0] - p0[0]) / 6, p1[1] + (p2[1] - p0[1]) / 6]
    const c2 = [p2[0] - (p3[0] - p1[0]) / 6, p2[1] - (p3[1] - p1[1]) / 6]
    d += `C${f(c1[0])} ${f(c1[1])} ${f(c2[0])} ${f(c2[1])} ${f(p2[0])} ${f(p2[1])}`
  }
  return `${d}Z`
}

export function sikkuPath(m: number, n: number): string {
  return sikkuLoops(m, n).map(smoothPath).join(' ')
}

/** One SVG path per closed line (so each can be animated on its own). */
export function sikkuLoopPaths(m: number, n: number): string[] {
  return sikkuLoops(m, n).map(smoothPath)
}

/** Pulli in the gaps between four dots; with the grid turned 45° an n x n kolam shows rows 1-3-5-…-(2n-1)-…-3-1. */
export function inBetweenDots(m: number, n: number): Pt[] {
  return Array.from({ length: (m - 1) * (n - 1) }, (_, k) => [(k % (m - 1)) + 1, Math.floor(k / (m - 1)) + 1] as Pt)
}

export function regionPath(cells: Set<string>): string {
  return sikkuRegion(cells).map(smoothPath).join(' ')
}

export function pulliDots(m: number, n: number): Pt[] {
  return Array.from({ length: m * n }, (_, k) => [(k % m) + 0.5, Math.floor(k / m) + 0.5] as Pt)
}

export function cellDots(cells: Set<string>): Pt[] {
  return [...cells].map((c) => c.split(',').map((v) => Number(v) + 0.5) as Pt)
}
