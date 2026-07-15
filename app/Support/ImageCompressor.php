<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ImageCompressor
{
    /**
     * Kompres + resize gambar upload → simpan sebagai WebP ke disk public,
     * kembalikan path relatif. Dipakai sebagai callback Filament
     * FileUpload::saveUploadedFileUsing().
     */
    public static function store(TemporaryUploadedFile $file, string $directory, int $maxWidth = 1600, int $quality = 78): string
    {
        $directory = trim($directory, '/');
        $data = @file_get_contents($file->getRealPath());

        $webp = $data ? self::toWebp($data, $maxWidth, $quality) : null;

        // Bukan gambar yang bisa diproses / GD tak ada → simpan apa adanya.
        if ($webp === null) {
            return $file->storePublicly($directory, 'public');
        }

        $relative = $directory . '/' . Str::ulid() . '.webp';
        Storage::disk('public')->put($relative, $webp);

        return $relative;
    }

    /**
     * Inti: ubah data gambar mentah (jpeg/png/webp) → biner WebP hasil resize.
     * Return null bila GD/WebP tak tersedia atau bukan gambar yang didukung.
     */
    public static function toWebp(string $data, int $maxWidth = 1600, int $quality = 78): ?string
    {
        if (! function_exists('imagewebp') || ! function_exists('imagecreatetruecolor')) {
            return null;
        }

        $src = @imagecreatefromstring($data);
        if (! $src) {
            return null;
        }

        $w = imagesx($src);
        $h = imagesy($src);
        $scale = $w > $maxWidth ? $maxWidth / $w : 1.0;
        $nw = max(1, (int) round($w * $scale));
        $nh = max(1, (int) round($h * $scale));

        if ($scale < 1.0) {
            $dst = imagecreatetruecolor($nw, $nh);
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
            imagedestroy($src);
        } else {
            imagealphablending($src, false);
            imagesavealpha($src, true);
            $dst = $src;
        }

        ob_start();
        imagewebp($dst, null, $quality);
        $out = ob_get_clean();
        imagedestroy($dst);

        return $out !== '' ? $out : null;
    }
}
