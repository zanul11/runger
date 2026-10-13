<?php

namespace App\Mail;

use App\Models\Runner;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RunnerPasswordReset extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Runner $runner, public string $token)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Reset Password Akun Peserta GTR',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.runner-password-reset',
            with: [
                'url' => route('gtr.password.reset', ['token' => $this->token, 'email' => $this->runner->email]),
                'expire' => config('auth.passwords.runners.expire'),
            ],
        );
    }
}
