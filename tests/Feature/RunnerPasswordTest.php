<?php

namespace Tests\Feature;

use App\Filament\Resources\Runners\Pages\ListRunners;
use App\Filament\Resources\Runners\Pages\ViewRunner;
use App\Mail\RunnerPasswordReset;
use App\Models\Runner;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password as PasswordBroker;
use Livewire\Livewire;
use Tests\TestCase;

class RunnerPasswordTest extends TestCase
{
    use RefreshDatabase;

    private function runner(): Runner
    {
        return Runner::create(['first_name' => 'Budi', 'email' => 'budi@t.id', 'password' => 'Lama1234']);
    }

    public function test_profile_shows_change_password_form(): void
    {
        $this->actingAs($this->runner(), 'runner')
            ->get('/runger-app/profil')
            ->assertOk()
            ->assertSee('Ganti Password');
    }

    public function test_runner_can_change_password_from_profile(): void
    {
        $runner = $this->runner();

        $this->actingAs($runner, 'runner')
            ->put('/runger-app/profil/password', [
                'current_password' => 'Lama1234',
                'password' => 'Baru5678',
                'password_confirmation' => 'Baru5678',
            ])
            ->assertRedirect('/runger-app/profil')
            ->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('Baru5678', $runner->fresh()->password));
    }

    public function test_change_password_rejects_wrong_current_or_weak_password(): void
    {
        $runner = $this->runner();

        $this->actingAs($runner, 'runner')
            ->put('/runger-app/profil/password', [
                'current_password' => 'Salah1234',
                'password' => 'Baru5678',
                'password_confirmation' => 'Baru5678',
            ])
            ->assertSessionHasErrorsIn('password', ['current_password']);

        $this->actingAs($runner, 'runner')
            ->put('/runger-app/profil/password', [
                'current_password' => 'Lama1234',
                'password' => 'lemah',
                'password_confirmation' => 'lemah',
            ])
            ->assertSessionHasErrorsIn('password', ['password']);

        $this->assertTrue(Hash::check('Lama1234', $runner->fresh()->password));
    }

    public function test_forgot_password_sends_email_and_reset_works(): void
    {
        Mail::fake();
        $runner = $this->runner();

        $this->get('/runger-app/masuk')->assertSee('Lupa password?');
        $this->get('/runger-app/lupa-password')->assertOk();

        $this->post('/runger-app/lupa-password', ['email' => 'budi@t.id'])
            ->assertSessionHas('success');

        $token = null;
        Mail::assertSent(RunnerPasswordReset::class, function (RunnerPasswordReset $mail) use (&$token) {
            $token = $mail->token;

            return $mail->hasTo('budi@t.id');
        });

        $this->get('/runger-app/reset-password/' . $token . '?email=budi@t.id')->assertOk()->assertSee('budi@t.id');

        $this->post('/runger-app/reset-password', [
            'token' => $token,
            'email' => 'budi@t.id',
            'password' => 'Baru5678',
            'password_confirmation' => 'Baru5678',
        ])->assertRedirect('/runger-app/masuk');

        $this->assertTrue(Hash::check('Baru5678', $runner->fresh()->password));

        // Token hanya bisa dipakai sekali.
        $this->post('/runger-app/reset-password', [
            'token' => $token,
            'email' => 'budi@t.id',
            'password' => 'Lagi9999A',
            'password_confirmation' => 'Lagi9999A',
        ])->assertSessionHasErrors('email');
    }

    public function test_forgot_password_does_not_reveal_unknown_email(): void
    {
        Mail::fake();

        $this->post('/runger-app/lupa-password', ['email' => 'tidakada@t.id'])
            ->assertSessionHas('success')
            ->assertSessionHasNoErrors();

        Mail::assertNothingSent();
    }

    public function test_reset_email_renders(): void
    {
        $html = (new RunnerPasswordReset($this->runner(), 'abc'))->render();

        $this->assertStringContainsString('/runger-app/reset-password/abc', $html);
        $this->assertStringContainsString('Budi', $html);
    }

    public function test_admin_can_set_password_and_send_reset_link(): void
    {
        Mail::fake();
        $runner = $this->runner();
        $admin = User::create(['name' => 'Admin', 'email' => 'a@t.id', 'password' => 'secret', 'role' => User::ROLE_ADMIN]);
        $this->actingAs($admin);

        $this->get('/admin/runners/' . $runner->id)->assertOk();

        Livewire::test(ListRunners::class)
            ->callAction(TestAction::make('setPassword')->table($runner), [
                'password' => 'Admin1234',
                'password_confirmation' => 'Admin1234',
            ])
            ->assertHasNoFormErrors();

        $this->assertTrue(Hash::check('Admin1234', $runner->fresh()->password));

        Livewire::test(ListRunners::class)
            ->callAction(TestAction::make('setPassword')->table($runner), [
                'password' => 'lemah',
                'password_confirmation' => 'lemah',
            ])
            ->assertHasFormErrors(['password']);

        Livewire::test(ViewRunner::class, ['record' => $runner->getKey()])
            ->callAction('sendResetLink');

        Mail::assertSent(RunnerPasswordReset::class, fn ($mail) => $mail->hasTo('budi@t.id'));
        $this->assertDatabaseHas('password_reset_tokens', ['email' => 'budi@t.id']);
    }
}
