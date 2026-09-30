import { useEffect, useRef } from 'react'
import { useApi } from '../api'
import { useI18n } from '../i18n'
import { isNative, nativeGoogleIdToken } from '../lib/native'

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
    if (!clientId || !box.current || isNative) return
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
      {isNative ? (
        <button
          type="button"
          onClick={() => nativeGoogleIdToken(clientId).then(onCredential, () => {})}
          className="mx-auto flex w-full max-w-xs items-center justify-center gap-3 rounded-full border border-line bg-white py-3 font-semibold text-ink"
        >
          <svg viewBox="0 0 48 48" className="size-5" aria-hidden>
            <path fill="#EA4335" d="M24 9.5c3.5 0 6.6 1.2 9 3.5l6.7-6.7C35.6 2.5 30.2 0 24 0 14.6 0 6.6 5.4 2.7 13.3l7.8 6C12.4 13.4 17.7 9.5 24 9.5z" />
            <path fill="#4285F4" d="M46.1 24.5c0-1.6-.1-3.1-.4-4.5H24v9h12.4c-.5 2.9-2.2 5.3-4.6 7l7.5 5.8c4.4-4 6.8-10 6.8-17.3z" />
            <path fill="#FBBC05" d="M10.5 28.7A14.5 14.5 0 0 1 9.5 24c0-1.6.3-3.2.8-4.7l-7.8-6A24 24 0 0 0 0 24c0 3.9.9 7.5 2.7 10.7l7.8-6z" />
            <path fill="#34A853" d="M24 48c6.5 0 11.9-2.1 15.9-5.8l-7.5-5.8c-2.1 1.4-4.9 2.3-8.4 2.3-6.3 0-11.6-3.9-13.5-9.7l-7.8 6C6.6 42.6 14.6 48 24 48z" />
          </svg>
          {t('Continue with Google')}
        </button>
      ) : (
        <div ref={box} className="flex min-h-11 justify-center" />
      )}
    </div>
  )
}
