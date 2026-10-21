<?php

namespace App\Mail;

use App\Models\GtrRegistration;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NameChanged extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public GtrRegistration $registration, public ?string $oldName = null)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Perubahan Nama Peserta — ' . $this->registration->nomor_registrasi,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.name-changed',
        );
    }
}
