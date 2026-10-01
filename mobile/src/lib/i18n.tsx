import { createContext, useCallback, useContext, useMemo, useState, type ReactNode } from 'react'
import ta from '../../../shared/ta'
import { storage } from './storage'

export type Lang = 'en' | 'ta'
const KEY = 'adupangarai.lang'

/** The language for API requests (kept in memory; set before the first render). */
let current: Lang = 'en'
let stored = false
export const currentLang = () => current
export const hasStoredLang = () => stored

export async function loadLang(): Promise<Lang> {
  const v = await storage.get(KEY)
  stored = v !== null
  current = v === 'ta' ? 'ta' : 'en'
  return current
}

/** English text is the key: t('Kitchen') -> 'கிச்சன்'. Placeholders: t('{n} left', { n: 3 }). */
export function translate(lang: Lang, key: string, vars?: Record<string, string | number>): string {
  const text = lang === 'ta' ? (ta[key] ?? key) : key
  return vars ? text.replace(/\{(\w+)\}/g, (_, k) => String(vars[k] ?? `{${k}}`)) : text
}

/** Marks text defined outside components as translatable. */
export const tk = (s: string) => s

type I18n = {
  lang: Lang
  setLang: (l: Lang) => void
  t: (key: string, vars?: Record<string, string | number>) => string
  fmtDate: (iso: string, opts: Intl.DateTimeFormatOptions) => string
}
const I18nContext = createContext<I18n | null>(null)

export function I18nProvider({ initial, children }: { initial: Lang; children: ReactNode }) {
  const [lang, setLangState] = useState<Lang>(initial)
  const setLang = useCallback((l: Lang) => {
    current = l
    stored = true
    storage.set(KEY, l)
    setLangState(l)
  }, [])
  const value = useMemo<I18n>(
    () => ({
      lang,
      setLang,
      t: (key, vars) => translate(lang, key, vars),
      fmtDate: (iso, opts) => new Date(`${iso.slice(0, 10)}T00:00:00`).toLocaleDateString(lang === 'ta' ? 'ta-IN' : 'en-IN', opts),
    }),
    [lang, setLang],
  )
  return <I18nContext.Provider value={value}>{children}</I18nContext.Provider>
}

export function useI18n() {
  const ctx = useContext(I18nContext)
  if (!ctx) throw new Error('useI18n must be used inside I18nProvider')
  return ctx
}

/** Keys chosen at runtime (from API values); listed so the completeness test sees them. */
export const RUNTIME_KEYS = [tk('breakfast'), tk('lunch'), tk('snack'), tk('dinner'), tk('piece'), tk('packet'), tk('cup')]

/** Unit words get translated (piece, packet, cup); symbols (g, kg, ml…) stay. */
export const unitLabel = (lang: Lang, unit: string | null | undefined) => (unit ? translate(lang, unit) : '')

/** Localised name from the API (`label`), falling back to the canonical name. */
export const nm = (x: { label?: string | null; name: string }) => x.label || x.name
