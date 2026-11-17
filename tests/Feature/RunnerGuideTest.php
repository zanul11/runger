<?php

namespace Tests\Feature;

use App\Models\GtrDocument;
use App\Models\GtrSetting;
use App\Models\Runner;
use App\Support\GtrDocumentPdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Menu "Guide" di Runger App: race guide + dokumen peserta. */
class RunnerGuideTest extends TestCase
{
    use RefreshDatabase;

    private function runner(): Runner
    {
        return Runner::create(['first_name' => 'Rina', 'email' => 'rina@t.id', 'password' => 'x']);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('gtr.account.guide'))->assertRedirect(route('gtr.login'));
    }

    public function test_shows_coming_soon_when_nothing_published(): void
    {
        GtrDocument::query()->update(['is_published' => false]);
        $runner = $this->runner();

        $this->actingAs($runner, 'runner')->get(route('gtr.account.guide'))
            ->assertOk()
            ->assertSee('Segera Hadir')
            ->assertSee('Belum ada dokumen peserta')
            ->assertSee(route('gtr.account.guide')); // tab "Guide" di bottom nav

        $this->actingAs($runner, 'runner')->get(route('gtr.account.guide.view'))
            ->assertRedirect(route('gtr.account.guide'));
    }

    public function test_shows_published_race_guide_and_documents(): void
    {
        Storage::fake('public');
        $dir = 'gtr/race-guide/batchabc123';
        $files = [];
        foreach (['01-cover', '02-rpc'] as $name) {
            $files[] = UploadedFile::fake()->image($name . '.jpg', 1080, 1350)->storeAs($dir, $name . '.jpg', 'public');
        }
        GtrSetting::current()->update(['race_guide_slides' => $files, 'race_guide_published_at' => now()]);
        GtrDocumentPdf::publish(GtrDocument::where('slug', 'par-q')->first());

        $runner = $this->runner();

        // Menu: kartu ringkas + dokumen langsung terlihat, viewer PDF belum dimuat.
        $this->actingAs($runner, 'runner')->get(route('gtr.account.guide'))
            ->assertOk()
            ->assertSee('Official Race Guide')
            ->assertSee('2 halaman')
            ->assertSee(route('gtr.account.guide.view'))
            ->assertDontSee('pdf.min.js')
            ->assertDontSee('01-cover.jpg')
            ->assertSee(route('gtr.doc', ['par-q', 'id']))
            ->assertSee(route('gtr.doc', ['par-q', 'en']))
            ->assertDontSee(route('gtr.doc', ['waiver', 'id']));

        // Halaman baca: viewer PDF (dibuat otomatis dari slide) + cadangan gambar.
        $this->actingAs($runner, 'runner')->get(route('gtr.account.guide.view'))
            ->assertOk()
            ->assertSee(route('gtr.guide.pdf'))
            ->assertSee('data-src="/storage/' . $dir . '/gtr-race-guide.pdf', false)
            ->assertSee('01-cover.jpg');

        Storage::disk('public')->assertExists($dir . '/gtr-race-guide.pdf');
    }
}
