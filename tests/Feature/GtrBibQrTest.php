<?php

namespace Tests\Feature;

use App\Support\GtrBibPdf;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Tests\TestCase;

/** QR di BIB cetak berisi nomor BIB persis (dibaca scanner timing & racepack). */
class GtrBibQrTest extends TestCase
{
    public function test_qr_contains_exact_bib_number(): void
    {
        foreach (['7001', '5158', '71000'] as $bib) {
            $file = GtrBibPdf::qr($bib);

            $this->assertFileExists($file);
            $decoded = (new QRCode(new QROptions(['readerUseImagickIfAvailable' => false])))->readFromFile($file)->data;
            $this->assertSame($bib, $decoded);
        }
    }

    public function test_no_qr_for_missing_or_non_numeric_bib(): void
    {
        $this->assertNull(GtrBibPdf::qr('----'));
        $this->assertNull(GtrBibPdf::qr(''));
    }
}
