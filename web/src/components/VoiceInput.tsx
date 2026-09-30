import { useEffect, useRef, useState } from 'react'
import { useI18n } from '../i18n'
import { isNative } from '../lib/native'

// Minimal typing for the browser Web Speech API (Chrome, Edge, Android, Safari).
type Recognition = {
  lang: string
  continuous: boolean
  interimResults: boolean
  start: () => void
  stop: () => void
  onresult: ((e: { resultIndex: number; results: ArrayLike<ArrayLike<{ transcript: string }> & { isFinal: boolean }> }) => void) | null
  onend: (() => void) | null
  onerror: ((e: { error: string }) => void) | null
}
const SpeechRecognitionImpl: (new () => Recognition) | undefined =
  (window as unknown as { SpeechRecognition?: new () => Recognition }).SpeechRecognition ??
  (window as unknown as { webkitSpeechRecognition?: new () => Recognition }).webkitSpeechRecognition

const LANGS = [
  { code: 'en-IN', label: 'English' },
  { code: 'ta-IN', label: 'தமிழ்' },
]

/** Text box with a mic button: speak (free, in-browser speech-to-text) or type. */
export default function VoiceInput({ value, onChange, placeholder, rows = 4 }: { value: string; onChange: (v: string) => void; placeholder: string; rows?: number }) {
  const { t, lang: uiLang } = useI18n()
  const [lang, setLang] = useState(uiLang === 'ta' ? 'ta-IN' : 'en-IN')
  const [listening, setListening] = useState(false)
  const [error, setError] = useState('')
  const rec = useRef<Recognition | null>(null)
  const base = useRef('')

  useEffect(() => () => rec.current?.stop(), [])

  /** Android app: the phone's own speech recognition (the browser API isn't available in app WebViews). */
  async function toggleNative() {
    const { SpeechRecognition } = await import('@capacitor-community/speech-recognition')
    if (listening) {
      await SpeechRecognition.stop()
      setListening(false)
      return
    }
    const perm = await SpeechRecognition.requestPermissions()
    if (perm.speechRecognition !== 'granted') {
      setError(t('Microphone permission was denied.'))
      return
    }
    setError('')
    base.current = value ? `${value.trim()} ` : ''
    await SpeechRecognition.removeAllListeners()
    await SpeechRecognition.addListener('partialResults', (d) => d.matches[0] && onChange(base.current + d.matches[0]))
    await SpeechRecognition.addListener('listeningState', (s) => s.status === 'stopped' && setListening(false))
    setListening(true)
    SpeechRecognition.start({ language: lang, partialResults: true, popup: false }).catch((e: Error) => {
      setError(t('Voice input stopped ({error}).', { error: e.message }))
      setListening(false)
    })
  }

  function toggle() {
    if (isNative) {
      void toggleNative()
      return
    }
    if (listening) {
      rec.current?.stop()
      return
    }
    if (!SpeechRecognitionImpl) return
    const r = new SpeechRecognitionImpl()
    r.lang = lang
    r.continuous = true
    r.interimResults = true
    base.current = value ? `${value.trim()} ` : ''
    r.onresult = (e) => {
      let text = ''
      for (let i = 0; i < e.results.length; i++) text += e.results[i][0].transcript
      onChange(base.current + text)
    }
    r.onerror = (e) => setError(e.error === 'not-allowed' ? t('Microphone permission was denied.') : t('Voice input stopped ({error}).', { error: e.error }))
    r.onend = () => setListening(false)
    setError('')
    rec.current = r
    r.start()
    setListening(true)
  }

  return (
    <div className="space-y-2">
      <div className="relative">
        <textarea
          rows={rows}
          value={value}
          onChange={(e) => onChange(e.target.value)}
          placeholder={placeholder}
          className="w-full rounded-2xl border border-line bg-white px-4 py-3 pr-16 outline-none focus:border-brand"
        />
        {(isNative || SpeechRecognitionImpl) && (
          <button
            type="button"
            onClick={toggle}
            aria-label={listening ? t('Stop listening') : t('Speak')}
            className={`absolute right-2 bottom-3 grid size-12 place-items-center rounded-full text-xl text-white shadow-md ${listening ? 'animate-pulse bg-red-600' : 'bg-brand'}`}
          >
            {listening ? '■' : '🎙️'}
          </button>
        )}
      </div>
      <div className="flex items-center justify-between text-xs text-muted">
        {isNative || SpeechRecognitionImpl ? (
          <>
            <span>{listening ? t('Listening… tap ■ when done') : t('Tap 🎙️ and speak, or type')}</span>
            <span className="flex gap-1">
              {LANGS.map((l) => (
                <button
                  key={l.code}
                  type="button"
                  disabled={listening}
                  onClick={() => setLang(l.code)}
                  className={`rounded-full px-2 py-0.5 font-bold ${lang === l.code ? 'bg-ink text-white' : 'bg-white'}`}
                >
                  {l.label}
                </button>
              ))}
            </span>
          </>
        ) : (
          <span>{t('Voice input isn’t supported in this browser — type instead (Chrome or Edge support voice).')}</span>
        )}
      </div>
      {error && <p className="text-sm text-red-700">{error}</p>}
    </div>
  )
}
