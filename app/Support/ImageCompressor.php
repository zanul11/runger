<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ImageCompressor
{
    /**
     * Kompres & resize gambar yang diupload, simpan ke disk public, kembalikan path relatif.
     * Dipakai sebagai callback Filament FileUpload::saveUploadedFileUsing().
     */
    public static function store(TemporaryUploadedFile $file, string $directory, int $maxWidth = 1600, int $quality = 62): string
    {
        $directory = trim($directory, '/');
        $path = $file->getRealPath();
        $originalSize = @filesize($path) ?: 0;
        $info = @getimagesize($path);

        // Bukan gambar raster yang didukung / GD tak ada → simpan apa adanya.
        if (! $info || ! function_exists('imagecreatetruecolor')) {
            return $file->storePublicly($directory, 'public');
        }

        [$width, $height, $type] = $info;

        $src = match ($type) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($path),
            IMAGETYPE_PNG => @imagecreatefrompng($path),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : false,
            default => false,
        };

        if (! $src) {
            return $file->storePublicly($directory, 'public');
        }

        // Resize proporsional bila lebih lebar dari batas.
        if ($width > $maxWidth) {
            $newW = $maxWidth;
            $newH = (int) round($height * ($maxWidth / $width));
            $dst = imagecreatetruecolor($newW, $newH);

            if (in_array($type, [IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
                imagealphablending($dst, false);
                imagesavealpha($dst, true);
            }

            imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $width, $height);
            imagedestroy($src);
            $src = $dst;
        }

        $ext = match ($type) {
            IMAGETYPE_PNG => 'png',
            IMAGETYPE_WEBP => 'webp',
            default => 'jpg',
        };

        $relative = $directory . '/' . Str::ulid() . '.' . $ext;
        $absolute = Storage::disk('public')->path($relative);

        if (! is_dir(dirname($absolute))) {
            @mkdir(dirname($absolute), 0755, true);
        }

        if ($type === IMAGETYPE_PNG) {
            // PNG: kompresi maksimal + filter (lossless).
            imagepng($src, $absolute, 9, PNG_ALL_FILTERS);
        } else {
            // JPEG/WebP: turunkan kualitas bertahap sampai ukuran ≤ 40% dari asli
            // (target pengurangan ~60%), dengan batas bawah kualitas 42.
            $target = $originalSize > 0 ? (int) ($originalSize * 0.4) : 0;
            $q = $quality;

            do {
                if ($type === IMAGETYPE_WEBP) {
                    imagewebp($src, $absolute, $q);
                } else {
                    imagejpeg($src, $absolute, $q);
                }
                $q -= 8;
            } while ($target > 0 && @filesize($absolute) > $target && $q >= 42);
        }

        imagedestroy($src);

        return $relative;
    }
}
