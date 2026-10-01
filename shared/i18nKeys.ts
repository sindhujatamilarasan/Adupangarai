import { readdirSync, readFileSync, statSync } from 'node:fs'
import { join } from 'node:path'

/** All literal keys passed to t('…') / tk('…') / translate(lang, '…') in the app source. */
export function collectKeys(dir: string): Set<string> {
  const keys = new Set<string>()
  const re = /\b(?:t|tk|translate\(\s*\w+\s*,)\(?\s*(['"])((?:\\.|(?!\1).)*)\1/g
  const walk = (d: string) => {
    for (const f of readdirSync(d)) {
      const p = join(d, f)
      if (statSync(p).isDirectory()) walk(p)
      else if (/\.(tsx?)$/.test(f) && !/\.test\.ts$/.test(f) && !p.includes('locales')) {
        for (const m of readFileSync(p, 'utf8').matchAll(re)) keys.add(m[2].replace(/\\'/g, "'"))
      }
    }
  }
  walk(dir)
  return keys
}
