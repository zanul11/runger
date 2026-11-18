<?php

namespace Tests\Feature;

use App\Models\GtrCategory;
use App\Models\GtrRegistration;
use App\Models\GtrSetting;
use App\Models\Runner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Setelah registration_closes_at lewat: form, submit & bayar online terkunci di Runger App. */
class GtrRegistrationClosedTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_and_payment_blocked_after_close(): void
    {
        $cat = GtrCategory::create(['name' => '7K', 'slug' => '7k', 'distance' => '7 KM', 'price_normal' => 100000, 'is_active' => true]);
        $runner = Runner::create(['first_name' => 'Rina', 'email' => 'rina@t.id', 'password' => 'x']);
        GtrSetting::current()->update(['registration_closes_at' => now()->subMinute()]);

        $this->actingAs($runner, 'runner')->get(route('gtr.register.form', $cat))
            ->assertRedirect(route('gtr.dashboard'));

        $this->actingAs($runner, 'runner')->post(route('gtr.register.submit', $cat), ['full_name' => 'Rina', 'size' => 'M'])
            ->assertRedirect(route('gtr.dashboard'));
        $this->assertSame(0, GtrRegistration::count());

        $this->actingAs($runner, 'runner')->get(route('gtr.dashboard'))
            ->assertOk()->assertSee('Pendaftaran GTR 2026 sudah ditutup.')->assertDontSee(route('gtr.register.form', $cat));

        // Registrasi pending lama tidak bisa memulai pembayaran baru.
        $reg = GtrRegistration::create(['runner_id' => $runner->id, 'gtr_category_id' => $cat->id, 'full_name' => 'Rina', 'pay' => 'QRIS', 'payment_status' => 'pending']);
        $this->actingAs($runner, 'runner')->post(route('gtr.payment.pay', $reg))
            ->assertRedirect(route('gtr.account.transaction'));
    }

    public function test_home_puts_own_race_first_and_auth_pages_show_notice_when_closed(): void
    {
        $cat = GtrCategory::create(['name' => 'Keteri', 'slug' => '7k', 'distance' => '7 KM', 'price_normal' => 100000, 'is_active' => true, 'start_time' => '06:00 WITA']);
        $runner = Runner::create(['first_name' => 'Rina', 'email' => 'rina@t.id', 'password' => 'x']);
        GtrRegistration::create(['runner_id' => $runner->id, 'gtr_category_id' => $cat->id, 'full_name' => 'Rina', 'pay' => 'QRIS', 'payment_status' => 'paid']);

        // Masih buka & sudah lunas: kartu runner + quick access, tanpa daftar kategori berharga.
        $this->actingAs($runner, 'runner')->get(route('gtr.dashboard'))
            ->assertOk()
            ->assertSeeInOrder(['Rina', 'BIB', 'Start', 'Race pack belum diambil', 'Quick Access'])
            ->assertDontSee('Daftar Race');

        GtrSetting::current()->update(['registration_closes_at' => now()->subMinute()]);

        auth('runner')->logout();
        $this->get(route('gtr.login'))->assertOk()->assertSee('Pendaftaran GTR 2026 sudah ditutup.');
        $this->get(route('gtr.register'))->assertOk()->assertSee('Pendaftaran race GTR 2026 sudah ditutup.');
    }

    public function test_countdown_and_detail_use_category_start_and_settings(): void
    {
        config(['services.gtr.race_date' => '2026-11-29']);
        $cat = GtrCategory::create(['name' => 'Keteri', 'slug' => '7k', 'distance' => '7 KM', 'price_normal' => 100000, 'is_active' => true, 'start_time' => '06:00 WITA']);
        GtrCategory::create(['name' => 'Long', 'slug' => '15k', 'distance' => '15K', 'price_normal' => 100000, 'is_active' => true, 'start_time' => '06:30 WITA']);
        GtrSetting::current()->update(['location_text' => 'SD Negeri 4 Banyu Urip', 'date_text' => 'Minggu, 29 November 2026']);

        $start = \Illuminate\Support\Carbon::parse('2026-11-29 06:00', 'Asia/Makassar')->getTimestampMs();
        $this->assertSame($start, GtrCategory::raceStartAt()->getTimestampMs()); // paling awal
        $this->get(route('gtr'))->assertOk()->assertSee('window.GTR_RACE_START_MS = ' . $start, false);

        $runner = Runner::create(['first_name' => 'Rina', 'email' => 'rina@t.id', 'password' => 'x']);
        $reg = GtrRegistration::create(['runner_id' => $runner->id, 'gtr_category_id' => $cat->id, 'full_name' => 'Rina', 'pay' => 'QRIS', 'payment_status' => 'paid']);

        $this->actingAs($runner, 'runner')->get(route('gtr.dashboard'))->assertSee('var target = ' . $start, false);
        $this->actingAs($runner, 'runner')->get(route('gtr.account.race.detail', $reg))
            ->assertOk()
            ->assertSee('SD Negeri 4 Banyu Urip')
            ->assertSee('Start <b>06:00 WITA</b>', false)
            ->assertDontSee('Bukit Keteri');
    }

    public function test_open_when_close_time_empty(): void
    {
        GtrSetting::current()->update(['registration_closes_at' => null]);
        $this->assertFalse(GtrSetting::registrationClosed());
    }
}
