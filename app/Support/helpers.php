<?php

if (!function_exists('media_url')) {
    /**
     * Resolve a media path to a public URL.
     * - Legacy public/ paths (assets/, gallery/, uploads/) → asset()
     * - http(s):// → return as-is
     * - everything else → treat as Filament FileUpload storage path (storage/app/public)
     */
    function media_url(?string $path): ?string
    {
        if (!$path) {
            return null;
        }
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }
        $path = ltrim($path, '/');
        if (
            str_starts_with($path, 'assets/') ||
            str_starts_with($path, 'gallery/') ||
            str_starts_with($path, 'uploads/') ||
            str_starts_with($path, 'storage/')
        ) {
            return asset($path);
        }
        // Filament FileUpload stored to storage/app/public (default disk = public)
        return asset('storage/' . $path);
    }
}
