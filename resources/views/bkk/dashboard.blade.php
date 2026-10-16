<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard bkk</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logofavicon.png') }}">
</head>
<body>
    <header style="display: flex; justify-content: space-between; align-items: center;">
        <h2>Dashboard bkk</h2>
        <nav style="display: flex; gap: 12px; align-items: center;">
            <a href="/bkk">Beranda</a>
            <a href="/bkk/dashboard">Dashboard</a>
            <span>{{ $authUser['nama_lengkap'] ?? $authUser['username'] ?? 'User' }} ({{ $authUser['role'] ?? '-' }})</span>
        </nav>
    </header>
    <hr>
    <main>
        <h3>Data User ($request->auth_user)</h3>
        <pre>{{ json_encode($authUser, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
    </main>
</body>
</html>
