import type { CapacitorConfig } from '@capacitor/cli'

// The Android app loads the built web app (dist/) and talks to the API at VITE_API_URL.
// Production must use an https:// API; plain http is allowed only for local testing.
const apiUrl = process.env.VITE_API_URL ?? ''
const devHttp = apiUrl.startsWith('http://')

const config: CapacitorConfig = {
  appId: 'com.adupangarai.app',
  appName: 'Adupangarai',
  webDir: 'dist',
  server: { androidScheme: 'https', cleartext: devHttp },
  android: { allowMixedContent: devHttp },
  plugins: {
    // Only Google is used; leaving out the others keeps their SDKs (and ad-ID permissions) out of the app.
    SocialLogin: { providers: { google: true, facebook: false, apple: false, twitter: false } },
  },
}

export default config
