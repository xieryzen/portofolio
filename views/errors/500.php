<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Terjadi kesalahan</title>
    <style>
        :root { color-scheme: dark; }
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 24px; background: #060a14; color: #eef2ff; font: 15px/1.7 system-ui, -apple-system, "Segoe UI", sans-serif; }
        .card { max-width: 520px; padding: 32px; border-radius: 20px; border: 1px solid rgba(148,163,184,.2); background: linear-gradient(165deg, #111b30, #0d1526); box-shadow: 0 30px 70px -30px rgba(0,0,0,.9); }
        h1 { margin: 0 0 12px; font-size: 22px; letter-spacing: -.02em; }
        p { margin: 0 0 14px; color: #93a1bd; }
        a { display: inline-block; margin-top: 8px; padding: 10px 18px; border-radius: 999px; background: #6d8bff; color: #08101f; font-weight: 600; text-decoration: none; }
        code { font-family: ui-monospace, monospace; font-size: 13px; color: #c7d2e8; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Terjadi kesalahan pada server</h1>
        <p>Permintaan Anda tidak dapat diproses saat ini. Silakan coba beberapa saat lagi.</p>
        <p style="font-size:13px">Detail teknis sudah dicatat di <code>storage/logs/app.log</code>. Aktifkan <code>APP_DEBUG=true</code> pada <code>.env</code> untuk melihat pesan lengkap saat pengembangan.</p>
        <a href="/">Kembali ke beranda</a>
    </div>
</body>
</html>
