import assert from 'node:assert/strict'
import { test } from 'node:test'
import { pulliDots, sikkuLoops, sikkuPath } from './kolam.ts'

const gcd = (a: number, b: number): number => (b ? gcd(b, a % b) : a)

test('a sikku kolam is one unbroken line exactly when gcd(m, n) = 1', () => {
  for (const [m, n] of [[2, 3], [3, 4], [4, 5], [5, 6], [3, 3], [4, 6], [5, 5]]) {
    assert.equal(sikkuLoops(m, n).length, gcd(m, n), `${m}x${n}`)
  }
})

test('the line stays near its dot grid and the path is closed', () => {
  const [m, n] = [3, 4]
  for (const loop of sikkuLoops(m, n)) {
    for (const [x, y] of loop) {
      assert.ok(x > -0.6 && x < m + 0.6 && y > -0.6 && y < n + 0.6, `${x},${y} outside`)
    }
  }
  assert.match(sikkuPath(m, n), /^M.*Z$/)
  assert.equal(pulliDots(m, n).length, 12)
})

test('the classic 1-3-5-7-5-3-1 kolam: 25 pulli in rows of 1,3,5,7,5,3,1 once turned 45°', async () => {
  const { inBetweenDots } = await import('./kolam.ts')
  const dots = [...pulliDots(4, 4), ...inBetweenDots(4, 4)]
  assert.equal(dots.length, 25)
  // Turning 45° makes rows out of x + y (the anti-diagonals); count dots per row.
  const rows = new Map<number, number>()
  for (const [x, y] of dots) rows.set(x + y, (rows.get(x + y) ?? 0) + 1)
  assert.deepEqual([...rows.entries()].sort((a, b) => a[0] - b[0]).map(([, c]) => c), [1, 3, 5, 7, 5, 3, 1])
})
