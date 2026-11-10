<?php

namespace Tests\Unit;

use App\Support\Passes;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/** Kasus dari tests/Fixtures/pass-cluster-cases.json — juga dipakai test JS scanner. */
class PassesTest extends TestCase
{
    public static function cases(): array
    {
        $json = json_decode(file_get_contents(__DIR__.'/../Fixtures/pass-cluster-cases.json'), true);

        return collect($json['cases'])->mapWithKeys(fn ($c) => [$c['name'] => [$c['times'], $c['expected']]])->all();
    }

    #[DataProvider('cases')]
    public function test_cluster(array $times, array $expected): void
    {
        $this->assertSame($expected, Passes::cluster($times));
    }

    public function test_gap_is_ten_minutes(): void
    {
        $this->assertSame(600_000, Passes::GAP_MS);
    }

    public function test_visits_from_pivot_json_or_fallback(): void
    {
        $this->assertSame(
            [['code' => 'WS1', 'km' => 7.59, 'cutoff' => null], ['code' => 'WS2', 'km' => 12.3, 'cutoff' => null]],
            Passes::visits('[{"code":"WS1","km":7.59,"cutoff":null},{"code":"WS2","km":12.3,"cutoff":null}]', 'X'),
        );
        $this->assertSame([['code' => 'CP1', 'km' => 3.57, 'cutoff' => null]], Passes::visits(null, 'CP1', '3.57'));
    }
}
