#!/usr/bin/env bash
# Build the Android app in Docker (no Android Studio needed).
#   EXPO_PUBLIC_API_URL=https://your-api ./scripts/build-android.sh apk    # test APK: "Adupangarai New", installs next to the current app
#   EXPO_PUBLIC_API_URL=https://your-api ./scripts/build-android.sh aab    # Play Store bundle (upload key from ../keys)
set -euo pipefail
cd "$(dirname "$0")/.."
kind="${1:-apk}"
: "${EXPO_PUBLIC_API_URL:?Set EXPO_PUBLIC_API_URL to the API address, e.g. https://adupangarai.in}"

task=assembleRelease; test_signing=1; variant=preview
[ "$kind" = "aab" ] && { task=bundleRelease; test_signing=0; variant=; }
# Test APKs are a separate "preview" app that installs next to the current one.
# Switching between preview and Play builds changes the package name, so regenerate android/ cleanly then.
clean=; [ "$(cat android/.variant 2>/dev/null)" != "$variant" ] && clean=--clean
APP_VARIANT=$variant CI=1 npx expo prebuild -p android --no-install $clean > /dev/null
echo "$variant" > android/.variant

repo="$(cd .. && pwd)"
docker run --rm -u "$(id -u):$(id -g)" \
  -e HOME=/repo/mobile/.gradle-cache -e GRADLE_USER_HOME=/repo/mobile/.gradle-cache/gradle \
  -e EXPO_PUBLIC_API_URL -e TEST_SIGNING=$test_signing -e NODE_ENV=production \
  -v "$repo":/repo -w /repo/mobile/android adupangarai-mobile-build ./gradlew "$task" --no-daemon

if [ "$kind" = "aab" ]; then ls -la android/app/build/outputs/bundle/release/*.aab; else ls -la android/app/build/outputs/apk/release/*.apk; fi
