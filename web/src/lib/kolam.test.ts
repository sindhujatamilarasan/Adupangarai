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
