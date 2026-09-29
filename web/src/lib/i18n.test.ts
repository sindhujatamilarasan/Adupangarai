import assert from 'node:assert/strict'
import { test } from 'node:test'
import { fileURLToPath } from 'node:url'
import ta from '../locales/ta.ts'
import { collectKeys } from '../../scripts/i18nKeys.ts'

const src = fileURLToPath(new URL('..', import.meta.url))
const placeholders = (s: string) => [...s.matchAll(/\{(\w+)\}/g)].map((m) => m[1]).sort()

test('every text used in the app has a Tamil translation', () => {
  const missing = [...collectKeys(src)].filter((k) => !(k in ta))
  assert.deepEqual(missing, [], `Missing Tamil for:\n  ${missing.join('\n  ')}`)
})

test('translations keep their {placeholders}', () => {
  for (const [en, tamil] of Object.entries(ta)) {
    assert.deepEqual(placeholders(tamil), placeholders(en), `Placeholder mismatch in "${en}"`)
  }
})
