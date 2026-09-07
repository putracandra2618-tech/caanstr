<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="refresh" content="0; url={{ route('home') }}">
    <title>{{ config('app.name') }}</title>
</head>
<body style="margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;background:#faf9f7;font-family:system-ui,sans-serif">
    <p style="color:#1c1917;font-weight:600;">Mengalihkan ke {{ config('app.name') }}&hellip;</p>
    <script>window.location.href = '{{ route('home') }}';</script>
</body>
</html>