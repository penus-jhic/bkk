{{-- Tombol hapus versi CV arsip (MeController@cvDestroy). Versi yang sudah dipakai melamar tidak bisa dihapus karena
     lamarannya ikut terhapus (lamaran.cv_id cascadeOnDelete), jadi tombolnya dinonaktifkan dengan alasannya.
     Parameter: $version (CvResume, dengan lamarans_count), $compact (true = tombol kecil di daftar versi). --}}
@php
    $usedCount = (int) ($version->lamarans_count ?? 0);
    $sizeClass = ($compact ?? false) ? 'h-7 px-2.5 text-[11px]' : 'h-8 px-3.5 text-xs';
@endphp
@if($usedCount > 0)
    <span class="{{ $sizeClass }} rounded-full border border-brand-ink/10 text-brand-ink/35 font-semibold inline-flex items-center gap-1 cursor-not-allowed" title="Tidak bisa dihapus: dipakai di {{ $usedCount }} lamaran">
        <i data-lucide="trash-2" class="w-3 h-3"></i>
        <span>Hapus</span>
    </span>
@else
    <form action="{{ route('bkk.me.cv.destroy', $version->id) }}" method="POST" onsubmit="return confirm('Hapus Versi CV #{{ $version->id }}? Versi yang dihapus tidak bisa dikembalikan.')">
        @csrf
        @method('DELETE')
        <button type="submit" class="{{ $sizeClass }} rounded-full border border-brand-darkred/25 text-brand-darkred hover:bg-brand-darkred hover:text-white font-semibold inline-flex items-center gap-1 transition-colors cursor-pointer" title="Hapus versi ini">
            <i data-lucide="trash-2" class="w-3 h-3"></i>
            <span>Hapus</span>
        </button>
    </form>
@endif
