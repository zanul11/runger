<?php

namespace Tests\Feature;

use App\Models\GtrCategory;
use App\Models\GtrDiscount;
use App\Models\GtrRegistration;
use App\Models\Runner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GtrCheckoutDiscountTest extends TestCase
{
    use RefreshDatabase;

    private function category(): GtrCategory
    {
        return GtrCategory::create(['name' => '7K', 'slug' => '7k', 'distance' => '7 KM', 'price_normal' => 100000]);
    }

    private function runner(): Runner
    {
        return Runner::create(['first_name' => 'Budi', 'email' => 'budi@t.id', 'phone' => '0811', 'password' => 'secret']);
    }

    private function form(array $override = []): array
    {
        return array_merge([
            'size' => 'L',
            'full_name' => 'Budi Santoso',
            'nik' => '1234567890123456',
            'email' => 'budi@t.id',
            'whatsapp' => '081111111111',
            'birth_date' => '1998-01-01',
            'gender' => 'Laki-laki',
            'address' => 'Gerung',
            'blood_type' => 'O',
            'emergency_name' => 'Ibu',
            'emergency_contact' => '082222222222',
            'pay' => 'QRIS',
            'agree_terms' => '1',
        ], $override);
    }

    public function test_valid_voucher_applied_and_tracked(): void
    {
        $cat = $this->category();
        $runner = $this->runner();
        $disc = GtrDiscount::create(['name' => 'Promo', 'code' => 'RUNGER25', 'type' => 'fixed', 'value' => 25000, 'quota' => 1]);

        $this->actingAs($runner, 'runner')
            ->post(route('gtr.register.submit', $cat), $this->form(['discount_code' => 'runger25']))
            ->assertRedirect();

        $reg = GtrRegistration::first();
        $this->assertSame($disc->id, $reg->gtr_discount_id);
        $this->assertSame('RUNGER25', $reg->discount_code);
        $this->assertSame(25000, $reg->discount_amount);
        // Harga bayar = 100.000 − 25.000 = 75.000.
        $this->assertSame(75000, $reg->baseAmount());

        // Kuota berkurang + tertrack sebagai pemakai.
        $this->assertSame(1, $disc->fresh()->used_count);
        $this->assertSame(0, $disc->fresh()->remaining());
        $this->assertTrue($disc->registrations()->whereKey($reg->id)->exists());
    }

    public function test_exhausted_voucher_rejected(): void
    {
        $cat = $this->category();
        $disc = GtrDiscount::create(['name' => 'Promo', 'code' => 'HABIS', 'type' => 'fixed', 'value' => 25000, 'quota' => 1, 'used_count' => 1]);

        $this->actingAs($this->runner(), 'runner')
            ->post(route('gtr.register.submit', $cat), $this->form(['discount_code' => 'HABIS']))
            ->assertSessionHasErrors('discount_code');

        $this->assertSame(0, GtrRegistration::count(), 'pendaftaran tak dibuat saat voucher invalid');
    }

    public function test_quota_returned_when_cancelled_and_reconsumed_when_reactivated(): void
    {
        $cat = $this->category();
        $disc = GtrDiscount::create(['name' => 'Promo', 'code' => 'BALIK', 'type' => 'fixed', 'value' => 25000, 'quota' => 1]);

        $this->actingAs($this->runner(), 'runner')
            ->post(route('gtr.register.submit', $cat), $this->form(['discount_code' => 'BALIK']))
            ->assertRedirect();

        $reg = GtrRegistration::first();
        $this->assertSame(1, $disc->fresh()->used_count);
        $this->assertSame(0, $disc->fresh()->remaining());

        // Dibatalkan → kuota kembali.
        $reg->update(['payment_status' => 'cancelled']);
        $this->assertSame(0, $disc->fresh()->used_count);
        $this->assertSame(1, $disc->fresh()->remaining());
        $this->assertFalse($reg->fresh()->discount_consumed);

        // Diaktifkan lagi (mis. bayar ulang) → kuota terpakai lagi.
        $reg->update(['payment_status' => 'paid']);
        $this->assertSame(1, $disc->fresh()->used_count);
        $this->assertTrue($reg->fresh()->discount_consumed);

        // Cancel lalu cancel lagi tidak dobel-kembalikan.
        $reg->update(['payment_status' => 'cancelled']);
        $reg->update(['payment_status' => 'cancelled']);
        $this->assertSame(0, $disc->fresh()->used_count);
    }

    public function test_quota_returned_when_registration_deleted(): void
    {
        $cat = $this->category();
        $disc = GtrDiscount::create(['name' => 'Promo', 'code' => 'HAPUS', 'type' => 'fixed', 'value' => 25000, 'quota' => 2]);

        $this->actingAs($this->runner(), 'runner')
            ->post(route('gtr.register.submit', $cat), $this->form(['discount_code' => 'HAPUS']))
            ->assertRedirect();

        $this->assertSame(1, $disc->fresh()->used_count);
        GtrRegistration::first()->delete();
        $this->assertSame(0, $disc->fresh()->used_count);
    }

    public function test_registration_without_voucher_still_works(): void
    {
        $cat = $this->category();

        $this->actingAs($this->runner(), 'runner')
            ->post(route('gtr.register.submit', $cat), $this->form())
            ->assertRedirect();

        $reg = GtrRegistration::first();
        $this->assertNull($reg->gtr_discount_id);
        $this->assertSame(0, (int) $reg->discount_amount);
        $this->assertSame(100000, $reg->baseAmount());
    }
}
