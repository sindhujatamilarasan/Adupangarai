@extends('legal.layout')
@section('title', 'Terms of use')
@section('content')
<h1>Terms of use</h1>
<p class="muted">Last updated: {{ $updated }} · Contact: <a href="mailto:{{ $contact }}">{{ $contact }}</a></p>

<p>By using Adupangarai you agree to these simple terms.</p>

<h2>The service</h2>
<p>Adupangarai helps you track your kitchen, find recipes, plan meals, make grocery lists and follow a personal health goal. It is free, provided "as is", and may change or be improved over time.</p>

<h2>Your account</h2>
<ul>
  <li>Keep your password safe. You are responsible for what happens in your account.</li>
  <li>Give correct information and use the app lawfully.</li>
  <li>You can delete your account at any time (Profile → Delete account).</li>
</ul>

<h2>Your content</h2>
<p>Recipes, photos and notes you add stay yours. Only upload photos you have the right to use. We store them only to run the app for you (see the <a href="/privacy">privacy policy</a>).</p>

<h2>Health and food information</h2>
<ul>
  <li>Calorie targets, nutrition values and tips are <strong>estimates and general guidance, not medical advice</strong>. Talk to a doctor before changing your diet if you are pregnant, diabetic, have a health condition or an eating disorder.</li>
  <li>The health coach is for adults (18+).</li>
  <li>Check ingredients yourself for allergies, and food yourself for freshness. Expiry reminders are only reminders.</li>
  <li>AI suggestions can be wrong. Always review them before saving.</li>
</ul>

<h2>Fair use</h2>
<p>Don't misuse the service, for example by trying to access other people's data, overloading it or reverse-engineering it. We may suspend accounts that do.</p>

<h2>Liability</h2>
<p>To the extent the law allows, we are not liable for indirect losses from using the app, including decisions about food or health made from its estimates.</p>

<h2>Changes and law</h2>
<p>We may update these terms and will change the date above when we do. These terms are governed by the laws of India.</p>

<div class="card" lang="ta">
<h2>தமிழில் சுருக்கமா</h2>
<ul>
  <li>ஆப் ஃப்ரீ; உங்க பாஸ்வேர்டை பத்திரமா வெச்சுக்கோங்க.</li>
  <li>நீங்க போடுற ரெசிபி, போட்டோ உங்களுது தான்.</li>
  <li>கலோரி, சத்து, டிப்ஸ் எல்லாம் கணிப்பு தான் — மருத்துவ ஆலோசனை இல்ல. உடல்நல பிரச்சனை இருந்தா டாக்டர்கிட்ட கேளுங்க.</li>
  <li>அலர்ஜி, சாப்பாடு ஃப்ரெஷ்ஷா இருக்கான்னு நீங்களே செக் பண்ணுங்க. AI தப்பா சொல்லலாம் — சேவ் பண்றதுக்கு முன்னாடி பாருங்க.</li>
</ul>
</div>
@endsection
