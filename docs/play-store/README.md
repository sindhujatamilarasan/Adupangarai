# Publishing Adupangarai on Google Play

Everything the Play Console asks for is in this folder. Paste the text below into the Console, and upload the images from here.

## 1. Before the first upload

| Step | Status |
|---|---|
| Host the API on a real **https** domain (the Play app can't use the laptop tunnel) | ⏳ to do |
| Build the bundle with that domain: `cd web && VITE_API_URL=https://YOUR-DOMAIN npm run android:aab` | after hosting |
| Output: `web/android/app/build/outputs/bundle/release/app-release.aab` | |
| Release signing (upload key in `web/android/keystore/`, passwords in `web/android/keystore.properties`) | ✅ done, **back both up** |
| In-app account deletion (Profile → Delete account) | ✅ done |
| Privacy policy page: `https://YOUR-DOMAIN/privacy` | ✅ ready (goes live with hosting) |
| Account deletion page: `https://YOUR-DOMAIN/delete-account` | ✅ ready (goes live with hosting) |
| Google developer account ($25, one time) at play.google.com/console | ⏳ you |

**Back up the upload key.** Copy `web/android/keystore/adupangarai-upload.jks` and `web/android/keystore.properties` to a safe place, such as Google Drive or a USB stick. Every future update must be signed with this key. If you lose it, Google support can reset it, but that takes days.

**For every new release:** raise `versionCode` in `web/android/app/build.gradle` (1 → 2 → 3…) and `versionName` (1.0.0 → 1.0.1…).

## 2. Google sign-in for the Play version

The Play Store re-signs the app with **Google's own key**, so Google sign-in needs two more Android OAuth clients. Create them in Google Cloud → Credentials, with package `com.adupangarai.app`:
1. **Upload key SHA-1:** `82:01:1C:4F:BF:D3:10:34:D0:0C:EC:9F:EA:62:D3:5E:0C:27:26:4B`. To print it again, run `npm run android:release-sha1`.
2. **App signing key SHA-1:** find it in Play Console → your app → Test and release → **App integrity** → App signing (it appears after the first upload).

Also set the OAuth consent screen to **In production** (Google Auth Platform → Audience → Publish app), using the privacy policy URL above. Otherwise only the test users you added can sign in with Google. Email and password login works either way.

## 3. Testing track

- **New personal developer accounts** must run a **closed test with at least 12 testers for 14 days** before they can publish to everyone.
- **Internal testing** (up to 100 testers, available within minutes) is the fastest way to share it with friends. Add their Gmail addresses, and they install from a Play link.

## 4. Store listing

**App name** (max 30 characters)
> Adupangarai: Kitchen & Meals

**Short description** (max 80 characters)
> Cook with what you have, plan meals, smart groceries & a friendly health coach.

**Full description**
```
Adupangarai (அடுப்பங்கரை, "by the hearth") is your home kitchen in your pocket, in English or Tamil.

🧺 KNOW WHAT'S IN YOUR KITCHEN
Track rice, dal, vegetables, milk, eggs and more, with expiry dates and low-stock alerts. Say "I bought 1 kg chicken, a dozen eggs and 2 litres of milk" and it's added.

🍳 WHAT CAN I COOK NOW?
See which recipes you can make right now with what you have, which are almost ready, and what to cook first before it expires. Recipes scale to any number of people.

📅 SMART MEAL PLAN
Plan the week in one tap: veg or non-veg, high protein, lighter, or fitted to your calorie target. No two similar dishes on one day and no repeats within three days. Print it or save it as an A4 PDF for the fridge.

🛒 SMART GROCERY LIST
The grocery list subtracts what you already have, so you only buy what's missing. Tick items as you shop and add them straight to your kitchen.

🍲 COOK AND TRACK
When you cook, the ingredients are taken out of your kitchen automatically.

💪 FRIENDLY HEALTH COACH
Set a goal (lose weight, stay fit or gain weight) and get a daily calorie and protein target. Tick the meals you ate and log extras (a vada, a coffee) by tapping, typing or just saying them. Take the 10,000 steps challenge, follow your weight progress, and earn badges and streaks. The tips are always positive.

🎙️ VOICE + AI
Speak in English or Tamil to add groceries, dictate a recipe or log what you ate. The AI only suggests; nothing is saved until you confirm.

🌐 ENGLISH AND தமிழ்
The whole app works in everyday spoken Tamil, including dish and ingredient names.

No ads. Your data is never sold. Delete your account anytime from Profile.

The health coach gives general guidance, not medical advice.
```

**Tamil listing (add under "Manage translations → Tamil")**

Short description:
> இருக்குறதை வெச்சு சமைங்க, சாப்பாடு பிளான், ஸ்மார்ட் மளிகை லிஸ்ட், ஹெல்த் கோச்.

Full description:
```
அடுப்பங்கரை — உங்க வீட்டு கிச்சன் இப்போ உங்க போன்ல.

🧺 கிச்சன்ல என்ன இருக்கு: அரிசி, பருப்பு, காய்கறி, பால், முட்டை — எக்ஸ்பைரி, ஸ்டாக் கம்மி அலர்ட்டோட. "1 கிலோ சிக்கன், ஒரு டஜன் முட்டை வாங்கினேன்"னு சொன்னா போதும்.

🍳 இப்போ என்ன சமைக்கலாம்: இருக்குற சாமான வெச்சு எந்த ரெசிபி பண்ணலாம், எது கிட்டத்தட்ட ரெடி, எக்ஸ்பைரி ஆகறதுக்குள்ள எதை முதல்ல சமைக்கணும்னு காட்டும்.

📅 ஸ்மார்ட் பிளான்: வெஜ் / நான்-வெஜ், புரோட்டீன், லைட், இல்லன்னா உங்க கலோரி டார்கெட்டுக்கு ஏத்த மாதிரி ஒரு டேப்ல வாரம் முழுக்க பிளான். A4 PDF-ஆ பிரிண்ட் பண்ணி ஃப்ரிட்ஜ்ல ஒட்டலாம்.

🛒 மளிகை லிஸ்ட்: வீட்டுல இருக்குறதை கழிச்சு, வாங்க வேண்டியது மட்டும்.

💪 ஹெல்த் கோச்: வெயிட் குறைக்க / ஏத்த / ஃபிட்டா இருக்க — தினசரி கலோரி டார்கெட், 10,000 ஸ்டெப்ஸ் சேலஞ்ச், பேட்ஜ், ஸ்ட்ரீக். எக்ஸ்ட்ரா சாப்பிட்டா சொல்லுங்க, பாசிட்டிவ்வா வழி சொல்லும்.

🎙️ வாய்ஸ் + AI: தமிழ்லயோ இங்கிலீஷ்லயோ பேசுங்க. நீங்க ஓகே சொல்லாம எதுவும் சேவ் ஆகாது.

விளம்பரம் இல்ல. உங்க டேட்டாவை விக்க மாட்டோம். எப்போ வேணும்னாலும் அக்கவுன்ட் டெலீட் பண்ணலாம்.
```

**Category:** Food & Drink · **Tags:** Meal planner, Recipes, Grocery list, Nutrition
**Contact email:** sindhujatamilarasan@gmail.com
**Privacy policy URL:** `https://YOUR-DOMAIN/privacy`

**Graphics in this folder**
- `icon-512.png`: app icon (512 × 512)
- `feature-graphic.png`: feature graphic (1024 × 500)
- `phone-*.png`: 8 phone screenshots (1080 × 1920), including 2 in Tamil

## 5. App content answers

| Question | Answer |
|---|---|
| Ads | No ads |
| App access | Login needed. Give reviewers `demo@adupangarai.test` / `password` (seed the demo on the live server) |
| Target audience | 18+ (the health coach is for adults) |
| Content rating (IARC) | Reference / utility app. No violence, sexual content, gambling or user-to-user chat |
| Health apps declaration | Nutrition & weight management / fitness (general wellness, not medical) |
| News app / Government app / Financial features | No |

### Data safety form

**Encryption and deletion**
- Data is encrypted in transit: **Yes**
- Users can request that data is deleted: **Yes**, in the app and at `/delete-account`

**Data sharing**
- Is data shared with third parties: **No**. Service providers acting for us (hosting, Google Gemini for AI text) don't count as "sharing" on this form.

**Data collected**

| Data type | Collected | Why | Optional? |
|---|---|---|---|
| Personal info → Name | Yes | Account management | Required |
| Personal info → Email address | Yes | Account management | Required |
| Personal info → User IDs (Google account ID) | Yes | Account management | Optional (Google sign-in) |
| Health and fitness → Health info (weight, height, sex, birth year, food log) | Yes | App functionality | Optional (coach) |
| Health and fitness → Fitness info (steps) | Yes | App functionality | Optional |
| Photos → Photos (recipe photos) | Yes | App functionality | Optional |
| Audio | **No**. Speech is turned into text on the phone; we only receive text | — | — |
| App activity → Other user-generated content (recipes, plans, lists) | Yes | App functionality | Required |
| Location, contacts, financial info, messages, device IDs, analytics | No | — | — |

None of this data is used for ads or marketing, and none of it is processed ephemerally.
