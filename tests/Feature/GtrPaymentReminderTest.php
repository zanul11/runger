<?php

namespace Tests\Feature;

use App\Filament\Resources\GtrRegistrations\Pages\ListGtrRegistrations;
use App\Mail\PaymentReminder;
use App\Models\GtrCategory;
use App\Models\GtrRegistration;
use App\Models\Runner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class GtrPaymentReminderTest extends TestCase
{
    use RefreshDatabase;

    private function reg(GtrCategory $cat, string $status, ?string $email = null): GtrRegistration
    {
        $runner = Runner::create(['first_name' => 'R', 'email' => Str::uuid() . '@t.id', 'password' => 'x']);

        return GtrRegistration::create([
            'runner_id' => $runner->id, 'gtr_category_id' => $cat->id,
            'full_name' => 'Peserta', 'pay' => 'QRIS', 'payment_status' => $status, 'email' => $email,
        ]);
    }

    public function test_reminder_sent_to_pending_and_cancelled_with_email(): void
    {
        Mail::fake();
        $this->actingAs(User::create(['name' => 'A', 'email' => 'a@t.id', 'password' => 'x', 'role' => User::ROLE_ADMIN]));
        $cat = GtrCategory::create(['name' => '7K', 'slug' => '7k', 'distance' => '7 KM', 'price_normal' => 100000]);

        $this->reg($cat, 'pending', 'p1@t.id');
        $this->reg($cat, 'cancelled', 'c1@t.id');  // batal juga diingatkan
        $this->reg($cat, 'pending', null);          // tanpa email → dilewati
        $this->reg($cat, 'paid', 'paid@t.id');      // sudah lunas → tidak dikirim

        Livewire::test(ListGtrRegistrations::class)->callAction('reminderAll');

        Mail::assertSent(PaymentReminder::class, 2);
        Mail::assertSent(PaymentReminder::class, fn ($m) => $m->hasTo('p1@t.id'));
        Mail::assertSent(PaymentReminder::class, fn ($m) => $m->hasTo('c1@t.id'));
        Mail::assertNotSent(PaymentReminder::class, fn ($m) => $m->hasTo('paid@t.id'));
    }
}
