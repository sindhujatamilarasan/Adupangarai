import { useEffect, useRef } from 'react'
import { useApi } from '../api'
import { useI18n } from '../i18n'

type GoogleId = {
  accounts: {
    id: {
      initialize: (o: { client_id: string; callback: (r: { credential: string }) => void }) => void
      renderButton: (el: HTMLElement, o: Record<string, unknown>) => void
    }
  }
}

/** "Sign in with Google" (Google Identity Services). Renders nothing until a client id is configured. */
export default function GoogleButton({ lang, onCredential }: { lang: string; onCredential: (credential: string) => void }) {
  const config = useApi<{ google_client_id: string | null }>('/auth/config')
  const clientId = config.data?.google_client_id
  const box = useRef<HTMLDivElement>(null)
  const callback = useRef(onCredential)
  const { t } = useI18n()
  useEffect(() => {
    callback.current = onCredential
  }, [onCredential])

  useEffect(() => {
    if (!clientId || !box.current) return
    const render = () => {
      const google = (window as unknown as { google?: GoogleId }).google
      if (!google || !box.current) return
      google.accounts.id.initialize({ client_id: clientId, callback: (r) => callback.current(r.credential) })
      google.accounts.id.renderButton(box.current, { theme: 'outline', size: 'large', shape: 'pill', width: 320, locale: lang, text: 'continue_with' })
    }
    const existing = document.querySelector<HTMLScriptElement>('script[data-gsi]')
    if (existing) {
      render()
      return
    }
    const s = document.createElement('script')
    s.src = 'https://accounts.google.com/gsi/client'
    s.async = true
    s.dataset.gsi = '1'
    s.onload = render
    document.head.appendChild(s)
  }, [clientId, lang])

  if (!clientId) return null
  return (
    <div className="mt-5 space-y-4">
      <div className="flex items-center gap-3 text-xs text-muted" aria-hidden>
        <span className="h-px flex-1 bg-line" />
        {t('or')}
        <span className="h-px flex-1 bg-line" />
      </div>
      <div ref={box} className="flex min-h-11 justify-center" />
    </div>
  )
}
