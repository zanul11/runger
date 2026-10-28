<?php

namespace Tests\Feature;

use App\Models\GtrDocument;
use App\Models\User;
use App\Support\GtrDocumentPdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class GtrDocumentTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create(['name' => 'Admin', 'email' => 'a@t.id', 'password' => 'secret', 'role' => User::ROLE_ADMIN]);
    }

    private static function pageCount(string $pdf): int
    {
        return preg_match_all('#/Type\s*/Page[^s]#', $pdf);
    }

    public static function docs(): array
    {
        return [
            'waiver id' => ['waiver', 'id'], 'waiver en' => ['waiver', 'en'],
            'par-q id' => ['par-q', 'id'], 'par-q en' => ['par-q', 'en'],
            'surat kuasa id' => ['surat-kuasa', 'id'], 'surat kuasa en' => ['surat-kuasa', 'en'],
        ];
    }

    #[DataProvider('docs')]
    public function test_default_documents_render_to_a_single_a4_page(string $slug, string $lang): void
    {
        $pdf = GtrDocumentPdf::render(GtrDocument::where('slug', $slug)->firstOrFail(), $lang);

        $this->assertStringStartsWith('%PDF-', $pdf);
        $this->assertSame(1, self::pageCount($pdf), "{$slug} ({$lang}) harus 1 halaman");
    }

    public function test_preview_is_admin_only(): void
    {
        $doc = GtrDocument::where('slug', 'waiver')->first();
        $marshal = User::create(['name' => 'M', 'email' => 'm@t.id', 'password' => 'secret', 'role' => User::ROLE_MARSHAL]);

        $this->get(route('gtr.docs.preview', [$doc, 'id']))->assertRedirect();
        $this->actingAs($marshal)->get(route('gtr.docs.preview', [$doc, 'id']))->assertForbidden();
        $this->actingAs($this->admin())->get(route('gtr.docs.preview', [$doc, 'en']))
            ->assertOk()->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_publish_shows_menu_and_download_then_unpublish_hides_it(): void
    {
        Storage::fake('public');
        $doc = GtrDocument::where('slug', 'par-q')->first();

        $this->get(route('gtr'))->assertOk()->assertDontSee(route('gtr.doc', ['par-q', 'id']));
        $this->get(route('gtr.doc', ['par-q', 'id']))->assertNotFound();

        GtrDocumentPdf::publish($doc);
        $doc->refresh();
        $this->assertTrue($doc->is_published);
        $this->assertFalse($doc->hasUnpublishedChanges());
        Storage::disk('public')->assertExists([$doc->pdf_id, $doc->pdf_en]);

        $this->get(route('gtr'))->assertOk()
            ->assertSee('Download PAR-Q')
            ->assertSee(route('gtr.doc', ['par-q', 'id']))
            ->assertSee(route('gtr.doc', ['par-q', 'en']));
        $this->get(route('gtr.doc', ['par-q', 'id']))->assertOk()
            ->assertDownload('GTR 2026 - Surat Screening Kesehatan - PAR-Q (Indonesia).pdf');
        $this->get(route('gtr.doc', ['par-q', 'en']))->assertOk()
            ->assertDownload('GTR 2026 - Health Screening - PAR-Q Form (English).pdf');

        // Edit setelah publikasi → ditandai "ada perubahan"; publikasi ulang mengganti file lama.
        $this->travel(1)->minutes();
        $doc->update(['nav_label' => 'PAR-Q Form']);
        $this->assertTrue($doc->fresh()->hasUnpublishedChanges());
        $old = $doc->pdf_id;
        $this->travel(1)->minutes();
        GtrDocumentPdf::publish($doc->fresh());
        Storage::disk('public')->assertMissing($old);
        $this->assertFalse($doc->fresh()->hasUnpublishedChanges());

        GtrDocumentPdf::unpublish($doc->fresh());
        $this->get(route('gtr.doc', ['par-q', 'id']))->assertNotFound();
        $this->get(route('gtr'))->assertDontSee('Download PAR-Q');
    }

    public function test_admin_pages_render(): void
    {
        $admin = $this->admin();
        $doc = GtrDocument::where('slug', 'waiver')->first();

        $this->actingAs($admin)->get('/admin/gtr-documents')->assertOk()->assertSee('Surat Kuasa');
        $this->actingAs($admin)->get("/admin/gtr-documents/{$doc->id}/edit")->assertOk();
        $this->actingAs($admin)->get('/admin/gtr-documents/create')->assertOk();
    }

    public function test_editor_html_is_converted_for_dompdf(): void
    {
        $html = '<h2>A. Data</h2><p>Nama Lengkap ___</p><p>Pernah sakit? [ ] Ya [ ] Tidak</p>'
            . '<p>[ ] Satu [ ] Dua [ ] Tiga</p><ol><li><p>Poin</p></li></ol><ul><li><p>Butir</p></li></ul>'
            . '<blockquote><h3>Kotak</h3><p>[ttd]</p></blockquote>'
            . '<div class="grid-layout" data-cols="2"><div class="grid-layout-col" data-col-span="1"><p>Kiri</p></div><div class="grid-layout-col" data-col-span="1"><p>Kanan</p></div></div>'
            . '<script>alert(1)</script>';

        $out = \App\Support\GtrDocumentHtml::toPdf($html, 'id');

        $this->assertStringContainsString('<div class="h">A. Data</div>', $out);
        $this->assertStringContainsString('<td class="lb">Nama Lengkap</td><td class="ln">', $out);
        $this->assertStringContainsString('<table class="yn">', $out);
        $this->assertStringContainsString('<table class="og">', $out);
        $this->assertStringContainsString('<div class="num">1</div>', $out);
        $this->assertStringContainsString('<div class="dot"></div>', $out);
        $this->assertStringContainsString('<div class="box"><div class="h3">Kotak</div><div class="sb"></div></div>', $out);
        $this->assertSame(2, substr_count($out, '<td class="c"'));
        $this->assertStringNotContainsString('script', $out);
    }

    public function test_inline_markup_becomes_lines_and_checkboxes(): void
    {
        $html = (string) GtrDocumentPdf::inline('Nomor BIB ____ Kategori [ ] 7K [] 15K <b>');

        $this->assertStringContainsString('class="bl"', $html);
        $this->assertSame(2, substr_count($html, 'class="cb"'));
        $this->assertStringContainsString('&lt;b&gt;', $html);
    }
}
