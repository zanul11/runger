<?php

namespace App\Support;

/**
 * Peta 3D race village GTR: halaman statis di public/ (index.html + assets/ gambar), ditampilkan
 * lewat iframe di website (/gerung-trail-run/race-village) dan Runger App (Guide → Race Village).
 */
class RaceVillage
{
    public const DIR = 'assets/gtr/race-village/Peta-Race-Village-GTR-web';

    /** URL relatif peta 3D (+ versi dari waktu ubah file), atau null bila belum dipasang. */
    public static function url(): ?string
    {
        return self::fileUrl('index.html');
    }

    /** URL relatif denah 2D (tampak atas, ringan & tanpa library 3D), atau null bila tidak ada. */
    public static function url2d(): ?string
    {
        return self::fileUrl('denah-2d.html');
    }

    private static function fileUrl(string $name): ?string
    {
        $file = public_path(self::DIR.'/'.$name);

        return is_file($file) ? '/'.self::DIR.'/'.$name.'?v='.filemtime($file) : null;
    }
}
