// APP_VARIANT=preview builds a test copy ("Adupangarai New", com.adupangarai.app.preview) that installs
// next to the current app instead of replacing it. Everything else comes from app.json.
module.exports = ({ config }) => {
  if (process.env.APP_VARIANT !== 'preview') return config
  return {
    ...config,
    name: 'Adupangarai New',
    android: { ...config.android, package: 'com.adupangarai.app.preview' },
  }
}
