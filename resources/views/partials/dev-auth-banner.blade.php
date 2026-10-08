{{-- Penanda login uji coba lokal (App\Support\DevAuth) supaya tidak tertukar dengan sesi sungguhan --}}
@php $devRole = \App\Support\DevAuth::currentRole(request()); @endphp
@if($devRole)
    <a href="{{ url((request()->is('ppdb*') ? '/ppdb' : '/bkk') . '/dev/masuk') }}"
       style="position:fixed;left:16px;bottom:16px;z-index:70;display:inline-flex;align-items:center;gap:8px;padding:8px 14px;border-radius:999px;background:#241012;color:#fff;font:600 12px/1 'Plus Jakarta Sans',system-ui,sans-serif;text-decoration:none;box-shadow:0 8px 30px -8px rgba(36,16,18,.45)"
       title="Login uji coba lokal aktif. Klik untuk ganti peran.">
        <span style="width:8px;height:8px;border-radius:999px;background:#F5C2C7"></span>
        Login dev: {{ $devRole }} · ganti peran
    </a>
@endif
