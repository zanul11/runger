<?php

namespace Tests\Feature;

use App\Models\GtrDiscount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GtrDiscountTest extends TestCase
{
    use RefreshDatabase;

    public function test_quota_remaining_and_usable(): void
    {
        $d = GtrDiscount::create(['name' => 'A', 'code' => 'RUNGER25', 'type' => 'fixed', 'value' => 25000, 'quota' => 2]);

        $this->assertSame(2, $d->remaining());
        $this->assertTrue($d->isUsable());

        $d->markUsed();
        $d->markUsed();
        $this->assertSame(0, $d->fresh()->remaining());
        $this->assertFalse($d->fresh()->isUsable(), 'kuota habis → tak bisa dipakai');
    }

    public function test_unlimited_quota(): void
    {
        $d = GtrDiscount::create(['name' => 'B', 'code' => 'UNLIM', 'type' => 'fixed', 'value' => 10000, 'quota' => null]);
        $d->markUsed();

        $this->assertNull($d->remaining());
        $this->assertTrue($d->fresh()->isUsable());
    }

    public function test_find_usable_is_case_insensitive_and_respects_active(): void
    {
        GtrDiscount::create(['name' => 'C', 'code' => 'HEMAT', 'type' => 'percent', 'value' => 10, 'is_active' => true]);
        GtrDiscount::create(['name' => 'D', 'code' => 'MATI', 'type' => 'fixed', 'value' => 5000, 'is_active' => false]);

        $this->assertNotNull(GtrDiscount::findUsable('hemat'));   // case-insensitive
        $this->assertNull(GtrDiscount::findUsable('MATI'));        // nonaktif
        $this->assertNull(GtrDiscount::findUsable('NGACO'));       // tak ada
    }

    public function test_amount_calculation(): void
    {
        $percent = GtrDiscount::make(['type' => 'percent', 'value' => 20]);
        $fixed = GtrDiscount::make(['type' => 'fixed', 'value' => 30000]);

        $this->assertSame(20000, $percent->amountFor(100000));  // 20%
        $this->assertSame(30000, $fixed->amountFor(100000));
        // Potongan tak melebihi nominal (fixed 30rb atas tagihan 20rb → 20rb).
        $this->assertSame(20000, $fixed->amountFor(20000));
    }
}
