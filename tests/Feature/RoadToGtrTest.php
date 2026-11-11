<?php

namespace Tests\Feature;

use App\Models\RoadToGtrRegistration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/** Form publik Road to GTR Fun Trail (15 Nov 2026). */
class RoadToGtrTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-11-11 10:00', 'Asia/Makassar'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function form(array $over = []): array
    {
        return array_merge(['name' => ' Baiq Nurul ', 'phone' => '+62 812-3456-7890', 'community' => 'Runners Gerung', 'address' => 'Gerung, Lombok Barat'], $over);
    }

    public function test_page_shows_form_and_gtr_home_links_to_it(): void
    {
        $this->get(route('gtr.road'))->assertOk()
            ->assertSee('Form Pendaftaran')->assertSee('Minggu, 15 November 2026')->assertSee('Tanam Pohon')
            ->assertSee(route('gtr.road.store'), false);

        $this->get(route('gtr'))->assertOk()->assertSee(route('gtr.road'), false);
    }

    public function test_registration_is_stored_with_normalized_phone(): void
    {
        $this->post(route('gtr.road.store'), $this->form())
            ->assertRedirect(route('gtr.road'))->assertSessionHas('success');

        $r = RoadToGtrRegistration::sole();
        $this->assertSame(['Baiq Nurul', '081234567890', 'Runners Gerung', 'Gerung, Lombok Barat'], [$r->name, $r->phone, $r->community, $r->address]);
        $this->assertSame('https://wa.me/6281234567890', $r->whatsapp_url);
        $this->assertSame('0812-3456-7890', $r->phone_formatted);
    }

    public function test_community_is_optional(): void
    {
        $this->post(route('gtr.road.store'), $this->form(['community' => '']))->assertSessionHasNoErrors();
        $this->assertNull(RoadToGtrRegistration::sole()->community);
    }

    public function test_required_fields_and_valid_phone(): void
    {
        $this->post(route('gtr.road.store'), ['name' => '', 'phone' => '12345', 'address' => ''])
            ->assertSessionHasErrors(['name', 'phone', 'address']);
        $this->assertSame(0, RoadToGtrRegistration::count());
    }

    public function test_same_phone_in_another_format_is_rejected(): void
    {
        $this->post(route('gtr.road.store'), $this->form())->assertSessionHasNoErrors();
        $this->post(route('gtr.road.store'), $this->form(['name' => 'Orang Lain', 'phone' => '0812 3456 7890']))
            ->assertSessionHasErrors(['phone' => 'No. HP ini sudah terdaftar di Road to GTR.']);
        $this->assertSame(1, RoadToGtrRegistration::count());
    }

    public function test_honeypot_submissions_are_dropped(): void
    {
        $this->post(route('gtr.road.store'), $this->form(['website' => 'http://spam.example']))->assertRedirect(route('gtr.road'));
        $this->assertSame(0, RoadToGtrRegistration::count());
    }

    public function test_form_closes_after_event_day(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-15 23:59', 'Asia/Makassar'));
        $this->assertTrue(RoadToGtrRegistration::isOpen());

        Carbon::setTestNow(Carbon::parse('2026-11-16 00:00', 'Asia/Makassar'));
        $this->get(route('gtr.road'))->assertOk()->assertSee('Pendaftaran ditutup')->assertDontSee('name="phone"', false);
        $this->get(route('gtr'))->assertOk()->assertDontSee(route('gtr.road'), false);
        $this->post(route('gtr.road.store'), $this->form())->assertForbidden();
        $this->assertSame(0, RoadToGtrRegistration::count());
    }

    /** Isi $n pendaftar langsung ke DB (nomor unik). */
    private function fill(int $n): void
    {
        $now = now();
        RoadToGtrRegistration::insert(array_map(fn ($i) => [
            'name' => 'P'.$i, 'phone' => '0811'.str_pad((string) $i, 8, '0', STR_PAD_LEFT), 'address' => 'X',
            'created_at' => $now, 'updated_at' => $now,
        ], range(1, $n)));
    }

    public function test_page_shows_remaining_quota(): void
    {
        $this->fill(85);

        $this->get(route('gtr.road'))->assertOk()
            ->assertSee('Sisa <b>15</b> slot', false)->assertSee('85/100 terdaftar')
            ->assertSee('name="phone"', false);
        $this->get(route('gtr'))->assertOk()->assertSee('sisa <b>15</b> slot', false);
    }

    public function test_last_slot_is_accepted_then_quota_is_full(): void
    {
        $this->fill(RoadToGtrRegistration::QUOTA - 1);

        $this->post(route('gtr.road.store'), $this->form())->assertSessionHasNoErrors()->assertSessionHas('success');
        $this->assertSame(RoadToGtrRegistration::QUOTA, RoadToGtrRegistration::count());

        $this->post(route('gtr.road.store'), $this->form(['phone' => '085700000000']))
            ->assertRedirect(route('gtr.road'))
            ->assertSessionHasErrors(['quota' => 'Maaf, kuota Road to GTR (100 orang) sudah penuh.']);
        $this->assertSame(RoadToGtrRegistration::QUOTA, RoadToGtrRegistration::count());

        $this->get(route('gtr.road'))->assertOk()->assertSee('Kuota penuh')->assertDontSee('name="phone"', false);
        // Banner promo hilang; link menu Race Info tetap (menuju layar "Kuota penuh").
        $this->get(route('gtr'))->assertOk()->assertDontSee('rtg-promo-card', false);
    }

    public function test_admin_list_page_renders(): void
    {
        RoadToGtrRegistration::create(['name' => 'A', 'phone' => '6281234567890', 'address' => 'X']);
        $this->assertSame('081234567890', RoadToGtrRegistration::sole()->phone);

        $admin = User::create(['name' => 'Admin', 'email' => 'a@t.id', 'password' => 'secret', 'role' => User::ROLE_ADMIN]);
        $this->actingAs($admin)
            ->get('/admin/road-to-gtr-registrations')->assertOk()->assertSee('0812-3456-7890');
    }
}
