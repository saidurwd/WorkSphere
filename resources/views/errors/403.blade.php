<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Access restricted</title>
    <style>
        body { font-family: system-ui, -apple-system, 'Segoe UI', sans-serif; margin: 0; min-height: 100vh; display: grid; place-items: center; background: #f8fafc; color: #0f172a; }
        main { text-align: center; padding: 2rem; max-width: 28rem; }
        h1 { font-size: 3rem; margin: 0; color: #b91c1c; }
        h2 { margin: .25rem 0 1rem; font-size: 1.25rem; }
        p { color: #475569; }
        a { color: #4338ca; }
        @media (prefers-color-scheme: dark) { body { background: #0f172a; color: #e2e8f0; } p { color: #94a3b8; } a { color: #a5b4fc; } }
    </style>
</head>
<body>
    <main>
        <h1>403</h1>
        <h2>Access restricted</h2>
        <p>You do not have permission to view this resource. If you believe this is an error, please contact your administrator.</p>
        <p><a href="{{ url('/workspace') }}">Back to the workspace</a></p>
    </main>
</body>
</html>
