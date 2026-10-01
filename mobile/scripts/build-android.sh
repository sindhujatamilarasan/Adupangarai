#!/usr/bin/env bash
# Build the Android app in Docker (no Android Studio needed).
#   EXPO_PUBLIC_API_URL=https://your-api ./scripts/build-android.sh apk    # test APK (debug key, Google sign-in works)
#   EXPO_PUBLIC_API_URL=https://your-api ./scripts/build-android.sh aab    # Play Store bundle (upload key from ../keys)
set -euo pipefail
cd "$(dirname "$0")/.."
kind="${1:-apk}"
: "${EXPO_PUBLIC_API_URL:?Set EXPO_PUBLIC_API_URL to the API address, e.g. https://adupangarai.in}"

CI=1 npx expo prebuild -p android --no-install > /dev/null
task=assembleRelease; test_signing=1
[ "$kind" = "aab" ] && { task=bundleRelease; test_signing=0; }

repo="$(cd .. && pwd)"
docker run --rm -u "$(id -u):$(id -g)" \
  -e HOME=/repo/mobile/.gradle-cache -e GRADLE_USER_HOME=/repo/mobile/.gradle-cache/gradle \
  -e EXPO_PUBLIC_API_URL -e TEST_SIGNING=$test_signing -e NODE_ENV=production \
  -v "$repo":/repo -w /repo/mobile/android adupangarai-mobile-build ./gradlew "$task" --no-daemon

if [ "$kind" = "aab" ]; then ls -la android/app/build/outputs/bundle/release/*.aab; else ls -la android/app/build/outputs/apk/release/*.apk; fi
