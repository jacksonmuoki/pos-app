<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Inventory')</title>
    <style>
        :root { --primary:#1b7a3d; --primary-dark:#145c2e; --bg:#f4f7f5; --border:#d6e0d9; --danger:#b00020; }
        * { box-sizing: border-box; }
        body { margin:0; font-family: system-ui, Arial, sans-serif; background:var(--bg); color:#1d2b22; }
        header { background:var(--primary); color:#fff; padding:12px 20px; display:flex; justify-content:space-between; align-items:center; }
        header a { color:#fff; text-decoration:none; font-weight:600; }
        main { max-width:1000px; margin:20px auto; padding:0 16px; }
        nav.inv { display:flex; flex-wrap:wrap; gap:8px; margin-bottom:16px; }
        nav.inv a { background:#fff; border:1px solid var(--border); padding:8px 14px; border-radius:6px; text-decoration:none; color:var(--primary); font-weight:600; }
        nav.inv a:hover { background:var(--primary); color:#fff; }
        .card { background:#fff; border:1px solid var(--border); border-radius:8px; padding:16px; margin-bottom:16px; }
        form.inline { display:flex; flex-wrap:wrap; gap:8px; }
        input, select { padding:8px; border:1px solid var(--border); border-radius:6px; font-size:14px; }
        button, .btn { background:var(--primary); color:#fff; border:0; padding:9px 16px; border-radius:6px; cursor:pointer; font-size:14px; text-decoration:none; display:inline-block; }
        button:hover, .btn:hover { background:var(--primary-dark); }
        button.secondary { background:#fff; color:var(--primary); border:1px solid var(--primary); }
        table { width:100%; border-collapse:collapse; background:#fff; }
        th, td { text-align:left; padding:9px 10px; border-bottom:1px solid var(--border); font-size:14px; }
        th { background:#eaf3ed; }
        .ok { color:var(--primary); font-weight:600; }
        .err { color:var(--danger); font-weight:600; }
        .low { color:var(--danger); font-weight:700; }
    </style>
</head>
<body>
<header>
    <a href="{{ url('/dashboard') }}">&larr; Dashboard</a>
    <span>{{ auth()->user()->name ?? '' }}</span>
</header>
<main>
    @include('inventory._nav')
    @if(session('status')) <p class="ok">{{ session('status') }}</p> @endif
    @if($errors->any()) <p class="err">{{ $errors->first() }}</p> @endif
    @yield('content')
</main>
</body>
</html>