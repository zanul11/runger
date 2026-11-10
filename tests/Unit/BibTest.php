<?php

namespace Tests\Unit;

use App\Support\Bib;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/** Kasus diambil dari tests/Fixtures/bib-normalize-cases.json — juga dipakai test JS scanner. */
class BibTest extends TestCase
{
    public static function cases(): array
    {
        $json = json_decode(file_get_contents(__DIR__.'/../Fixtures/bib-normalize-cases.json'), true);

        return collect($json['cases'])
            ->mapWithKeys(fn ($c) => [var_export($c['input'], true) => [$c['input'], $c['expected']]])
            ->all();
    }

    #[DataProvider('cases')]
    public function test_normalize(mixed $input, ?string $expected): void
    {
        $this->assertSame($expected, Bib::normalize($input));
    }

    public function test_registration_number(): void
    {
        $this->assertSame('GTR202600123', Bib::normalizeRegistrationNumber(' gtr202600123 '));
        $this->assertNull(Bib::normalizeRegistrationNumber('7001'));
        $this->assertNull(Bib::normalizeRegistrationNumber('GTR-2026'));
    }
}
