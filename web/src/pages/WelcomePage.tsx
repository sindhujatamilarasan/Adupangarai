import { useState } from 'react'
import { useNavigate, useSearchParams } from 'react-router-dom'
import { api, ApiError, type User } from '../api'
import { useAuth } from '../auth'
import Kolam from '../components/Kolam'
import { Alert, Button, Field } from '../components/ui'
import { tk, useI18n } from '../i18n'

/** Drawn icons (not text symbols), so they show the same on every phone. */
const Tick = () => (
  <svg viewBox="0 0 24 24" className="mt-0.5 size-4 shrink-0 text-leaf" fill="none" stroke="currentColor" strokeWidth={2.6} strokeLinecap="round" strokeLinejoin="round" aria-hidden>
    <path d="M5 12.5l4.5 4.5L19 7.5" />
  </svg>
)
const Arrow = ({ back = false }: { back?: boolean }) => (
  <svg viewBox="0 0 24 24" className={`inline size-5 ${back ? 'rotate-180' : ''}`} fill="none" stroke="currentColor" strokeWidth={2.2} strokeLinecap="round" strokeLinejoin="round" aria-hidden>
    <path d="M5 12h14M13 6l6 6-6 6" />
  </svg>
)

/** What the app does, why it helps, and how to use it: one slide per part of the app. */
const SLIDES = [
  {
    emoji: '🧺',
    title: tk('Know what’s in your kitchen'),
    why: tk('No more forgotten vegetables or buying what you already have.'),
    how: [tk('Add rice, dal, vegetables, milk… with how much you have'), tk('Get a reminder before things expire'), tk('Say it: “I bought 1 kg tomato and 2 litres milk”')],
  },
  {
    emoji: '🍳',
    title: tk('What can I cook now?'),
    why: tk('See dishes you can make right now with what you have.'),
    how: [tk('Ready, almost ready, and “cook this first” before it spoils'), tk('Recipes scale to the number of people'), tk('Tap “Cook” and the ingredients come out of your kitchen')],
  },
  {
    emoji: '📅',
    title: tk('Plan the week in one tap'),
    why: tk('No daily “what to cook today?” worry.'),
    how: [tk('Smart plan: veg / non-veg, high protein, lighter, or your calorie goal'), tk('No two similar dishes on one day'), tk('Print it or save an A4 PDF for the fridge')],
  },
  {
    emoji: '🛒',
    title: tk('A grocery list that thinks'),
    why: tk('Buy only what’s missing for your plan. Save money, less waste.'),
    how: [tk('The list subtracts what you already have'), tk('Tick items as you shop'), tk('One tap adds them back to your kitchen')],
  },
  {
    emoji: '💪',
    title: tk('Your friendly health coach'),
    why: tk('Eat well for your goal: lose, gain or stay fit.'),
    how: [tk('A daily calorie and protein target made for you'), tk('Tick meals you ate, add extras like “2 vada and a coffee”'), tk('10,000 steps challenge, streaks and badges')],
  },
  {
    emoji: '🎙️',
    title: tk('Speak in Tamil or English'),
    why: tk('Use the app the way you talk at home.'),
    how: [tk('Switch language anytime with த / EN at the top'), tk('Tap the mic to add items, recipes or food by voice'), tk('Nothing is saved until you check and confirm')],
  },
]

/**
 * First thing after sign-up: name the kitchen, then a short tour.
 * Shown until the person finishes or skips it; Profile can show the tour again (?tour=1).
 */
