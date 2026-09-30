import { Capacitor, registerPlugin } from '@capacitor/core'

/** True inside the Android app (Capacitor), false in a browser. */
export const isNative = Capacitor.isNativePlatform()

/**
 * Where the API lives. In the browser the dev server proxies /api, so it stays relative.
 * The Android app has no proxy and must be built with VITE_API_URL (e.g. https://api.example.com).
 */
export const API_BASE = (import.meta.env.VITE_API_URL as string | undefined)?.replace(/\/$/, '') ?? ''

/** Server paths like /storage/recipes/x.jpg need the API host inside the app. */
export const assetUrl = (path: string | null) => (path && path.startsWith('/') ? `${API_BASE}${path}` : path)

/** Prints the current page: Android print service (incl. "Save as PDF") in the app, browser print otherwise. */
const NativePrint = registerPlugin<{ print(options: { name: string }): Promise<void> }>('Print')
export async function printPage(name: string) {
  if (isNative) await NativePrint.print({ name })
  else window.print()
}

/** Native Google account picker (Android Credential Manager). Returns a Google ID token for our server to verify. */
export async function nativeGoogleIdToken(webClientId: string): Promise<string> {
  const { SocialLogin } = await import('@capgo/capacitor-social-login')
  await SocialLogin.initialize({ google: { webClientId } })
  const res = await SocialLogin.login({ provider: 'google', options: {} })
  const idToken = 'idToken' in res.result ? res.result.idToken : null
  if (!idToken) throw new Error('No Google ID token')
  return idToken
}
