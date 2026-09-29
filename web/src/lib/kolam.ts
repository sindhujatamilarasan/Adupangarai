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
const PETAL_W = 0.24
const PETAL_H = 0.52

export function sikkuLoops(m: number, n: number): Pt[][] {
  const seen = new Set<string>()
  const key = (a: Pt, b: Pt) => [a, b].map((p) => p.join(',')).sort().join('|')
  const edge = ([x, y]: Pt): Pt => [x === 0 ? -1 : x === m ? 1 : 0, y === 0 ? -1 : y === n ? 1 : 0]
  const loops: Pt[][] = []

  for (let i = 0; i < m; i++) {
    for (const d0 of [1, -1]) {
      const start: Pt = [i + 0.5, 0]
      if (seen.has(key(start, [start[0] + d0 * 0.5, 0.5]))) continue

      let p = start
      let [dx, dy] = [d0, 1]
      const steps: [Pt, Pt][] = []
      do {
        const q: Pt = [p[0] + dx * 0.5, p[1] + dy * 0.5]
        seen.add(key(p, q))
        steps.push([p, q])
        p = q
        const [nx, ny] = edge(p)
        if (nx) dx = -dx
        if (ny) dy = -dy
      } while (!(p[0] === start[0] && p[1] === start[1] && dx === d0 && dy === 1))

      const pts: Pt[] = []
      for (const [a, b] of steps) {
        const cx = Math.floor((a[0] + b[0]) / 2) + 0.5
        const cy = Math.floor((a[1] + b[1]) / 2) + 0.5
        const mx = (a[0] + b[0]) / 2 - cx
        const my = (a[1] + b[1]) / 2 - cy
        const len = Math.hypot(mx, my)
        pts.push([cx + (mx / len) * BULGE, cy + (my / len) * BULGE])

        let [nx, ny] = edge(b)
        if (!nx && !ny) {
          pts.push(b)
          continue
        }
        // Teardrop petal: cross at the edge point, swing out to the far side, round the tip, cross back.
        if (nx && ny) [nx, ny] = [nx * Math.SQRT1_2, ny * Math.SQRT1_2]
        const [tx, ty] = [-ny, nx]
        const prev = pts[pts.length - 1]
        const s = (prev[0] - b[0]) * tx + (prev[1] - b[1]) * ty > 0 ? -1 : 1
        const at = (h: number, w: number): Pt => [b[0] + nx * h + tx * w * s, b[1] + ny * h + ty * w * s]
        pts.push(b, at(PETAL_H * 0.55, PETAL_W), at(PETAL_H, 0), at(PETAL_H * 0.55, -PETAL_W), b)
      }
      loops.push(pts)
    }
  }
  return loops
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

export function pulliDots(m: number, n: number): Pt[] {
  return Array.from({ length: m * n }, (_, k) => [(k % m) + 0.5, Math.floor(k / m) + 0.5] as Pt)
}
