import assert from 'node:assert/strict'
import { test } from 'node:test'
import { fileURLToPath } from 'node:url'
import ta from '../../../shared/ta.ts'
import { collectKeys } from '../../../shared/i18nKeys.ts'

const src = fileURLToPath(new URL('..', import.meta.url))

test('every text used in the mobile app has a Tamil translation', () => {
  const missing = [...collectKeys(src)].filter((k) => !(k in ta))
  assert.deepEqual(missing, [], `Missing Tamil for:\n  ${missing.join('\n  ')}`)
})
