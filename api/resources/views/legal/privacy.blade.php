@extends('legal.layout')
@section('title', 'Privacy policy')
@section('content')
<h1>Privacy policy</h1>
<p class="muted">Last updated: {{ $updated }} · Contact: <a href="mailto:{{ $contact }}">{{ $contact }}</a></p>

<p>Adupangarai is a kitchen, meal-planning and health-coach app. This policy explains what we store, why, and how you can delete it.
We do not show ads, do not sell your data, and do not use tracking or analytics tools.</p>

<h2>What we store</h2>
<table>
  <tr><th>Data</th><th>Why</th></tr>
  <tr><td>Name, email, password (stored only as a secure hash), Google account ID if you sign in with Google</td><td>Your account and sign-in</td></tr>
  <tr><td>Kitchen stock, recipes, recipe photos you upload, meal plans, grocery lists and prices</td><td>The app's main features</td></tr>
  <tr><td>Health coach data you enter: sex, birth year, height, weight, goal, food you log, step counts</td><td>Your calorie target, progress and badges</td></tr>
  <tr><td>Language choice</td><td>Showing the app in English or Tamil</td></tr>
</table>

<h2>Voice and AI features</h2>
<p><strong>Voice input</strong> uses your phone's or browser's speech recognition (on Android this is provided by Google). We receive only the resulting text, never audio.</p>
<p><strong>AI features</strong> (reading a shopping list, drafting a recipe, estimating calories) send only the text you entered to Google's Gemini API to get a suggestion. Nothing is saved until you confirm it. Your name, email and health profile are not sent.
While we use Gemini's free service, Google may use that text to improve its products (see <a href="https://ai.google.dev/gemini-api/terms">Gemini API terms</a>), so please don't type personal details into AI boxes. You can always skip AI and type values yourself.</p>

<h2>Your consent and rights</h2>
<p>By creating an account you agree to us processing the data above for the purposes listed. Under India's Digital Personal Data Protection Act, 2023, you can: see and correct your data (in the app), withdraw consent and erase everything (Profile → Delete account), and raise a grievance.</p>
<p><strong>Grievance contact:</strong> {{ $developer }}, <a href="mailto:{{ $contact }}">{{ $contact }}</a>. We reply within 7 days. If you are not satisfied, you may complain to the Data Protection Board of India.</p>

<h2>Sharing</h2>
<p>Your data is not shared with anyone, except these service providers acting for us: our hosting provider (which stores the database), Google Sign-In (only if you choose it) and Google Gemini (AI text, as described above).</p>

<h2>Security</h2>
<p>All traffic uses HTTPS. Passwords are hashed. Each kitchen's data is only visible to its own account.</p>

<h2>Keeping and deleting your data</h2>
<p>We keep your data while your account exists. You can delete your account and all its data at any time. In the app, go to <strong>Profile → Delete account</strong>. See <a href="/delete-account">how to delete your account</a>. Deletion is immediate and permanent.</p>

<h2>Health information</h2>
<p>The health coach gives general guidance only. It is not medical advice. The coach is for adults (18+).</p>

<h2>Children</h2>
<p>The app is not directed at children under 13, and the health coach is for adults only.</p>

<h2>Changes</h2>
<p>If this policy changes, we will update this page and the date above. See also our <a href="/terms">terms of use</a>.</p>

<div class="card" lang="ta">
<h2>தமிழில் சுருக்கமா</h2>
<ul>
  <li>விளம்பரம் இல்ல, உங்க டேட்டாவை விக்க மாட்டோம், டிராக்கிங் இல்ல.</li>
  <li>அக்கவுன்ட் (பேர், இமெயில், பாஸ்வேர்ட் — ஹாஷ் பண்ணி), கிச்சன் சாமான், ரெசிபி, பிளான், மளிகை லிஸ்ட், நீங்க போடுற ஹெல்த் டேட்டா (வெயிட், உயரம், சாப்பாடு, ஸ்டெப்ஸ்) மட்டும் சேவ் பண்றோம்.</li>
  <li>வாய்ஸ்: உங்க போன் தான் பேச்சை டெக்ஸ்ட்டா மாத்தும்; எங்களுக்கு டெக்ஸ்ட் மட்டும் தான் வரும்.</li>
  <li>AI: நீங்க டைப்/சொன்ன டெக்ஸ்ட் மட்டும் Google Gemini-க்கு போகும். ஃப்ரீ சர்வீஸ்ங்கறதால Google அதை தங்க சர்வீஸை மேம்படுத்த யூஸ் பண்ணலாம் — அதனால AI பாக்ஸ்ல பர்சனல் விவரம் போடாதீங்க. நீங்க ஓகே சொல்லாம எதுவும் சேவ் ஆகாது.</li>
  <li>புகார் / சந்தேகம்: {{ $contact }} — 7 நாள்ல பதில் சொல்றோம்.</li>
  <li>எப்போ வேணும்னாலும் <strong>ப்ரொஃபைல் → அக்கவுன்ட் டெலீட்</strong> பண்ணலாம் — எல்லா டேட்டாவும் உடனே அழிஞ்சிடும்.</li>
  <li>ஹெல்த் கோச் பொதுவான வழிகாட்டல் தான், மருத்துவ ஆலோசனை இல்ல.</li>
</ul>
</div>
@endsection
