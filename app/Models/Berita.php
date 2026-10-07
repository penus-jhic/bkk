<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Berita extends Model
{
    use HasFactory;

    protected $table = 'berita';

    protected $fillable = [
        'judul',
        'slug',
        'kategori_id',
        'ringkasan',
        'konten',
        'gambar_sampul',
        'caption_gambar',
        'penulis_nama',
        'penulis_jabatan',
        'penulis_avatar',
        'penulis_bio',
        'estimasi_baca',
        'is_featured',
        'status',
        'views_count',
        'published_at',
    ];

    protected $casts = [
        'is_featured' => 'boolean',
        'published_at' => 'datetime',
        'views_count' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->slug)) {
                $baseSlug = Str::slug($model->judul);
                $slug = $baseSlug;
                $counter = 1;
                while (static::where('slug', $slug)->exists()) {
                    $slug = $baseSlug . '-' . $counter++;
                }
                $model->slug = $slug;
            }

            if (empty($model->estimasi_baca) && !empty($model->konten)) {
                $wordCount = str_word_count(strip_tags($model->konten));
                $minutes = max(1, (int) ceil($wordCount / 200));
                $model->estimasi_baca = "{$minutes} Menit Baca";
            }

            if (empty($model->published_at) && $model->status === 'PUBLISHED') {
                $model->published_at = Carbon::now();
            }
        });
    }

    public function kategori(): BelongsTo
    {
        return $this->belongsTo(KategoriBerita::class, 'kategori_id');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'PUBLISHED');
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    /**
     * Parse and render markdown content to HTML.
     */
    public function getRenderedKontenAttribute(): string
    {
        return Str::markdown($this->konten ?? '', [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);
    }

    /**
     * Get formatted Indonesian publish date.
     */
    public function getFormattedDateAttribute(): string
    {
        $date = $this->published_at ?? $this->created_at;
        if (!$date) return '-';

        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];

        return $date->format('d') . ' ' . ($months[(int)$date->format('n')] ?? '') . ' ' . $date->format('Y');
    }
}
