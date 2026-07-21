<?php

namespace Tests\Feature;

use App\Filament\Resources\GtrRegistrations\Pages\CreateGtrRegistration;
use App\Mail\RegistrationConfirmation;
use App\Models\GtrCategory;
use App\Models\GtrRegistration;
use App\Models\Runner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class GtrManualRegistrationTest extends TestCase
{
    use RefreshDatabase;

    private function actingAdmin(): void
    {
        $this->actingAs(User::create([
            'name' => 'Admin', 'email' => 'admin@t.id', 'password' => 'secret', 'role' => User::ROLE_ADMIN,
        ]));
    }

    private function fields(GtrCategory $cat, string $status = 'paid', string $email = 'peserta@t.id'): array
    {
        return [
            'gtr_category_id' => $cat->id,
            'payment_status' => $status,
            'full_name' => 'Budi Santoso',
            'email' => $email,
            'password' => 'rahasia6',
            'whatsapp' => '081234567890',
            'gender' => 'Laki-laki',
            'size' => 'L',
        ];
    }

    public function test_admin_creates_participant_with_account_bib_and_email_when_paid(): void
    {
        Mail::fake();
        $this->actingAdmin();
        $cat = GtrCategory::create(['name' => '7K', 'slug' => '7k', 'distance' => '7 KM', 'bib_prefix' => '7']);

        Livewire::test(CreateGtrRegistration::class)
            ->fillForm($this->fields($cat, 'paid'))
            ->call('create')
            ->assertHasNoFormErrors();

        // Akun peserta dibuat + password ter-hash.
        $runner = Runner::where('email', 'peserta@t.id')->first();
        $this->assertNotNull($runner);
        $this->assertTrue(Hash::check('rahasia6', $runner->password));
        $this->assertSame('male', $runner->gender);

        // Pendaftaran + nomor registrasi + BIB (karena paid).
        $reg = GtrRegistration::where('runner_id', $runner->id)->first();
        $this->assertNotNull($reg);
        $this->assertNotEmpty($reg->nomor_registrasi);
        $this->assertSame('7001', $reg->bib_number);
        $this->assertNotNull($reg->paid_at);

        // Email konfirmasi terkirim.
        Mail::assertSent(RegistrationConfirmation::class);
    }

    public function test_pending_registration_has_no_bib_and_no_email(): void
    {
        Mail::fake();
        $this->actingAdmin();
        $cat = GtrCategory::create(['name' => '7K', 'slug' => '7k', 'distance' => '7 KM', 'bib_prefix' => '7']);

        Livewire::test(CreateGtrRegistration::class)
            ->fillForm($this->fields($cat, 'pending'))
            ->call('create')
            ->assertHasNoFormErrors();

        $reg = GtrRegistration::first();
        $this->assertNull($reg->bib_number);
        Mail::assertNothingSent();
    }

    public function test_service_fee_only_applies_to_qris(): void
    {
        $cat = GtrCategory::create(['name' => '7K', 'slug' => '7k', 'distance' => '7 KM', 'price_normal' => 100000]);
        $runner = Runner::create(['first_name' => 'X', 'email' => 'x@t.id', 'password' => 'x']);

        $qris = GtrRegistration::create(['runner_id' => $runner->id, 'gtr_category_id' => $cat->id, 'pay' => 'QRIS']);
        $cash = new GtrRegistration(['pay' => 'Cash']);
        $cash->setRelation('category', $cat);

        $this->assertSame(GtrRegistration::ADMIN_FEE, $qris->serviceFee());
        $this->assertSame(0, $cash->serviceFee());

        // Total: QRIS = base + fee; Cash = base saja.
        $this->assertSame(100000 + GtrRegistration::ADMIN_FEE, $qris->totalAmount());
        $this->assertSame(100000, $cash->totalAmount());
    }

    public function test_existing_runner_is_reused(): void
    {
        Mail::fake();
        $this->actingAdmin();
        $cat = GtrCategory::create(['name' => '7K', 'slug' => '7k', 'distance' => '7 KM', 'bib_prefix' => '7']);
        $runner = Runner::create(['first_name' => 'Lama', 'email' => 'peserta@t.id', 'password' => 'old']);

        Livewire::test(CreateGtrRegistration::class)
            ->fillForm($this->fields($cat, 'pending'))
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame(1, Runner::where('email', 'peserta@t.id')->count());
        $this->assertSame($runner->id, GtrRegistration::first()->runner_id);
    }
}
