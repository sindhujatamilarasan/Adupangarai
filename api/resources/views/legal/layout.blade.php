<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>@yield('title') · Adupangarai</title>
<style>
  :root { --ink:#231a14; --muted:#76675c; --line:#ece4d8; --brand:#7a4320; --bg:#fbf8f3; }
  body { margin:0; background:var(--bg); color:var(--ink); font:16px/1.65 system-ui, -apple-system, "Segoe UI", Roboto, "Noto Sans Tamil", sans-serif; }
  main { max-width:720px; margin:0 auto; padding:32px 16px 64px; }
  h1 { font-size:1.8rem; margin:0 0 4px; } h2 { font-size:1.15rem; margin:28px 0 6px; } h3 { font-size:1rem; margin:18px 0 4px; }
  p, li { margin:6px 0; } .muted { color:var(--muted); font-size:.9rem; }
  a { color:var(--brand); } .card { background:#fff; border:1px solid var(--line); border-radius:16px; padding:4px 20px 16px; margin-top:24px; }
  header { display:flex; align-items:center; gap:12px; margin-bottom:20px; } header img { width:44px; height:44px; border-radius:12px; }
  table { border-collapse:collapse; width:100%; font-size:.95rem; } td, th { text-align:left; padding:8px 6px; border-bottom:1px solid var(--line); vertical-align:top; }
</style>
</head>
<body><main>
<header><img src="/logo.svg" alt=""><div><strong>Adupangarai</strong><br><span class="muted">அடுப்பங்கரை</span></div></header>
@yield('content')
</main></body>
</html>
