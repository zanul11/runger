<?php

namespace Tests\Feature;

use App\Models\GtrCategory;
use App\Models\GtrRegistration;
use App\Models\Runner;
use App\Services\BibNumberService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class GtrChangeCategoryTest extends TestCase
{
    use RefreshDatabase;

    private function paidReg(GtrCategory $cat): GtrRegistration
    {
        $runner = Runner::create(['first_name' => 'R', 'email' => Str::uuid() . '@t.id', 'password' => 'x']);
        $reg = GtrRegistration::create([
            'runner_id' => $runner->id, 'gtr_category_id' => $cat->id,
            'full_name' => 'Peserta', 'pay' => 'Cash', 'payment_status' => 'pending',
        ]);
        $reg->update(['payment_status' => 'paid']); // memicu BIB otomatis

        return $reg->fresh();
    }

    public function test_bib_reissued_with_new_category_prefix(): void
    {
        $a = GtrCategory::create(['name' => '7K', 'slug' => '7k', 'distance' => '7 KM', 'bib_prefix' => '7']);
        $b = GtrCategory::create(['name' => '15K', 'slug' => '15k', 'distance' => '15 KM', 'bib_prefix' => '1']);

        $reg = $this->paidReg($a);
        $this->assertSame('7001', $reg->bib_number);

        // Pindah kategori → BIB terbit ulang di kategori baru.
        $reg->forceFill(['gtr_category_id' => $b->id])->saveQuietly();
        $newBib = app(BibNumberService::class)->reassignFor($reg->refresh());

        $this->assertSame('1001', $newBib);
        $this->assertSame('1001', $reg->fresh()->bib_number);
        $this->assertSame('7001', $reg->fresh()->previous_bib_number); // jejak audit
    }

    public function test_old_number_is_not_reused_by_next_participant(): void
    {
        $a = GtrCategory::create(['name' => '7K', 'slug' => '7k', 'distance' => '7 KM', 'bib_prefix' => '7']);
        $b = GtrCategory::create(['name' => '15K', 'slug' => '15k', 'distance' => '15 KM', 'bib_prefix' => '1']);

        $first = $this->paidReg($a);          // 7001
        $second = $this->paidReg($a);         // 7002
        $this->assertSame('7002', $second->bib_number);

        // first pindah ke 15K → 7001 kosong, TIDAK boleh dipakai ulang.
        $first->forceFill(['gtr_category_id' => $b->id])->saveQuietly();
        app(BibNumberService::class)->reassignFor($first->refresh());

        $third = $this->paidReg($a);
        $this->assertSame('7003', $third->bib_number, 'nomor lama tidak boleh dipakai ulang');
    }

    public function test_sequence_continues_in_target_category(): void
    {
        $a = GtrCategory::create(['name' => '7K', 'slug' => '7k', 'distance' => '7 KM', 'bib_prefix' => '7']);
        $b = GtrCategory::create(['name' => '15K', 'slug' => '15k', 'distance' => '15 KM', 'bib_prefix' => '1']);

        $this->paidReg($b);                    // 1001 sudah terpakai di kategori tujuan
        $mover = $this->paidReg($a);           // 7001

        $mover->forceFill(['gtr_category_id' => $b->id])->saveQuietly();
        $newBib = app(BibNumberService::class)->reassignFor($mover->refresh());

        $this->assertSame('1002', $newBib, 'lanjut urutan kategori tujuan');
    }
}