export default function WelcomePage() {
  const { user, setUser } = useAuth()
  const { t, lang, setLang } = useI18n()
  const navigate = useNavigate()
  const [params, setParams] = useSearchParams()
  const tourOnly = params.get('tour') === '1'
  // The step lives in the address (?step=2), so switching language (which reloads the page) keeps your place.
  const step = Number(params.get('step') ?? (tourOnly ? 1 : 0))
  const setStep = (n: number) => setParams({ ...(tourOnly ? { tour: '1' } : {}), step: String(n) }, { replace: true })
  const [kitchen, setKitchen] = useState(user!.household.name)
  const [error, setError] = useState('')
  const [busy, setBusy] = useState(false)
  const last = SLIDES.length + 1

  async function saveKitchen() {
    setBusy(true)
    setError('')
    try {
      const r = await api<{ user: User }>('/profile', { method: 'PUT', body: { household_name: kitchen.trim() } })
      setUser(r.user)
      setStep(1)
    } catch (e) {
      setError((e as ApiError).message)
    } finally {
      setBusy(false)
    }
  }

  /** Mark the welcome as done (so it isn't shown again) and go on. */
  async function finish(to: string) {
    setBusy(true)
    try {
      const r = await api<{ user: User }>('/profile', { method: 'PUT', body: { onboarded: true } })
      setUser(r.user)
    } catch {
      /* not fatal: they'd just see the tour once more */
    }
    navigate(to, { replace: true })
  }

  const slide = SLIDES[step - 1]
  const suggestions = [t('{name}’s Kitchen', { name: user!.name.split(' ')[0] }), t('Amma’s Kitchen'), t('Home Kitchen')]

  return (
    <main className="mx-auto flex min-h-svh max-w-md flex-col px-6 pt-[calc(1.5rem+env(safe-area-inset-top))] pb-[calc(1.5rem+env(safe-area-inset-bottom))]">
      {/* Top: progress + language + skip */}
      <div className="flex items-center justify-between gap-3">
        <div className="flex gap-1.5" aria-label={t('Step {n} of {total}', { n: step + 1, total: last + 1 })}>
          {Array.from({ length: last + 1 }, (_, i) => (
            <span key={i} className={`h-1.5 rounded-full transition-all ${i === step ? 'w-6 bg-brand' : i < step ? 'w-1.5 bg-brand/50' : 'w-1.5 bg-line'}`} />
          ))}
        </div>
        <div className="flex items-center gap-3">
          <button onClick={() => setLang(lang === 'ta' ? 'en' : 'ta')} className="rounded-full border border-line bg-white px-3 py-1 font-tamil text-sm font-semibold">
            {lang === 'ta' ? 'EN' : 'த'}
          </button>
          {step > 0 && step < last && (
            <button onClick={() => finish('/')} disabled={busy} className="text-sm font-semibold text-muted">
              {t('Skip')}
            </button>
          )}
        </div>
      </div>

      <div className="flex flex-1 flex-col justify-center py-8">
        {step === 0 && (
          <div className="space-y-6">
            <div className="text-center">
              <img src="/logo.svg" alt="" className="mx-auto size-20 rounded-[1.4rem] shadow-[0_8px_24px_rgb(122_67_32/0.25)]" />
              <h1 className="mt-5 font-display text-[1.9rem] leading-tight font-semibold">{t('Welcome, {name}!', { name: user!.name.split(' ')[0] })}</h1>
              <p className="mt-2 text-muted">{t('Let’s set up your kitchen. What should we call it?')}</p>
            </div>
            {error && <Alert kind="error">{error}</Alert>}
            <Field label={t('Kitchen name')} value={kitchen} onChange={(e) => setKitchen(e.target.value)} maxLength={100} autoFocus />
            <div className="flex flex-wrap gap-2">
              {suggestions.map((s) => (
                <button key={s} onClick={() => setKitchen(s)} className={`rounded-full border px-3 py-1.5 text-sm font-semibold ${kitchen === s ? 'border-brand bg-brand/5 text-brand' : 'border-line bg-white text-muted'}`}>
                  {s}
                </button>
              ))}
            </div>
            <p className="text-xs text-muted">{t('Everyone who shares this kitchen sees the same stock, plan and grocery list.')}</p>
          </div>
        )}

        {slide && (
          <div key={step} className="text-center">
            <div className="relative mx-auto grid size-44 place-items-center">
              <Kolam classic className="absolute inset-0 size-44 text-brand opacity-25" strokeWidth={0.05} />
              <span className="relative grid size-24 place-items-center rounded-full bg-white text-5xl shadow-[0_8px_30px_rgb(122_67_32/0.15)]">{slide.emoji}</span>
            </div>
            <h1 className="mt-6 font-display text-[1.75rem] leading-tight font-semibold">{t(slide.title)}</h1>
            <p className="mx-auto mt-2 max-w-xs text-muted">{t(slide.why)}</p>
            <ul className="mx-auto mt-6 max-w-sm space-y-2.5 text-left">
              {slide.how.map((h) => (
                <li key={h} className="card flex items-start gap-3 px-4 py-3 text-sm">
                  <Tick />
                  <span>{t(h)}</span>
                </li>
              ))}
            </ul>
          </div>
        )}

        {step === last && (
          <div className="text-center">
            <Kolam classic animate className="mx-auto size-28 text-brand" strokeWidth={0.06} />
            <h1 className="mt-5 font-display text-[1.9rem] leading-tight font-semibold">{t('You’re all set! 🎉')}</h1>
            <p className="mx-auto mt-2 max-w-xs text-muted">{t('Start by adding what you have at home. Everything else works from there.')}</p>
            <div className="mt-8 space-y-3">
              <Button onClick={() => finish('/kitchen?add=1')} loading={busy}>
                🧺 {t('Add what’s in my kitchen')}
              </Button>
              <button onClick={() => finish('/')} disabled={busy} className="w-full rounded-xl border border-line bg-white py-3.5 font-semibold text-brand">
                {t('Explore the app first')}
              </button>
            </div>
          </div>
        )}
      </div>

      {/* Bottom navigation */}
      {step === 0 && (
        <Button onClick={saveKitchen} loading={busy} disabled={!kitchen.trim()}>
          {t('Continue')} <Arrow />
        </Button>
      )}
      {slide && (
        <div className="flex gap-3">
          {step > (tourOnly ? 1 : 0) && (
            <button onClick={() => setStep(step - 1)} aria-label={t('Back')} className="rounded-xl border border-line bg-white px-5 font-semibold text-muted">
              <Arrow back />
            </button>
          )}
          <Button onClick={() => setStep(step + 1)}>
            {step === SLIDES.length ? t('Finish') : t('Next')} <Arrow />
          </Button>
        </div>
      )}
    </main>
  )
}
