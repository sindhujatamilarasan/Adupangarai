// Lets the app import ../shared (Tamil text, kolam maths), which the web app uses too.
const { getDefaultConfig } = require('expo/metro-config')
const path = require('path')

const config = getDefaultConfig(__dirname)
config.watchFolders = [path.resolve(__dirname, '../shared')]
module.exports = config
