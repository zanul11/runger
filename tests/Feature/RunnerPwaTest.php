<?php

namespace Tests\Feature;

use App\Models\Runner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** PWA Runger App: manifest + ikon logo GTR, dan ajakan "Pasang aplikasi". */
class RunnerPwaTest extends TestCase
{
    use RefreshDatabase;

    public function test_manifest_icons_exist(): void
    {
        $m = json_decode(file_get_contents(public_path('manifest.webmanifest')), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame('/runger-app', $m['start_url']);
        $this->assertSame('standalone', $m['display']);
        $purposes = array_column($m['icons'], 'purpose');
        $this->assertContains('any', $purposes);
        $this->assertContains('maskable', $purposes);
        foreach ($m['icons'] as $icon) {
            $this->assertFileExists(public_path(ltrim($icon['src'], '/')));
            [$w, $h] = getimagesize(public_path(ltrim($icon['src'], '/')));
            $this->assertSame($icon['sizes'], "{$w}x{$h}");
        }
        $this->assertFileExists(public_path('pwa/runger-app-apple-touch.png'));
    }

    public function test_login_and_app_pages_offer_install(): void
    {
        $this->get(route('gtr.login'))->assertOk()
            ->assertSee('rel="manifest"', false)
            ->assertSee('/pwa/runger-app-apple-touch.png')
            ->assertSee('beforeinstallprompt', false)
            ->assertSee('Pasang Runger App di HP');

        $runner = Runner::create(['first_name' => 'Rina', 'email' => 'rina@t.id', 'password' => 'x']);
        foreach (['gtr.dashboard', 'gtr.account.profile'] as $route) {
            $this->actingAs($runner, 'runner')->get(route($route))->assertOk()
                ->assertSee('rel="manifest"', false)
                ->assertSee('data-pwa-install', false);
        }
    }
}
