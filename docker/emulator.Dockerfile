# Headless Android emulator for checking builds:  docker build -f docker/emulator.Dockerfile -t adupangarai-emulator docker
FROM adupangarai-android
RUN apt-get update && apt-get install -y --no-install-recommends libpulse0 libnss3 libxcomposite1 libxcursor1 libxdamage1 libxi6 libxtst6 libgl1 libdbus-1-3 libxkbfile1 libasound2 > /dev/null \
 && rm -rf /var/lib/apt/lists/* \
 && sdkmanager "emulator" "system-images;android-35;google_apis;x86_64" > /dev/null \
 && echo no | avdmanager create avd -n phone -k "system-images;android-35;google_apis;x86_64" -d pixel_6 > /dev/null \
 && chmod -R a+rwX $ANDROID_HOME /root
ENV PATH=$PATH:$ANDROID_HOME/emulator
