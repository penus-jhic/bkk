<?php

namespace App\Http\Controllers\Mitra;

use App\Http\Controllers\Controller;
use App\Models\Mitra;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class MitraAuthController extends Controller
{
    /**
     * Tampilan Halaman Login Mitra (/bkk/dashboard/login)
     */
    public function showLogin(Request $request): View|RedirectResponse
    {
        $mitraId = $request->session()->get('mitra_id');
        if ($mitraId && Mitra::where('id', $mitraId)->exists()) {
            return redirect()->route('bkk.mitra.dashboard');
        }

        // Ambil daftar mitra terdaftar untuk quick login helper
        $registeredMitras = Mitra::where('is_verified', true)
            ->select('id', 'nama_perusahaan', 'singkatan', 'kota', 'sektor_industri')
            ->orderBy('nama_perusahaan')
            ->get();

        return view('mitra.pages.login', compact('registeredMitras'));
    }

    /**
     * Handler Proses Autentikasi Login Mitra
     */
    public function login(Request $request): RedirectResponse
    {
        $request->validate([
            'nama_perusahaan' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ], [
            'nama_perusahaan.required' => 'Nama Perusahaan wajib diisi.',
            'password.required' => 'Kata sandi akun mitra wajib diisi.',
        ]);

        // Sanitasi Nama Perusahaan: trim spasi tepi dan normalisasi multi-spasi
        $rawInput = (string) $request->input('nama_perusahaan');
        $sanitizedName = trim(preg_replace('/\s+/', ' ', $rawInput));

        // Pencarian case-insensitive pada database
        $mitra = Mitra::whereRaw('LOWER(TRIM(nama_perusahaan)) = LOWER(?)', [$sanitizedName])
            ->orWhereRaw('LOWER(TRIM(singkatan)) = LOWER(?)', [$sanitizedName])
            ->first();

        if (!$mitra || !Hash::check($request->input('password'), $mitra->password)) {
            return back()
                ->withInput($request->only('nama_perusahaan', 'remember'))
                ->with('error', 'Nama Perusahaan atau kata sandi tidak cocok dengan data kemitraan kami.');
        }

        if (!$mitra->is_verified) {
            return back()
                ->withInput($request->only('nama_perusahaan'))
                ->with('error', "Akun kemitraan '{$mitra->nama_perusahaan}' saat ini masih dalam proses peninjauan / dinonaktifkan oleh Admin BKK.");
        }

        // Simpan sesi autentikasi mitra
        $request->session()->regenerate();
        $request->session()->put('mitra_id', $mitra->id);

        return redirect()
            ->route('bkk.mitra.dashboard')
            ->with('success', "Selamat datang di Portal IDUKA, {$mitra->nama_perusahaan}!");
    }

    /**
     * Handler Logout Mitra (Hapus Sesi)
     */
    public function logout(Request $request): RedirectResponse
    {
        $request->session()->forget('mitra_id');
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('bkk.mitra.login')
            ->with('success', 'Anda telah berhasil keluar dari akun Mitra IDUKA.');
    }

    /**
     * Halaman Pengaturan Akun Mitra (/bkk/dashboard/pengaturan)
     */
    public function pengaturan(Request $request): View
    {
        $mitra = $request->attributes->get('mitra') ?? Mitra::findOrFail($request->session()->get('mitra_id'));
        return view('mitra.pages.pengaturan', compact('mitra'));
    }

    /**
     * Update Password Mitra
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        $mitra = $request->attributes->get('mitra') ?? Mitra::findOrFail($request->session()->get('mitra_id'));

        $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'current_password.required' => 'Password saat ini wajib diisi.',
            'password.required' => 'Password baru wajib diisi.',
            'password.min' => 'Password baru minimal harus 8 karakter.',
            'password.confirmed' => 'Konfirmasi password baru tidak cocok.',
        ]);

        if (!Hash::check($request->input('current_password'), $mitra->password)) {
            return back()->with('password_error', 'Password saat ini yang Anda masukkan salah.');
        }

        $mitra->update([
            'password' => Hash::make($request->input('password')),
        ]);

        return back()->with('password_success', 'Kata sandi akun Mitra berhasil diperbarui!');
    }

    /**
     * Update Logo Perusahaan (Maks 5 MB: png, jpg, jpeg, ico)
     */
    public function updateLogo(Request $request): RedirectResponse
    {
        $mitra = $request->attributes->get('mitra') ?? Mitra::findOrFail($request->session()->get('mitra_id'));

        $request->validate([
            'logo' => ['required', 'file', 'mimes:png,jpg,jpeg,ico', 'max:5120'], // 5120 KB = 5 MB
        ], [
            'logo.required' => 'Silakan pilih berkas logo perusahaan.',
            'logo.mimes' => 'Format logo harus berupa berkas .png, .jpg, .jpeg, atau .ico.',
            'logo.max' => 'Ukuran berkas logo maksimal 5 MB.',
        ]);

        $file = $request->file('logo');
        $extension = $file->guessExtension() ?: 'png';
        $fileName = 'mitra_' . $mitra->id . '_' . Str::random(24) . '.' . $extension;
        $targetDir = public_path('uploads/mitra_logos');

        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $file->move($targetDir, $fileName);
        $publicUrl = '/bkk/uploads/mitra_logos/' . $fileName;

        $mitra->update([
            'logo_url' => $publicUrl,
        ]);

        return back()->with('logo_success', 'Logo perusahaan berhasil diunggah dan diperbarui!');
    }

    /**
     * Update Informasi Kontak & Alamat Profil Mitra
     */
    public function updateProfil(Request $request): RedirectResponse
    {
        $mitra = $request->attributes->get('mitra') ?? Mitra::findOrFail($request->session()->get('mitra_id'));

        $validated = $request->validate([
            'singkatan' => ['nullable', 'string', 'max:50'],
            'sektor_industri' => ['required', 'string', 'max:150'],
            'alamat_kantor' => ['required', 'string'],
            'kota' => ['required', 'string', 'max:100'],
            'website' => ['nullable', 'url', 'max:255'],
            'email_perusahaan' => ['required', 'email', 'max:150'],
            'no_telp_perusahaan' => ['required', 'string', 'max:50'],
            'pic_name' => ['required', 'string', 'max:150'],
            'pic_role' => ['required', 'string', 'max:150'],
            'pic_email' => ['required', 'email', 'max:150'],
            'pic_phone' => ['required', 'string', 'max:50'],
        ], [
            'sektor_industri.required' => 'Sektor industri wajib diisi.',
            'alamat_kantor.required' => 'Alamat kantor wajib diisi.',
            'kota.required' => 'Kota kantor wajib diisi.',
            'email_perusahaan.required' => 'Email perusahaan wajib diisi.',
            'no_telp_perusahaan.required' => 'Nomor telepon perusahaan wajib diisi.',
            'pic_name.required' => 'Nama PIC HRD wajib diisi.',
            'pic_role.required' => 'Jabatan PIC HRD wajib diisi.',
            'pic_email.required' => 'Email PIC HRD wajib diisi.',
            'pic_phone.required' => 'Nomor telepon/WhatsApp PIC wajib diisi.',
        ]);

        $mitra->update($validated);

        return back()->with('profil_success', 'Informasi profil kemitraan berhasil disimpan!');
    }
}
