# Builds the React Native (Expo) Android app without Android Studio:
#   docker build -f docker/android-build.Dockerfile -t adupangarai-android docker
#   docker build -f docker/mobile-build.Dockerfile -t adupangarai-mobile-build docker
#   cd mobile && npm run android:apk   (see mobile/README.md)
FROM adupangarai-android
RUN curl -fsSL https://deb.nodesource.com/setup_22.x | bash - > /dev/null \
 && apt-get install -y --no-install-recommends nodejs > /dev/null && rm -rf /var/lib/apt/lists/* \
 && sdkmanager "ndk;27.1.12297006" "cmake;3.22.1" > /dev/null \
 && chmod -R a+rwX $ANDROID_HOME
