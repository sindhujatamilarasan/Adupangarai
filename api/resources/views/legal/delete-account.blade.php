@extends('legal.layout')
@section('title', 'Delete your account')
@section('content')
<h1>Delete your Adupangarai account</h1>
<p class="muted">Adupangarai · by {{ $developer }} · <a href="mailto:{{ $contact }}">{{ $contact }}</a></p>

<h2>In the app (fastest)</h2>
<ol>
  <li>Open Adupangarai and sign in.</li>
  <li>Tap your initial (top right) to open <strong>Profile</strong>.</li>
  <li>Scroll down to <strong>Delete account</strong>, type your email to confirm, and tap <strong>Delete forever</strong>.</li>
</ol>

<h2>Without the app</h2>
<p>Email <a href="mailto:{{ $contact }}?subject=Delete%20my%20Adupangarai%20account">{{ $contact }}</a> from the email address of your account, with the subject “Delete my account”. We delete it within 7 days and reply to confirm.</p>

<h2>What gets deleted</h2>
<p>Everything, immediately and permanently: your account, kitchen stock and history, your recipes and photos, meal plans, grocery lists, and all health coach data (goal, weight, food and step logs). Nothing is kept afterwards, and no backups are restored.</p>

<div class="card" lang="ta">
<h2>அக்கவுன்ட் டெலீட் பண்றது எப்படி</h2>
<p>ஆப்ல <strong>ப்ரொஃபைல் → அக்கவுன்ட் டெலீட்</strong> போய், உங்க இமெயிலை டைப் பண்ணி உறுதிப்படுத்துங்க. இல்லன்னா <a href="mailto:{{ $contact }}">{{ $contact }}</a>-க்கு மெயில் பண்ணுங்க. உங்க எல்லா டேட்டாவும் உடனே நிரந்தரமா அழிஞ்சிடும்.</p>
</div>
@endsection
