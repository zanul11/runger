<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\GtrEventSummary;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class GtrSummaryTest extends TestCase
{
    use RefreshDatabase;

    public static function addresses(): array
    {
        return [
            ['Dasan tapen gerung', 'Lombok Barat'],
            ['Jl Nusa Indah VI No 24A BTN Sweta', 'Kota Mataram'],
            ['pejeruk bangket ampenan', 'Kota Mataram'],
            ['Jl. Selaparang, Tanjung Karang', 'Kota Mataram'],
            ['Dusun Karang Pangsor, Pemenang Barat, Kabupaten Lombok Utara', 'Lombok Utara'],
            ['BTN Bermis, Kelurahan Leneng, Kecamatan Praya', 'Lombok Tengah'],
            ['Maluk, Sumbawa Barat', 'Sumbawa Barat'],
            ['Sumberkima, Kec Grogak, Kabupaten Buleleng, Bali', 'Bali'],
            ['1 bougainvillea drive, 0885 Alyangula, Australia', 'Luar negeri'],
            ['Kabulika', 'Lainnya'],
        ];
    }

    #[DataProvider('addresses')]
    public function test_region_from_free_text_address(string $address, string $region): void
    {
        $this->assertSame($region, GtrEventSummary::region($address));
    }

    public function test_summary_command_and_admin_page(): void
    {
        $this->artisan('gtr:summary')->expectsOutputToContain('RINGKASAN GTR 2026')->assertSuccessful();
        $admin = User::create(['name' => 'Admin', 'email' => 'a@t.id', 'password' => 'secret', 'role' => User::ROLE_ADMIN]);
        $this->actingAs($admin)->get('/admin/gtr-summary')->assertOk()->assertSee('Teks ringkasan');
    }
}
