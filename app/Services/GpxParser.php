<?php

namespace App\Services;

use SimpleXMLElement;

/**
 * Parse a GPX file and derive:
 *  - total_km, elevation_gain_m, elevation_min_m, elevation_max_m
 *  - tikum_lat / tikum_lng (start point)
 *  - route_points (RDP-simplified [[lat, lon], ...])
 *  - km_markers ([{km, lat, lon}, ...])
 *  - elevation_profile (sampled [[km, ele], ...] for chart, ~120 points)
 *  - name (from <name> tag if present)
 */
class GpxParser
{
    /**
     * Parse the GPX file at the absolute path and return a flat assoc array
     * matching the `routes` table columns.
     */
    public static function parse(string $absolutePath): array
    {
        $content = @file_get_contents($absolutePath);
        if (! $content) {
            return [];
        }

        // Strip default namespaces so XPath simple queries work.
        $clean = preg_replace('/\s(xmlns)="[^"]+"/i', '', $content, 1);

        try {
            $xml = new SimpleXMLElement($clean);
        } catch (\Throwable $e) {
            return [];
        }

        $trkName = (string) ($xml->trk->name ?? '');

        $points = [];
        foreach ($xml->xpath('//trkpt') as $pt) {
            $lat = (float) $pt['lat'];
            $lon = (float) $pt['lon'];
            $ele = isset($pt->ele) ? (float) $pt->ele : null;
            $points[] = [$lat, $lon, $ele];
        }

        if (empty($points)) {
            return [];
        }

        // Cumulative distance via haversine
        $cum = [0.0];
        $totalM = 0.0;
        for ($i = 1; $i < count($points); $i++) {
            $d = self::haversine($points[$i - 1][0], $points[$i - 1][1], $points[$i][0], $points[$i][1]);
            $totalM += $d;
            $cum[$i] = $totalM;
        }

        // Elevation gain (sum positive deltas), min/max
        $elevGain = 0.0;
        $elevMin = PHP_INT_MAX;
        $elevMax = PHP_INT_MIN;
        $lastEle = null;
        foreach ($points as $p) {
            if ($p[2] === null) {
                continue;
            }
            if ($lastEle !== null && $p[2] > $lastEle) {
                $elevGain += $p[2] - $lastEle;
            }
            $elevMin = min($elevMin, $p[2]);
            $elevMax = max($elevMax, $p[2]);
            $lastEle = $p[2];
        }
        if ($elevMin === PHP_INT_MAX) {
            $elevMin = null;
            $elevMax = null;
        }

        // KM markers
        $kmMarkers = [];
        $maxKm = (int) floor($totalM / 1000);
        for ($k = 1; $k <= $maxKm; $k++) {
            $target = $k * 1000.0;
            for ($i = 0; $i < count($cum); $i++) {
                if ($cum[$i] >= $target) {
                    $kmMarkers[] = ['km' => $k, 'lat' => $points[$i][0], 'lon' => $points[$i][1]];
                    break;
                }
            }
        }

        // Elevation profile: sample ~120 points evenly along the route
        $profile = self::sampleElevationProfile($points, $cum, $totalM, 120);

        // Simplified polyline (RDP) — target ~100 vertices for display
        $latLon = array_map(fn ($p) => [$p[0], $p[1]], $points);
        $simplified = self::rdp($latLon, 0.00003);

        return [
            'name' => $trkName ?: null,
            'total_km' => round($totalM / 1000, 2),
            'elevation_gain_m' => (int) round($elevGain),
            'elevation_min_m' => $elevMin !== null ? (int) round($elevMin) : null,
            'elevation_max_m' => $elevMax !== null ? (int) round($elevMax) : null,
            'km_marker_count' => count($kmMarkers),
            'tikum_lat' => $points[0][0],
            'tikum_lng' => $points[0][1],
            'route_points' => $simplified,
            'km_markers' => $kmMarkers,
            'elevation_profile' => $profile,
        ];
    }

    /** Sample ~$targetCount evenly-spaced [km, ele] pairs across the route. */
    protected static function sampleElevationProfile(array $points, array $cum, float $totalM, int $targetCount): array
    {
        if ($totalM <= 0 || empty($points)) {
            return [];
        }
        $out = [];
        $step = $totalM / max($targetCount - 1, 1);
        $cursor = 0;
        for ($i = 0; $i < $targetCount; $i++) {
            $target = $i * $step;
            while ($cursor < count($cum) - 1 && $cum[$cursor + 1] < $target) {
                $cursor++;
            }
            $ele = $points[$cursor][2] ?? null;
            if ($ele !== null) {
                $out[] = [round($cum[$cursor] / 1000, 3), (int) round($ele)];
            }
        }
        return $out;
    }

    /** Haversine distance in meters between two lat/lon points. */
    protected static function haversine(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $R = 6371000.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;
        return 2 * $R * asin(sqrt($a));
    }

    /** Ramer-Douglas-Peucker simplification on [lat, lon] points (degrees as planar approximation). */
    protected static function rdp(array $pts, float $epsilon): array
    {
        $n = count($pts);
        if ($n < 3) {
            return $pts;
        }
        $dmax = 0;
        $idx = 0;
        for ($i = 1; $i < $n - 1; $i++) {
            $d = self::perpDistance($pts[$i], $pts[0], $pts[$n - 1]);
            if ($d > $dmax) {
                $dmax = $d;
                $idx = $i;
            }
        }
        if ($dmax > $epsilon) {
            $left = self::rdp(array_slice($pts, 0, $idx + 1), $epsilon);
            $right = self::rdp(array_slice($pts, $idx), $epsilon);
            return array_merge(array_slice($left, 0, -1), $right);
        }
        return [$pts[0], $pts[$n - 1]];
    }

    /** Perpendicular distance from point p to line a-b in lat/lon space. */
    protected static function perpDistance(array $p, array $a, array $b): float
    {
        if ($a === $b) {
            return hypot($p[0] - $a[0], $p[1] - $a[1]);
        }
        $dx = $b[0] - $a[0];
        $dy = $b[1] - $a[1];
        $norm = hypot($dx, $dy);
        if ($norm === 0.0) {
            return 0.0;
        }
        return abs($dy * $p[0] - $dx * $p[1] + $b[0] * $a[1] - $b[1] * $a[0]) / $norm;
    }
}
