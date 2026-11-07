<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Dashboard</title>
    <style>body{font-family:system-ui,sans-serif;padding:2rem;background:#f0f7f2}h1{color:#1b7a3d}</style>
</head>
<body>
    <h1>{{ ucfirst($company) }} dashboard</h1>
    <p>Signed in as {{ $user->name }} ({{ $user->role }})</p>
    <form method="POST" action="/logout">@csrf <button>Sign out</button></form>
</body>
</html>