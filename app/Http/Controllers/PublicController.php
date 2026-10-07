<?php

namespace App\Http\Controllers;

use App\Models\Berita;
use App\Models\KategoriBerita;
use App\Models\Lowongan;
use App\Models\Mitra;
use App\Models\PermohonanKerjasama;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class PublicController extends Controller
{
    /**
     * Ambil parameter query sebagai string; nilai array (?q[]=x) diabaikan agar tidak memicu error.
     */
    protected function queryString(Request $request, string $key): ?string
    {
        $value = $request->input($key);

        return is_string($value) ? $value : null;
    }

    /**
     * Endpoint publik utama BKK (Landpage).
     */
    public function index(Request $request): View|JsonResponse
    {
        $latestBerita = Berita::published()
            ->with('kategori')
            ->latest('published_at')
            ->take(3)
            ->get();

        $featuredLowongan = Lowongan::with('mitra')
            ->terbuka()
            ->latest('created_at')
            ->take(4)
            ->get();

        $mitraCount = Mitra::verified()->count();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => [
                    'prefix' => '/bkk',
                    'berita' => $latestBerita,
                    'lowongan' => $featuredLowongan,
                    'total_mitra' => $mitraCount,
                ],
            ]);
        }

        return view('index.pages.index', compact('latestBerita', 'featuredLowongan', 'mitraCount'));
    }

    /**
     * Endpoint informasi BKK.
     */
    public function info(Request $request): View|JsonResponse
    {
        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'bkk Info',
            ]);
        }

        return view('index.pages.index');
    }

    /**
     * Daftar Berita & Agenda BKK.
     */
    public function berita(Request $request): View
    {
        $kategoriSlug = $this->queryString($request, 'kategori');
        $searchQuery = $this->queryString($request, 'q');

        $categories = KategoriBerita::withCount(['beritas' => fn ($q) => $q->where('status', 'PUBLISHED')])->get();
        $totalPublished = Berita::published()->count();

        $heroBerita = null;
        if (empty($kategoriSlug) && empty($searchQuery) && ($request->input('page', 1) == 1)) {
            $heroBerita = Berita::published()
                ->where('is_featured', true)
                ->latest('published_at')
                ->first() ?? Berita::published()->latest('published_at')->first();
        }

        $query = Berita::published()->with('kategori');

        if ($heroBerita) {
            $query->where('id', '!=', $heroBerita->id);
        }

        if (!empty($searchQuery)) {
            $like = config('database.default') === 'pgsql' ? 'ilike' : 'like';
            $query->where(function ($q) use ($searchQuery, $like) {
                $q->where('judul', $like, "%{$searchQuery}%")
                  ->orWhere('ringkasan', $like, "%{$searchQuery}%")
                  ->orWhere('penulis_nama', $like, "%{$searchQuery}%");
            });
        }

        if (!empty($kategoriSlug)) {
            $query->whereHas('kategori', fn ($k) => $k->where('slug', $kategoriSlug));
        }

        $beritas = $query->latest('published_at')->paginate(6)->withQueryString();

        return view('index.pages.berita', compact(
            'categories',
            'totalPublished',
            'heroBerita',
            'beritas',
            'kategoriSlug',
            'searchQuery'
        ));
    }

    /**
     * Detail Berita BKK.
     */
    public function beritaDetail(string $id_berita): View
    {
        $berita = Berita::published()
            ->with('kategori')
            ->where(function ($query) use ($id_berita) {
                $query->where('slug', $id_berita);
                if (is_numeric($id_berita)) {
                    $query->orWhere('id', (int) $id_berita);
                }
            })
            ->firstOrFail();

        $berita->increment('views_count');

        $relatedBerita = Berita::published()
            ->where('id', '!=', $berita->id)
            ->where('kategori_id', $berita->kategori_id)
            ->latest('published_at')
            ->take(3)
            ->get();

        if ($relatedBerita->count() < 3) {
            $fallback = Berita::published()
                ->where('id', '!=', $berita->id)
                ->whereNotIn('id', $relatedBerita->pluck('id'))
                ->latest('published_at')
                ->take(3 - $relatedBerita->count())
                ->get();
            $relatedBerita = $relatedBerita->merge($fallback);
        }

        return view('index.pages.berita-detail', compact('berita', 'relatedBerita'));
    }

    /**
     * Daftar Lowongan PKL & Kerja BKK.
     */
    public function lowongan(Request $request): View|JsonResponse
    {
        $query = Lowongan::with('mitra')->terbuka();

        $searchQuery = $this->queryString($request, 'q');
        if (!empty($searchQuery)) {
            $like = config('database.default') === 'pgsql' ? 'ilike' : 'like';
            $query->where(function ($q) use ($searchQuery, $like) {
                $q->where('judul', $like, "%{$searchQuery}%")
                  ->orWhere('target_jurusan', $like, "%{$searchQuery}%")
                  ->orWhere('lokasi', $like, "%{$searchQuery}%")
                  ->orWhereHas('mitra', fn ($m) => $m->where('nama_perusahaan', $like, "%{$searchQuery}%"));
            });
        }

        $tipe = $this->queryString($request, 'tipe');
        // Nilai enum kolom tipe: 'PKL' & 'Kerja'
        $tipeValues = ['pkl' => 'PKL', 'kerja' => 'Kerja'];
        if (!empty($tipe) && isset($tipeValues[strtolower($tipe)])) {
            $query->where('tipe', $tipeValues[strtolower($tipe)]);
        }

        $jurusan = $this->queryString($request, 'jurusan');
        if (!empty($jurusan) && $jurusan !== 'all') {
            $like = config('database.default') === 'pgsql' ? 'ilike' : 'like';
            $query->where('target_jurusan', $like, "%{$jurusan}%");
        }

        // Pilihan lokasi di halaman lowongan => kata kunci yang dicari di kolom lokasi
        $lokasiKeywords = [
            'bogor' => ['Bogor', 'Cibinong'],
            'jakarta' => ['Jakarta'],
            'depok' => ['Depok', 'Bekasi'],
            'karawang' => ['Karawang'],
            'hybrid' => ['Hybrid', 'Remote'],
        ];
        $lokasi = $this->queryString($request, 'lokasi');
        if (isset($lokasiKeywords[$lokasi])) {
            $like = config('database.default') === 'pgsql' ? 'ilike' : 'like';
            $query->where(function ($q) use ($lokasiKeywords, $lokasi, $like) {
                foreach ($lokasiKeywords[$lokasi] as $keyword) {
                    $q->orWhere('lokasi', $like, "%{$keyword}%");
                }
            });
        }

        $urut = $this->queryString($request, 'urut');
        match ($urut) {
            'deadline' => $query->orderBy('deadline'),
            'quota' => $query->orderByDesc('kuota'),
            default => $query->latest('created_at'),
        };

        $lowongans = $query->paginate(9)->withQueryString();
        $totalLowongan = Lowongan::terbuka()->count();
        $tipeCounts = Lowongan::terbuka()
            ->selectRaw('tipe, count(*) as total')
            ->groupBy('tipe')
            ->pluck('total', 'tipe');

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => [
                    'total' => $totalLowongan,
                    'lowongan' => $lowongans,
                ],
            ]);
        }

        return view('index.pages.lowongan', compact('lowongans', 'totalLowongan', 'tipeCounts', 'searchQuery', 'tipe', 'jurusan', 'lokasi', 'urut'));
    }

    /**
     * Detail Lowongan PKL & Kerja BKK.
     */
    public function lowonganDetail(Request $request, string $id_lowongan): View|JsonResponse|Response
    {
        $lowongan = Lowongan::with('mitra')
            ->withCount('lamarans')
            ->terbuka()
            ->where(function ($q) use ($id_lowongan) {
                $q->where('slug', $id_lowongan);
                if (is_numeric($id_lowongan)) {
                    $q->orWhere('id', (int) $id_lowongan);
                }
            })
            ->first();

        if ($lowongan) {
            $lowongan->increment('views_count');
        }

        $relatedLowongan = Lowongan::with('mitra')
            ->terbuka()
            ->when($lowongan, fn ($q) => $q->where('id', '!=', $lowongan->id))
            ->latest('created_at')
            ->take(3)
            ->get();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => (bool) $lowongan,
                'data' => [
                    'lowongan' => $lowongan,
                    'related' => $relatedLowongan,
                ],
            ], $lowongan ? Response::HTTP_OK : Response::HTTP_NOT_FOUND);
        }

        $totalLowongan = Lowongan::terbuka()->count();

        $data = compact('id_lowongan', 'lowongan', 'relatedLowongan', 'totalLowongan');

        // Lowongan tidak ditemukan / tidak aktif: tetap tampilkan blok "tidak ditemukan" di view, dengan status 404
        if (!$lowongan) {
            return response()->view('index.pages.lowongan-detail', $data, Response::HTTP_NOT_FOUND);
        }

        return view('index.pages.lowongan-detail', $data);
    }

    /**
     * Halaman Profil & Struktur Tentang BKK.
     */
    public function tentang(): View
    {
        return view('index.pages.tentang');
    }

    /**
     * Halaman Informasi Kemitraan & Kerja Sama IDUKA.
     */
    public function kerjasama(): View
    {
        $mitraList = Mitra::verified()->latest()->take(12)->get();
        $totalMitraVerified = Mitra::verified()->count();
        return view('index.pages.kerjasama', compact('mitraList', 'totalMitraVerified'));
    }

    /**
     * Handler submit formulir permohonan kerja sama dari laman publik.
     */
    public function storeKerjasama(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'nama_perusahaan' => ['required', 'string', 'max:200'],
            'bidang_usaha' => ['required', 'string', 'max:150'],
            'alamat_perusahaan' => ['required', 'string'],
            'email_resmi' => ['required', 'email', 'max:150'],
            'no_telepon' => ['required', 'string', 'max:50'],
            'nama_pic' => ['required', 'string', 'max:150'],
            'jabatan_pic' => ['required', 'string', 'max:150'],
            'jenis_kerjasama' => ['nullable', 'array'],
            'jenis_kerjasama.*' => ['string', 'max:100'],
            'estimasi_kebutuhan' => ['nullable', 'string', 'max:50'],
            'pesan_tambahan' => ['nullable', 'string'],
        ]);

        $jenisKerjasama = $validated['jenis_kerjasama'] ?? ['PKL / Magang Siswa'];

        // Tabel permohonan tidak punya kolom estimasi kebutuhan, jadi dicatat di awal pesan tambahan
        if (!empty($validated['estimasi_kebutuhan'])) {
            $validated['pesan_tambahan'] = trim('Estimasi kebutuhan talenta: ' . $validated['estimasi_kebutuhan'] . "\n\n" . ($validated['pesan_tambahan'] ?? ''));
        }

        $permohonan = PermohonanKerjasama::create([
            'nama_perusahaan' => $validated['nama_perusahaan'],
            'bidang_usaha' => $validated['bidang_usaha'],
            'alamat_perusahaan' => $validated['alamat_perusahaan'],
            'email_resmi' => $validated['email_resmi'],
            'no_telepon' => $validated['no_telepon'],
            'nama_pic' => $validated['nama_pic'],
            'jabatan_pic' => $validated['jabatan_pic'],
            'jenis_kerjasama' => $jenisKerjasama,
            'pesan_tambahan' => $validated['pesan_tambahan'] ?? null,
            'status' => 'MENUNGGU_REVIEW',
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Permohonan kerja sama berhasil dikirimkan ke tim BKK SMK Pelita Nusantara.',
                'data' => $permohonan,
            ], 201);
        }

        return redirect()
            ->to(route('bkk.kerjasama') . '#form-kemitraan')
            ->with('success', 'Terima kasih! Permohonan kerja sama industri Anda telah berhasil dikirimkan ke tim BKK SMK Plus Pelita Nusantara. Tim kami akan segera menghubungi PIC yang bersangkutan.');
    }
}
