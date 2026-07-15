<?php

namespace App\Console\Commands;

use App\Support\ImageCompressor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Backfill: optimasi gambar GTR yang SUDAH terupload (input dari menu admin) ke WebP.
 * Dijalankan di server tempat file berada. Non-destruktif: file asli dibackup ke
 * `originals/…` pada disk public, path DB diperbarui ke file .webp.
 *
 * Pakai --dry untuk proyeksi tanpa mengubah apa pun.
 */
class OptimizeGtrImages extends Command
{
    protected $signature = 'gtr:optimize-images {--dry : Proyeksi saja} {--quality=78} {--max=1600}';

    protected $description = 'Kompres gambar hasil input menu GTR (yang sudah terupload) ke WebP.';

    /** table => [kolom gambar...] */
    private array $map = [
        'gtr_settings' => ['header_image'],
        'gtr_categories' => ['header_image'],
        'gtr_overviews' => ['photo_main', 'photo_2', 'photo_3'],
        'gtr_scenics' => ['image'],
        'gallery_items' => ['image'],
        'events' => ['hero_image', 'poster_image'],
    ];

    public function handle(): int
    {
        $dry = (bool) $this->option('dry');
        $quality = (int) $this->option('quality');
        $maxW = (int) $this->option('max');
        $disk = Storage::disk('public');

        $before = 0;
        $after = 0;
        $done = 0;
        $missing = 0;

        foreach ($this->map as $table => $columns) {
            foreach (DB::table($table)->get() as $row) {
                foreach ($columns as $col) {
                    $path = $row->{$col} ?? null;
                    if (! $path) {
                        continue;
                    }
                    if (! $disk->exists($path)) {
                        $missing++;

                        continue;
                    }

                    // Sudah webp & kecil → lewati.
                    $origBytes = $disk->size($path);
                    $webp = ImageCompressor::toWebp($disk->get($path), $maxW, $quality);
                    if ($webp === null) {
                        continue;
                    }

                    $newBytes = strlen($webp);
                    // Bila sudah .webp dan tak lebih kecil, biarkan.
                    if (str_ends_with(strtolower($path), '.webp') && $newBytes >= $origBytes) {
                        continue;
                    }

                    $before += $origBytes;
                    $after += $newBytes;
                    $done++;
                    $this->line(sprintf('  %-15s %-42s %s → %s', $table,
                        \Illuminate\Support\Str::limit(basename($path), 42),
                        $this->human($origBytes), $this->human($newBytes)));

                    if ($dry) {
                        continue;
                    }

                    $newPath = preg_replace('/\.[^.\/]+$/', '', $path) . '.webp';
                    if (! $disk->exists('originals/' . $path)) {
                        $disk->copy($path, 'originals/' . $path);
                    }
                    $disk->put($newPath, $webp);
                    if ($newPath !== $path) {
                        $disk->delete($path);
                    }
                    DB::table($table)->where('id', $row->id)->update([$col => $newPath]);
                }
            }
        }

        $saved = $before - $after;
        $pct = $before > 0 ? round($saved / $before * 100) : 0;
        $this->newLine();
        $this->info(($dry ? '[DRY] ' : '') . "{$done} gambar" . ($missing ? " ({$missing} file tak ada di disk ini)" : '')
            . " · {$this->human($before)} → {$this->human($after)} · hemat {$this->human($saved)} ({$pct}%)");
        if ($dry) {
            $this->comment('Jalankan tanpa --dry di server tempat file berada. Asli dibackup di storage originals/.');
        }

        return self::SUCCESS;
    }

    private function human(int $b): string
    {
        return $b >= 1048576 ? round($b / 1048576, 1) . 'MB' : ($b >= 1024 ? round($b / 1024) . 'KB' : $b . 'B');
    }
}
