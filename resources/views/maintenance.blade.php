<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Maintenance</title>
    <style>
        body { font-family: system-ui, sans-serif; background:#f4f8f5; margin:0; min-height:100vh; display:grid; place-items:center; }
        .box { background:#fff; max-width:480px; padding:40px; border-radius:12px; text-align:center; box-shadow:0 4px 20px rgba(0,0,0,.08); }
        h1 { color:#1b7a3d; margin-top:0; }
        p { color:#475467; line-height:1.6; }
    </style>
</head>
<body>
    <div class="box">
        <h1>{{ $name }}</h1>
        <p>{{ $message }}</p>
        @if ($contact)<p><small>Need help? {{ $contact }}</small></p>@endif
    </div>
</body>
</html>