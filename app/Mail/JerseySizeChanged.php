<?php

namespace App\Mail;

use App\Models\GtrRegistration;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class JerseySizeChanged extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public GtrRegistration $registration, public ?string $oldSize = null)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Perubahan Ukuran Jersey — ' . $this->registration->nomor_registrasi,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.jersey-size-changed',
        );
    }
}
