<?php

namespace Tests\Feature;

use App\Mail\PaymentConfirmation;
use App\Models\GtrCategory;
use App\Models\GtrRegistration;
use App\Models\GtrSetting;
use App\Models\Runner;
use App\Support\RaceDayCard;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/** QR e-ticket dibuat di server (bisa offline) + kartu Race Day di Runger App. */
class RunnerRaceDayCardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.gtr.race_date' => '2026-11-29']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function reg(array $attrs = []): GtrRegistration
    {
        $runner = Runner::create(['first_name' => 'Rina', 'email' => 'rina' . uniqid() . '@t.id', 'password' => 'x']);
        $cat = GtrCategory::firstOrCreate(['slug' => '7k'], ['name' => '7K', 'distance' => '7 KM', 'price_normal' => 100000, 'start_time' => '06:00 WITA']);

        return GtrRegistration::create(array_merge([
            'runner_id' => $runner->id, 'gtr_category_id' => $cat->id,
            'full_name' => 'Rina', 'pay' => 'QRIS', 'payment_status' => 'pending',
        ], $attrs))->fresh('category');
    }

    private function setting(): GtrSetting
    {
        return tap(GtrSetting::current())->update([
            'racepack_starts_on' => '2026-11-27',
            'racepack_ends_on' => '2026-11-28',
            'racepack_schedule' => 'Jumat-Sabtu, 27-28 November 2026',
            'racepack_location' => 'Gelora Mini Soccer Gerung',
            'assembly_time' => '05.15 WITA',
        ])->fresh();
    }

    private function decode(string $png): string
    {
        $file = tempnam(sys_get_temp_dir(), 'qr');
        file_put_contents($file, $png);

        return (new QRCode(new QROptions(['readerUseImagickIfAvailable' => false])))->readFromFile($file)->data;
    }

    public function test_qr_is_generated_locally_and_contains_registration_number(): void
    {
        $reg = $this->reg();

        $this->assertSame($reg->nomor_registrasi, $this->decode($reg->qrPng()));
        $this->assertStringStartsWith('data:image/svg+xml;base64,', $reg->qrDataUri());
    }

    public function test_race_detail_and_email_do_not_use_external_qr_service(): void
    {
        $reg = $this->reg();
        $reg->update(['payment_status' => 'paid']);
        $reg->refresh();

        $this->actingAs($reg->runner, 'runner')->get(route('gtr.account.race.detail', $reg))
            ->assertOk()
            ->assertSee('data:image/svg+xml;base64,', false)
            ->assertDontSee('qrserver');

        $html = (new PaymentConfirmation($reg))->render();
        $this->assertStringNotContainsString('qrserver', $html);
        $this->assertStringContainsString('data:image/png;base64,', $html); // cid: diganti data URI saat render pratinjau
    }

    public function test_card_modes_follow_dates(): void
    {
        $s = $this->setting();
        $paid = $this->reg(['payment_status' => 'paid']);
        $at = fn (string $d) => RaceDayCard::for($paid, $s, Carbon::parse($d . ' 09:00', 'Asia/Makassar'));

        $this->assertNull($at('2026-11-25'));
        $this->assertSame(['mode' => 'racepack', 'when' => 'tomorrow'], $at('2026-11-26'));
        $this->assertSame(['mode' => 'racepack', 'when' => 'today'], $at('2026-11-27'));
        $this->assertSame(['mode' => 'racepack', 'when' => 'last'], $at('2026-11-28'));
        $this->assertSame(['mode' => 'race', 'when' => 'today'], $at('2026-11-29'));
        $this->assertNull($at('2026-11-30'));

        // Sudah ambil race pack → H-1 jadi "Besok race day", sebelum itu tidak ada kartu.
        $paid->forceFill(['racepack_collected_at' => now()])->save();
        $this->assertNull($at('2026-11-27'));
        $this->assertSame(['mode' => 'race', 'when' => 'tomorrow'], $at('2026-11-28'));

        // Belum lunas → tidak pernah tampil.
        $this->assertNull(RaceDayCard::for($this->reg(), $s, Carbon::parse('2026-11-29 06:00', 'Asia/Makassar')));
    }

    public function test_card_shown_on_home_and_detail_during_racepack(): void
    {
        $this->setting();
        $reg = $this->reg();
        $reg->update(['payment_status' => 'paid']);
        $reg->refresh();
        Carbon::setTestNow(Carbon::parse('2026-11-28 10:00', 'Asia/Makassar'));

        $this->actingAs($reg->runner, 'runner')->get(route('gtr.dashboard'))
            ->assertOk()
            ->assertSee('Hari terakhir ambil race pack!')
            ->assertSee(route('gtr.account.race.detail', $reg));

        $this->actingAs($reg->runner, 'runner')->get(route('gtr.account.race.detail', $reg))
            ->assertOk()
            ->assertSee('Hari terakhir ambil race pack!')
            ->assertSee('Gelora Mini Soccer Gerung')
            ->assertDontSee('class="rn-btn red" data-open-qr', false); // QR sudah di kartu atas, tidak dobel di Race Pack Collection
    }

    public function test_race_day_card_warns_when_racepack_not_collected(): void
    {
        $this->setting();
        $reg = $this->reg();
        $reg->update(['payment_status' => 'paid']);
        $reg->refresh();
        Carbon::setTestNow(Carbon::parse('2026-11-29 04:30', 'Asia/Makassar'));

        $this->actingAs($reg->runner, 'runner')->get(route('gtr.account.race.detail', $reg))
            ->assertOk()
            ->assertSee('Hari ini race day!')
            ->assertSee('05.15 WITA')
            ->assertSee('06:00 WITA')
            ->assertSee('belum tercatat diambil');
    }
}
