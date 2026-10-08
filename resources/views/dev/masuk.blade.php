{{-- Pemilih peran login uji coba lokal (App\Support\DevAuth). Hanya bisa dibuka di development dari localhost. --}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Login Uji Coba {{ $appName }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@600;700&family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; background: #F5F4F2; color: #241012; font: 15px/1.5 'Plus Jakarta Sans', system-ui, sans-serif; display: grid; place-items: center; padding: 32px 16px; }
        main { width: 100%; max-width: 640px; }
        .eyebrow { font-size: 11px; font-weight: 700; letter-spacing: .14em; text-transform: uppercase; color: #7A1018; }
        h1 { font-family: 'Oswald', sans-serif; text-transform: uppercase; letter-spacing: .02em; font-size: 34px; line-height: 1.1; margin: 8px 0 0; display: inline-block; padding-bottom: 6px; background: linear-gradient(#7A1018, #7A1018) left bottom / 100% 3px no-repeat; }
        p.lead { color: rgba(36,16,18,.7); margin: 14px 0 24px; }
        .warn { background: #fff7e6; border: 1px solid #f5d38a; color: #7a4b00; border-radius: 12px; padding: 10px 14px; font-size: 13px; margin-bottom: 20px; }
        .grid { display: grid; gap: 10px; }
        a.card { display: flex; align-items: center; justify-content: space-between; gap: 16px; background: #fff; border: 1px solid rgba(36,16,18,.1); border-radius: 16px; padding: 16px 18px; text-decoration: none; color: inherit; transition: border-color .15s, box-shadow .15s, transform .15s; }
        a.card:hover { border-color: rgba(122,16,24,.35); box-shadow: 0 8px 30px -8px rgba(36,16,18,.18); transform: translateY(-1px); }
        a.card.active { border-color: #7A1018; box-shadow: 0 0 0 3px rgba(122,16,24,.12); }
        .card b { display: block; font-size: 15px; }
        .card small { color: rgba(36,16,18,.6); font-size: 12.5px; }
        .role { font-family: 'Oswald', sans-serif; font-size: 12px; letter-spacing: .06em; padding: 4px 10px; border-radius: 999px; background: #E8E8E8; white-space: nowrap; }
        .active .role { background: #7A1018; color: #fff; }
        h2 { font-family: 'Oswald', sans-serif; text-transform: uppercase; font-size: 16px; letter-spacing: .04em; margin: 28px 0 10px; }
        ul.links { list-style: none; padding: 0; margin: 0; display: grid; gap: 8px; }
        ul.links a { color: #7A1018; font-weight: 700; text-decoration: none; }
        ul.links a:hover { text-decoration: underline; }
        ul.links small { display: block; color: rgba(36,16,18,.6); }
    </style>
</head>
<body>
<main>
    <span class="eyebrow">Mode development · {{ $appName }}</span>
    <h1>Masuk sebagai…</h1>
    <p class="lead">Pilih peran untuk menguji halaman tanpa menjalankan auth service. Peran tersimpan di cookie selama 7 hari dan bisa diganti kapan saja lewat halaman ini.</p>

    <div class="warn">Halaman ini hanya aktif karena <code>APP_ENV=local</code> dan <code>AUTH_DEV_BYPASS=true</code>, dan hanya bisa dibuka dari localhost. Jangan aktifkan di server.</div>

    <div class="grid">
        @foreach($personas as $role => $persona)
            <a class="card {{ $currentRole === $role ? 'active' : '' }}" href="{{ route(strtolower($appName) . '.dev.login.as', strtolower($role)) }}">
                <span>
                    <b>{{ $persona['label'] }}</b>
                    <small>{{ $persona['desc'] }} → <code>{{ $persona['home'] }}</code></small>
                </span>
                <span class="role">{{ $currentRole === $role ? 'AKTIF · ' : '' }}{{ $role }}</span>
            </a>
        @endforeach
    </div>

    @if(!empty($extraLinks))
        <h2>Lainnya</h2>
        <ul class="links">
            @foreach($extraLinks as $link)
                <li><a href="{{ $link['href'] }}">{{ $link['label'] }}</a><small>{{ $link['note'] }}</small></li>
            @endforeach
        </ul>
    @endif
</main>
</body>
</html>
