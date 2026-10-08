@extends('admin.master')

@section('title', 'Profil Saya - Admin BKK')

@section('content')
@php
    $name = $authUser['nama_lengkap'] ?? $authUser['username'] ?? 'Administrator';
    $fields = [
        ['label' => 'Nama Lengkap', 'value' => $authUser['nama_lengkap'] ?? '-'],
        ['label' => 'Username', 'value' => $authUser['username'] ?? '-'],
        ['label' => 'Email', 'value' => $authUser['email'] ?? '-'],
        ['label' => 'No. HP', 'value' => $authUser['no_hp'] ?? '-'],
        ['label' => 'NIP / Nomor Induk', 'value' => $authUser['nomor_induk'] ?? '-'],
        ['label' => 'Role', 'value' => strtoupper($authUser['role'] ?? '-')],
    ];
@endphp
<div class="flex flex-col gap-6">
    <!-- Page Header -->
    <div>
        <div class="flex items-center gap-2 text-xs text-muted mb-1">
            <a href="{{ route('bkk.admin.index') }}" class="hover:text-navy">Dashboard</a>
            <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
            <span class="text-navy font-semibold">Profil Saya</span>
        </div>
        <h1 class="text-2xl font-bold font-headline text-navy">Profil Saya</h1>
        <p class="text-xs text-muted mt-1">
            Data akun berasal dari layanan otentikasi sekolah. Perubahan data dilakukan melalui administrator sistem.
        </p>
    </div>

    <div class="bg-white rounded-2xl border border-line shadow-xs p-6 max-w-2xl">
        <div class="flex items-center gap-4 pb-5 border-b border-line">
            <div class="w-16 h-16 rounded-full grid place-items-center text-white text-xl font-bold shadow-md bg-maroon shrink-0">
                {{ collect(preg_split('/\s+/', trim($name)))->take(2)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('') }}
            </div>
            <div class="min-w-0">
                <div class="font-headline font-bold text-navy text-lg truncate">{{ $name }}</div>
                <span class="mt-1 inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-navy/10 text-navy">
                    <i data-lucide="shield-check" class="w-3.5 h-3.5"></i>
                    {{ strtoupper($authUser['role'] ?? 'ADMIN') }}
                </span>
            </div>
        </div>

        <dl class="mt-5 grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4">
            @foreach($fields as $field)
                <div>
                    <dt class="text-[11px] font-semibold text-muted uppercase tracking-wider">{{ $field['label'] }}</dt>
                    <dd class="text-sm text-navy font-medium mt-0.5 break-words">{{ $field['value'] }}</dd>
                </div>
            @endforeach
        </dl>
    </div>
</div>
@endsection
