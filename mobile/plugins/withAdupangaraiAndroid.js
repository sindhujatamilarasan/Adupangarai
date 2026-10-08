// Android build settings for Adupangarai (applied on every `expo prebuild`; never edit android/ by hand).
// - Release builds are signed with the Play upload key from ../keys/keystore.properties (gitignored).
// - Debug builds use ../keys/debug.keystore, the key Google sign-in is registered with.
// - Phones only (arm), R8 shrinking on, compressed native libraries.
const { withAppBuildGradle, withDangerousMod, withGradleProperties } = require('expo/config-plugins')
const fs = require('fs')
const path = require('path')

const KEYS = path.resolve(__dirname, '../../keys')

const releaseSigning = `
def adupangaraiKeys = new File(rootProject.projectDir, '../../keys/keystore.properties')
def adupangaraiProps = new Properties()
if (adupangaraiKeys.exists()) adupangaraiKeys.withInputStream { adupangaraiProps.load(it) }
`

module.exports = function withAdupangaraiAndroid(config) {
  config = withAppBuildGradle(config, (c) => {
    let g = c.modResults.contents
    if (!g.includes('adupangaraiKeys')) {
      g = g.replace(/\nandroid \{/, `${releaseSigning}\nandroid {`)
      g = g.replace(
        /signingConfigs \{\n(\s*)debug \{/,
        `signingConfigs {\n$1release {\n$1    if (adupangaraiKeys.exists()) {\n$1        storeFile new File(adupangaraiKeys.parentFile, adupangaraiProps['storeFile'])\n$1        storePassword adupangaraiProps['storePassword']\n$1        keyAlias adupangaraiProps['keyAlias']\n$1        keyPassword adupangaraiProps['keyPassword']\n$1    }\n$1}\n$1debug {`,
      )
      // In the release block, sign with the upload key when it is available.
      // TEST_SIGNING=1 signs a release build with the debug key (test APKs, where Google sign-in uses that key).
      g = g.replace(/(release \{\n[^}]*?)signingConfig signingConfigs\.debug/, "$1signingConfig((adupangaraiKeys.exists() && System.getenv('TEST_SIGNING') != '1') ? signingConfigs.release : signingConfigs.debug)")
    }
    c.modResults.contents = g
    return c
  })

  config = withDangerousMod(config, [
    'android',
    (c) => {
      const debugKey = path.join(KEYS, 'debug.keystore')
      if (fs.existsSync(debugKey)) fs.copyFileSync(debugKey, path.join(c.modRequest.platformProjectRoot, 'app/debug.keystore'))
      return c
    },
  ])

  return withGradleProperties(config, (c) => {
    const set = (key, value) => {
      c.modResults = c.modResults.filter((p) => p.key !== key)
      c.modResults.push({ type: 'property', key, value })
    }
    set('reactNativeArchitectures', 'armeabi-v7a,arm64-v8a')
    // Reuse earlier build results (the C++ part takes most of the build time).
    set('org.gradle.caching', 'true')
    set('android.enableMinifyInReleaseBuilds', 'true')
    set('android.enableShrinkResourcesInReleaseBuilds', 'true')
    // Compressed native libraries, unpacked on install: smaller download, and works on emulators that translate ARM.
    set('expo.useLegacyPackaging', 'true')
    return c
  })
}
