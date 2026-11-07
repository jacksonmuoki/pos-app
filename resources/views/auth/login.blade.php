<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign in</title>
    <style>
        body{font-family:system-ui,sans-serif;background:#f0f7f2;display:grid;place-items:center;min-height:100vh;margin:0}
        form{background:#fff;padding:2rem;border-radius:12px;width:min(360px,90vw);box-shadow:0 4px 20px rgba(0,0,0,.08)}
        h1{margin:0 0 1rem;color:#1b7a3d;font-size:1.4rem}
        input{width:100%;padding:.7rem;margin:.4rem 0 1rem;border:1px solid #ccd;border-radius:8px;box-sizing:border-box}
        button{width:100%;padding:.8rem;background:#1b7a3d;color:#fff;border:0;border-radius:8px;font-size:1rem;cursor:pointer}
        .err{color:#b00020;font-size:.9rem;margin-bottom:.5rem}
    </style>
</head>
<body>
    <form method="POST" action="/login">
        @csrf
        <h1>{{ ucfirst(tenant('id')) }} POS</h1>
        @error('email')<div class="err">{{ $message }}</div>@enderror
        <label>Email</label>
        <input type="email" name="email" value="{{ old('email') }}" required autofocus>
        <label>Password</label>
        <input type="password" name="password" required>
        <button type="submit">Sign in</button>
    </form>
</body>
</html>