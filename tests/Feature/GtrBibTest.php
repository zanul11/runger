<?php

namespace Tests\Feature;

use App\Models\GtrCategory;
use App\Models\GtrRegistration;
use App\Models\GtrSetting;
use App\Models\Runner;
use App\Models\User;
use App\Support\GtrBibPdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GtrBibTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create(['name' => 'Admin', 'email' => 'a@t.id', 'password' => 'secret', 'role' => User::ROLE_ADMIN]);
    }

    private function seedRunners(): void
    {
        GtrSetting::current()->update(['title' => 'Gerung Trail Run 2026', 'race_contact' => '+62 859-3660-7558']);
        $ep = array_map(fn ($i) => [$i * 0.15, 100 + 40 * sin($i / 8)], range(0, 100));
        $c15 = GtrCategory::create(['name' => '15K', 'slug' => 'gtr-15k', 'distance' => '15 K', 'tag' => 'Race Trail', 'color' => '#E53935', 'is_active' => true, 'sort_order' => 2,
            'elevation_profile' => $ep, 'water_stations' => [['type' => 'cp', 'km' => 5.6, 'name' => 'CP1'], ['type' => 'cop', 'km' => 8, 'name' => 'COP']]]);
        $c7 = GtrCategory::create(['name' => '7K', 'slug' => 'gtr-7k', 'distance' => '7 KM', 'color' => '#35E656', 'is_active' => true, 'sort_order' => 1]);
        foreach ([[$c7, '7001', 'paid'], [$c7, '7002', 'paid'], [$c15, '5001', 'paid'], [$c15, null, 'pending']] as $i => [$c, $bib, $st]) {
            $runner = Runner::create(['first_name' => 'Pelari' . $i, 'email' => "r{$i}@t.id", 'password' => 'x']);
            GtrRegistration::create([
                'runner_id' => $runner->id, 'gtr_category_id' => $c->id, 'full_name' => 'Peserta ' . $i, 'gender' => 'Perempuan',
                'bib_number' => $bib, 'payment_status' => $st, 'blood_type' => 'AB', 'emergency_name' => 'Ibu', 'emergency_contact' => '6281234567890',
            ]);
        }
    }

    private static function pages(string $pdf): int
    {
        return preg_match_all('#/Type\s*/Page[^s]#', $pdf);
    }

    public function test_admin_can_print_bibs_per_category_sample_and_single(): void
    {
        $this->seedRunners();
        $admin = $this->actingAs($this->admin());

        $all = $admin->get(route('gtr.bib.pdf', ['category' => 'all']))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertSame(3, self::pages($all->getContent()), 'hanya peserta lunas ber-BIB');

        $c7 = GtrCategory::where('slug', 'gtr-7k')->first();
        $this->assertSame(2, self::pages($admin->get(route('gtr.bib.pdf', ['category' => $c7->id]))->getContent()));
        $this->assertSame(2, self::pages($admin->get(route('gtr.bib.pdf', ['category' => 'all', 'sample' => 1]))->getContent()), '1 contoh per kategori');
        $this->assertSame(1, self::pages($admin->get(route('gtr.bib.pdf', ['bib' => '5001']))->getContent()));
        $admin->get(route('gtr.bib.pdf', ['bib' => '9999']))->assertNotFound();
    }

    public function test_bib_printing_is_admin_only(): void
    {
        $marshal = User::create(['name' => 'M', 'email' => 'm@t.id', 'password' => 'secret', 'role' => User::ROLE_MARSHAL]);

        $this->get(route('gtr.bib.pdf'))->assertRedirect();
        $this->actingAs($marshal)->get(route('gtr.bib.pdf'))->assertForbidden();
    }

    public function test_admin_page_renders(): void
    {
        $this->seedRunners();
        $this->actingAs($this->admin())->get('/admin/gtr-bib-print')->assertOk()->assertSee('Cetak BIB')->assertSee('15 K · Race Trail')->assertSee('flip=1', false);
    }

    public function test_phone_and_strip_helpers(): void
    {
        $this->assertSame('0812-3456-7890', GtrBibPdf::phone('6281234567890'));
        $this->assertSame('0859-3660-7558', GtrBibPdf::phone('0859 3660 7558 (Panitia GTR)'));
        $this->assertNull(GtrBibPdf::phone(''));

        $this->seedRunners();
        $c15 = GtrCategory::where('slug', 'gtr-15k')->first();
        $flipped = GtrBibPdf::panel($c15, true);
        $upright = GtrBibPdf::panel($c15, false);
        $this->assertFileExists($flipped);
        $this->assertNotSame($flipped, $upright);
        [$w, $h] = getimagesize($flipped);
        // 210 × 56 mm pada 300 dpi
        $this->assertEqualsWithDelta(2480, $w, 2);
        $this->assertEqualsWithDelta(661, $h, 2);

        // Warna terang (hijau 7K) digelapkan untuk cetak; merah 15K tetap terbaca.
        $this->assertNotSame('#35E656', GtrBibPdf::theme(GtrCategory::where('slug', 'gtr-7k')->first())['ink']);
    }
}
