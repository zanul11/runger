<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\GtrCategory;
use App\Models\GtrRegistration;
use App\Models\GtrResult;
use App\Models\GtrTimingPoint;
use App\Models\Runner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class GtrFilamentSmokeTest extends TestCase
{
    use RefreshDatabase;

    private function seedMinimal(): void
    {
        Event::create(['slug' => 'gtr', 'title' => 'GTR 2026', 'date' => '2026-11-29', 'time' => '06:00']);
        $tp = GtrTimingPoint::create(['code' => 'CP1', 'name' => 'CP1', 'type' => 'checkpoint']);
        $cat = GtrCategory::create(['name' => '7K', 'slug' => '7k', 'distance' => '7 KM']);
        $cat->timingPoints()->attach($tp->id, ['sequence' => 1, 'is_mandatory' => true]);

        $runner = Runner::create(['first_name' => 'Budi', 'email' => 'b@t.id', 'password' => 'x']);
        $reg = GtrRegistration::create([
            'runner_id' => $runner->id, 'gtr_category_id' => $cat->id, 'full_name' => 'Budi', 'gender' => 'Laki-laki', 'bib_number' => '1',
        ]);
        GtrResult::create(['gtr_registration_id' => $reg->id, 'status' => 'finisher', 'net_time_seconds' => 3000, 'rank_overall' => 1]);
    }

    private function admin(): User
    {
        return User::create(['name' => 'Admin', 'email' => 'a@t.id', 'password' => 'secret', 'role' => User::ROLE_ADMIN]);
    }

    public function test_marshal_cannot_access_admin_panel(): void
    {
        $marshal = User::create(['name' => 'M', 'email' => 'm@t.id', 'password' => 'secret', 'role' => User::ROLE_MARSHAL]);

        $this->actingAs($marshal)->get('/admin')->assertForbidden();
    }

    #[DataProvider('adminPages')]
    public function test_admin_pages_render(string $path): void
    {
        $this->seedMinimal();
        $this->actingAs($this->admin())->get($path)->assertOk();
    }

    public static function adminPages(): array
    {
        return [
            'timing points' => ['/admin/gtr-timing-points'],
            'timing point create' => ['/admin/gtr-timing-points/create'],
            'sponsors' => ['/admin/gtr-sponsors'],
            'sponsor create' => ['/admin/gtr-sponsors/create'],
            'marshals' => ['/admin/marshals'],
            'marshal create' => ['/admin/marshals/create'],
            'results' => ['/admin/gtr-results'],
            'monitor' => ['/admin/gtr-monitor'],
            'payment report' => ['/admin/gtr-payment-report'],
            'registrations' => ['/admin/gtr-registrations'],
        ];
    }

    public function test_category_edit_with_relation_manager_renders(): void
    {
        $this->seedMinimal();
        $cat = GtrCategory::first();

        $this->actingAs($this->admin())->get("/admin/gtr-categories/{$cat->id}/edit")->assertOk();
    }
}
