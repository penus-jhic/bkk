<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class StaticAssetController extends Controller
{
    /**
     * Mappings MIME type umum untuk menjamin header Content-Type tepat
     */
    protected array $mimeTypes = [
        'css'   => 'text/css; charset=UTF-8',
        'js'    => 'application/javascript; charset=UTF-8',
        'svg'   => 'image/svg+xml',
        'webp'  => 'image/webp',
        'png'   => 'image/png',
        'jpg'   => 'image/jpeg',
        'jpeg'  => 'image/jpeg',
        'gif'   => 'image/gif',
        'ico'   => 'image/x-icon',
        'pdf'   => 'application/pdf',
        'woff'  => 'font/woff',
        'woff2' => 'font/woff2',
        'ttf'   => 'font/ttf',
    ];

    /**
     * Sajikan file gambar dari public/images/
     */
    public function serveImage(Request $request, string $path): BinaryFileResponse
    {
        return $this->deliverFile(public_path('images'), $path);
    }

    /**
     * Sajikan file unggahan dari public/uploads/
     */
    public function serveUpload(Request $request, string $path): BinaryFileResponse
    {
        return $this->deliverFile(public_path('uploads'), $path);
    }

    /**
     * Sajikan file storage dari storage/app/public/ (atau public/storage/)
     */
    public function serveStorage(Request $request, string $path): BinaryFileResponse
    {
        $primaryDir = storage_path('app/public');
        $fallbackDir = public_path('storage');

        if (file_exists($primaryDir . DIRECTORY_SEPARATOR . ltrim($path, '/\\'))) {
            return $this->deliverFile($primaryDir, $path);
        }

        if (file_exists($fallbackDir . DIRECTORY_SEPARATOR . ltrim($path, '/\\'))) {
            return $this->deliverFile($fallbackDir, $path);
        }

        abort(404, 'File not found in storage');
    }

    /**
     * Sajikan file build Vite dari public/build/
     */
    public function serveBuild(Request $request, string $path): BinaryFileResponse
    {
        return $this->deliverFile(public_path('build'), $path);
    }

    /**
     * Validasi path, proteksi directory traversal, dan kirimkan response file dengan caching
     */
    protected function deliverFile(string $baseDir, string $relativePath): BinaryFileResponse
    {
        // 1. Tolak karakter traversal berbahaya atau null-byte
        if (str_contains($relativePath, '..') || str_contains($relativePath, "\0")) {
            abort(404, 'Invalid asset path');
        }

        $baseRealPath = realpath($baseDir);
        if ($baseRealPath === false) {
            abort(404, 'Base directory does not exist');
        }

        $targetPath = $baseDir . DIRECTORY_SEPARATOR . ltrim($relativePath, '/\\');
        $fileRealPath = realpath($targetPath);

        // 2. Pastikan file ada dan berstatus berkas reguler
        if ($fileRealPath === false || !is_file($fileRealPath)) {
            abort(404, 'Asset not found');
        }

        // 3. Pastikan path berkas berada di dalam base directory (proteksi symlink escape & path traversal)
        $normalizedBase = rtrim(str_replace('\\', '/', strtolower($baseRealPath)), '/') . '/';
        $normalizedFile = str_replace('\\', '/', strtolower($fileRealPath));

        if (!str_starts_with($normalizedFile, $normalizedBase)) {
            abort(404, 'Unauthorized asset path');
        }

        // 4. Siapkan HTTP headers dengan caching
        $extension = strtolower(pathinfo($fileRealPath, PATHINFO_EXTENSION));
        $headers = [
            'Cache-Control' => 'public, max-age=86400, must-revalidate',
        ];

        if (isset($this->mimeTypes[$extension])) {
            $headers['Content-Type'] = $this->mimeTypes[$extension];
        }

        return response()->file($fileRealPath, $headers);
    }
}
