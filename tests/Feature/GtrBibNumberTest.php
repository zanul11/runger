<?php

namespace Tests\Feature;

use App\Models\GtrCategory;
use App\Models\GtrRegistration;
use App\Models\Runner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class GtrBibNumberTest extends TestCase
{
    use RefreshDatabase;

    private function reg(GtrCategory $cat, string $status = 'pending'): GtrRegistration
    {
        $runner = Runner::create(['first_name' => 'R', 'email' => Str::uuid() . '@t.id', 'password' => 'x']);

        return GtrRegistration::create([
            'runner_id' => $runner->id,
            'gtr_category_id' => $cat->id,
            'full_name' => 'Peserta',
            'payment_status' => $status,
        ]);
    }

    public function test_no_bib_before_paid(): void
    {
        $cat = GtrCategory::create(['name' => 'A', 'slug' => 'a', 'distance' => '7 KM', 'bib_prefix' => '7']);
        $r = $this->reg($cat);

        $this->assertNull($r->bib_number);
    }

    public function test_bib_generated_on_paid_with_prefix_and_padding(): void
    {
        $cat = GtrCategory::create(['name' => 'A', 'slug' => 'a', 'distance' => '7 KM', 'bib_prefix' => '7']);

        $r1 = $this->reg($cat);
        $r1->update(['payment_status' => 'paid']);
        $this->assertSame('7001', $r1->fresh()->bib_number);

        $r2 = $this->reg($cat);
        $r2->update(['payment_status' => 'paid']);
        $this->assertSame('7002', $r2->fresh()->bib_number);
    }

    public function test_sequence_is_per_category(): void
    {
        $a = GtrCategory::create(['name' => 'A', 'slug' => 'a', 'distance' => '7 KM', 'bib_prefix' => '7']);
        $b = GtrCategory::create(['name' => 'B', 'slug' => 'b', 'distance' => '15 KM', 'bib_prefix' => '1']);

        $ra = $this->reg($a);
        $ra->update(['payment_status' => 'paid']);
        $rb = $this->reg($b);
        $rb->update(['payment_status' => 'paid']);

        $this->assertSame('7001', $ra->fresh()->bib_number);
        $this->assertSame('1001', $rb->fresh()->bib_number);
    }

    public function test_over_999_becomes_four_digits(): void
    {
        $cat = GtrCategory::create(['name' => 'A', 'slug' => 'a', 'distance' => '7 KM', 'bib_prefix' => '7']);

        // Simulasikan sudah ada BIB tertinggi 7999.
        $seed = $this->reg($cat);
        $seed->forceFill(['bib_number' => '7999'])->saveQuietly();

        $next = $this->reg($cat);
        $next->update(['payment_status' => 'paid']);

        $this->assertSame('71000', $next->fresh()->bib_number);
    }

    public function test_paid_on_create_gets_bib(): void
    {
        $cat = GtrCategory::create(['name' => 'A', 'slug' => 'a', 'distance' => '7 KM', 'bib_prefix' => '7']);
        $r = $this->reg($cat, 'paid');

        $this->assertSame('7001', $r->fresh()->bib_number);
    }

    public function test_existing_bib_not_overwritten(): void
    {
        $cat = GtrCategory::create(['name' => 'A', 'slug' => 'a', 'distance' => '7 KM', 'bib_prefix' => '7']);
        $r = $this->reg($cat);
        $r->forceFill(['bib_number' => '7500'])->saveQuietly();

        $r->update(['payment_status' => 'paid']);

        $this->assertSame('7500', $r->fresh()->bib_number);
    }
}
