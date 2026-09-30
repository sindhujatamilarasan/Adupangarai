import { createContext, useCallback, useContext, useMemo, useState, type ReactNode } from 'react'
import ta from './locales/ta'

export type Lang = 'en' | 'ta'
const KEY = 'adupangarai.lang'

/** Current language outside React (the API client sends it as Accept-Language). */
// oxlint-disable-next-line react/only-export-components
export function currentLang(): Lang {
  try {
    return localStorage.getItem(KEY) === 'ta' ? 'ta' : 'en'
  } catch {
    return 'en'
  }
}

/** Whether this device has an explicit language choice (the latest tap wins over the account's saved one). */
// oxlint-disable-next-line react/only-export-components
export function hasStoredLang(): boolean {
  try {
    return localStorage.getItem(KEY) !== null
  } catch {
    return false
  }
}

/**
 * English text is the key: t('Kitchen') -> 'சமையலறை' in Tamil, 'Kitchen' in English.
 * Placeholders: t('{count} left', { count: 3 }). Missing Tamil falls back to English.
 */
// oxlint-disable-next-line react/only-export-components
export function translate(lang: Lang, key: string, vars?: Record<string, string | number>): string {
  const text = lang === 'ta' ? (ta[key] ?? key) : key
  return vars ? text.replace(/\{(\w+)\}/g, (_, k) => String(vars[k] ?? `{${k}}`)) : text
}

type I18n = {
  lang: Lang
  setLang: (l: Lang) => void
  t: (key: string, vars?: Record<string, string | number>) => string
  /** Locale-aware date, e.g. fmtDate('2026-10-05', { weekday: 'long' }) */
  fmtDate: (iso: string, opts: Intl.DateTimeFormatOptions) => string
}

const I18nContext = createContext<I18n | null>(null)

export function I18nProvider({ children }: { children: ReactNode }) {
  const [lang, setLangState] = useState<Lang>(currentLang)

  const setLang = useCallback((l: Lang) => {
    try {
      localStorage.setItem(KEY, l)
    } catch {
      /* private mode: language just won't persist */
    }
    document.documentElement.lang = l
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

// oxlint-disable-next-line react/only-export-components -- hook lives beside its provider
export function useI18n() {
  const ctx = useContext(I18nContext)
  if (!ctx) throw new Error('useI18n must be used inside I18nProvider')
  return ctx
}

/** Marks text defined outside components as translatable (translated later with t()). */
// oxlint-disable-next-line react/only-export-components
export const tk = (s: string) => s

/** Keys chosen at runtime (from API values); listed so the completeness test sees them. */
// oxlint-disable-next-line react/only-export-components
export const RUNTIME_KEYS = [
  tk('breakfast'), tk('lunch'), tk('snack'), tk('dinner'),
  tk('pantry'), tk('fridge'), tk('freezer'),
  tk('piece'), tk('packet'), tk('cup'),
]

/** Unit names that are words (piece, packet, cup) get translated; symbols (g, kg, ml...) stay. */
// oxlint-disable-next-line react/only-export-components
export const unitLabel = (lang: Lang, unit: string | null | undefined) => (unit ? translate(lang, unit) : '')

/** Localised display name from the API (`label`), falling back to the canonical name. */
// oxlint-disable-next-line react/only-export-components
export const nm = (x: { label?: string | null; name: string }) => x.label || x.name
