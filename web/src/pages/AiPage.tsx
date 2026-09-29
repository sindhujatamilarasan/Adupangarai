import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { AiPantrySheet, AiPlanSheet, AiRecipeSheet } from '../components/AiSheets'
import Kolam from '../components/Kolam'
import { Alert } from '../components/ui'
import { tk, useI18n } from '../i18n'

const actions = [
  { key: 'pantry', emoji: '🛍️', title: tk('Say what you bought'), text: tk('“1 kg chicken, a dozen eggs and 2 litres milk” → added to your kitchen after you check it.') },
  { key: 'recipe', emoji: '📝', title: tk('Say a recipe'), text: tk('Dictate a recipe in English or Tamil → a ready-to-save recipe with estimated nutrition.') },
  { key: 'plan', emoji: '📅', title: tk('Plan my meals'), text: tk('Balanced, high-protein or lighter days, picked from your recipes and what’s in your kitchen.') },
] as const

export default function AiPage() {
  const navigate = useNavigate()
  const { t } = useI18n()
  const [open, setOpen] = useState<(typeof actions)[number]['key'] | null>(null)
  const [done, setDone] = useState('')

  return (
    <div className="space-y-4">
      <header className="card relative overflow-hidden p-6">
        <Kolam m={2} n={3} diamond className="pointer-events-none absolute -top-3 -right-3 size-32 text-brand opacity-[.12]" strokeWidth={0.07} />
        <p className="text-xs font-semibold tracking-[.18em] text-accent uppercase">✦ {t('Assistant')}</p>
        <h1 className="mt-1 font-display text-[1.75rem] font-semibold tracking-tight">{t('AI mode')}</h1>
        <p className="mt-1 max-w-xs text-sm text-muted">{t('Talk to your kitchen. The AI only prepares drafts — you always check before anything is saved.')}</p>
      </header>

      {done && <Alert kind="success">{done}</Alert>}

      {actions.map((a) => (
        <button key={a.key} onClick={() => setOpen(a.key)} className="flex w-full items-start gap-4 card p-5 text-left active:scale-[.99]">
          <span className="grid size-14 shrink-0 place-items-center rounded-2xl bg-cream text-3xl">{a.emoji}</span>
          <span>
            <span className="block font-semibold">{t(a.title)}</span>
            <span className="block text-sm text-muted">{t(a.text)}</span>
          </span>
        </button>
      ))}

      <p className="px-2 text-center text-xs text-muted">{t('Runs on a free AI model. Voice uses your browser’s speech recognition (Chrome/Edge/Android).')}</p>

      {open === 'pantry' && (
        <AiPantrySheet
          onClose={() => setOpen(null)}
          onDone={(m) => {
            setOpen(null)
            setDone(m)
          }}
        />
      )}
      {open === 'recipe' && <AiRecipeSheet onClose={() => setOpen(null)} />}
      {open === 'plan' && (
        <AiPlanSheet
          onClose={() => setOpen(null)}
          onDone={(m) => {
            setOpen(null)
            navigate('/planner', { state: { flash: m } })
          }}
        />
      )}
    </div>
  )
}
