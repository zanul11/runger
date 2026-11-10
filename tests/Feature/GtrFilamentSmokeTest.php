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
            'scan logs' => ['/admin/gtr-scan-logs'],
            'sponsors' => ['/admin/gtr-sponsors'],
            'sponsor create' => ['/admin/gtr-sponsors/create'],
            'discounts' => ['/admin/gtr-discounts'],
            'discount create' => ['/admin/gtr-discounts/create'],
            'marshals' => ['/admin/marshals'],
            'marshal create' => ['/admin/marshals/create'],
            'results' => ['/admin/gtr-results'],
            'monitor' => ['/admin/gtr-monitor'],
            'payment report' => ['/admin/gtr-payment-report'],
            'email preview' => ['/admin/gtr-email-preview'],
            'registrations' => ['/admin/gtr-registrations'],
        ];
    }

    public function test_registration_view_with_scan_history_renders(): void
    {
        $this->seedMinimal();
        $reg = GtrRegistration::first();
        $tp = GtrTimingPoint::first();
        $marshal = User::create(['name' => 'Pak Marshal', 'email' => 'pm@t.id', 'password' => 'secret', 'role' => User::ROLE_MARSHAL]);
        \App\Models\GtrScanLog::create([
            'gtr_registration_id' => $reg->id, 'gtr_timing_point_id' => $tp->id, 'scanned_at' => now(),
            'client_uuid' => (string) \Illuminate\Support\Str::uuid(), 'scanned_by' => $marshal->id,
        ]);

        $admin = $this->admin();
        $this->actingAs($admin)->get("/admin/gtr-registrations/{$reg->id}")->assertOk();
        $this->actingAs($admin)->get('/admin/gtr-scan-logs')->assertOk()->assertSee('Pak Marshal');
    }

    public function test_category_edit_with_relation_manager_renders(): void
    {
        $this->seedMinimal();
        $cat = GtrCategory::first();

        $this->actingAs($this->admin())->get("/admin/gtr-categories/{$cat->id}/edit")->assertOk();
    }
}
