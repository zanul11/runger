<?php

namespace Tests\Feature;

use App\Models\GtrCategory;
use App\Models\GtrRegistration;
use App\Models\GtrSetting;
use App\Models\Runner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Runger App versi runner: tab Home · Race · Jadwal · Guide · Profil + lembar My QR. */
class RunnerAppNavTest extends TestCase
{
    use RefreshDatabase;

    private function paidRunner(): array
    {
        $cat = GtrCategory::create([
            'name' => 'Keteri', 'slug' => '7k', 'distance' => '7 KM', 'price_normal' => 100000, 'is_active' => true,
            'start_time' => '06:00 WITA', 'color' => '#E53935',
            'rundown' => [
                ['date' => '28 Nov 2026', 'time' => '19.00', 'activity' => 'Technical Meeting', 'location' => 'Aula Desa'],
                ['date' => '29 Nov 2026', 'time' => '05.15', 'activity' => 'Runner Assembly', 'location' => 'Venue'],
            ],
        ]);
        $runner = Runner::create(['first_name' => 'Rina', 'email' => 'rina@t.id', 'password' => 'x']);
        $reg = GtrRegistration::create(['runner_id' => $runner->id, 'gtr_category_id' => $cat->id, 'full_name' => 'Rina Putri', 'pay' => 'QRIS', 'payment_status' => 'paid']);

        return [$runner, $reg->fresh(), $cat];
    }

    public function test_bottom_nav_and_qr_sheet_for_paid_runner(): void
    {
        [$runner, $reg] = $this->paidRunner();

        $this->actingAs($runner, 'runner')->get(route('gtr.dashboard'))
            ->assertOk()
            ->assertSee(route('gtr.account.race'))
            ->assertSee(route('gtr.account.schedule'))
            ->assertSee(route('gtr.account.guide'))
            ->assertSee(route('gtr.account.profile'))
            ->assertDontSee('>Transaction<', false)
            ->assertSee('id="qr-sheet"', false)
            ->assertSee($reg->nomor_registrasi)
            ->assertSee('My QR');
    }

    public function test_race_tab_shows_runner_information_for_main_registration(): void
    {
        [$runner, $reg] = $this->paidRunner();
        GtrSetting::current()->update(['racepack_schedule' => 'Jumat-Sabtu, 27-28 November 2026', 'racepack_location' => 'Gelora Mini Soccer']);

        $this->actingAs($runner, 'runner')->get(route('gtr.account.race'))
            ->assertOk()
            ->assertSee('BIB ' . $reg->bib_number)
            ->assertSee('Race Guide')
            ->assertSee('data-tab="map"', false)
            ->assertSee('data-tab="gear"', false)
            ->assertSee('Race Pack Collection')
            ->assertSee('Jumat-Sabtu, 27-28 November 2026')
            ->assertSee('Gelora Mini Soccer');
    }

    public function test_schedule_tab_lists_rundown_per_day(): void
    {
        [$runner] = $this->paidRunner();
        GtrSetting::current()->update(['date_text' => 'Minggu, 29 November 2026']);

        $this->actingAs($runner, 'runner')->get(route('gtr.account.schedule'))
            ->assertOk()
            ->assertSeeInOrder(['NOVEMBER 2026', 'SAB', '28', 'MIN', '29', 'RACE'])
            ->assertSeeInOrder(['Sabtu, 28 November 2026', 'Technical Meeting', 'Aula Desa'])
            ->assertSee('Runner Assembly');
    }

    public function test_profile_links_transaction_history(): void
    {
        [$runner] = $this->paidRunner();

        $this->actingAs($runner, 'runner')->get(route('gtr.account.profile'))
            ->assertOk()->assertSee('Riwayat Transaksi')->assertSee(route('gtr.account.transaction'));

        $this->actingAs($runner, 'runner')->get(route('gtr.account.transaction'))
            ->assertOk()->assertSee('Riwayat Transaksi')->assertSee(route('gtr.account.profile'));
    }

    public function test_race_tab_empty_state_without_registration(): void
    {
        $runner = Runner::create(['first_name' => 'Budi', 'email' => 'budi@t.id', 'password' => 'x']);

        $this->actingAs($runner, 'runner')->get(route('gtr.account.race'))
            ->assertOk()->assertSee('belum terdaftar')->assertDontSee('id="qr-sheet"', false);
    }
}
