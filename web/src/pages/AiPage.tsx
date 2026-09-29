import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { AiPantrySheet, AiPlanSheet, AiRecipeSheet } from '../components/AiSheets'
import { Alert } from '../components/ui'

const actions = [
  { key: 'pantry', emoji: '🛍️', title: 'Say what you bought', text: '“1 kg chicken, a dozen eggs and 2 litres milk” → added to your kitchen after you check it.' },
  { key: 'recipe', emoji: '📝', title: 'Say a recipe', text: 'Dictate a recipe in English or Tamil → a ready-to-save recipe with estimated nutrition.' },
  { key: 'plan', emoji: '📅', title: 'Plan my meals', text: 'Balanced, high-protein or lighter days, picked from your recipes and what’s in your kitchen.' },
] as const

export default function AiPage() {
  const navigate = useNavigate()
  const [open, setOpen] = useState<(typeof actions)[number]['key'] | null>(null)
  const [done, setDone] = useState('')

  return (
    <div className="space-y-4">
      <header className="relative overflow-hidden rounded-3xl bg-gradient-to-br from-brand to-brand-dark p-5 text-white shadow-sm">
        <span className="absolute -top-8 -right-8 size-32 rounded-full bg-accent/25" aria-hidden />
        <p className="text-3xl">✨</p>
        <h1 className="text-2xl font-extrabold">AI mode</h1>
        <p className="text-sm opacity-90">Talk to your kitchen. The AI only prepares drafts — you always check before anything is saved.</p>
      </header>

      {done && <Alert kind="success">{done}</Alert>}

      {actions.map((a) => (
        <button key={a.key} onClick={() => setOpen(a.key)} className="flex w-full items-start gap-4 rounded-3xl bg-white p-4 text-left shadow-sm active:scale-[.99]">
          <span className="grid size-14 shrink-0 place-items-center rounded-2xl bg-cream text-3xl">{a.emoji}</span>
          <span>
            <span className="block font-extrabold">{a.title}</span>
            <span className="block text-sm text-muted">{a.text}</span>
          </span>
        </button>
      ))}

      <p className="px-2 text-center text-xs text-muted">Runs on a free AI model. Voice uses your browser’s speech recognition (Chrome/Edge/Android).</p>

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
