<?php

namespace App\Mail;

use App\Models\GtrRegistration;
use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentReminder extends Mailable
{
    use Queueable, SerializesModels;

    /** $deadline = tanggal tutup pendaftaran; bila diisi, email menonjolkan tenggat pelunasan. */
    public function __construct(public GtrRegistration $registration, public ?CarbonInterface $deadline = null)
    {
    }

    public function envelope(): Envelope
    {
        $subject = $this->deadline
            ? 'Pendaftaran GTR 2026 ditutup ' . $this->deadline->copy()->locale('id')->translatedFormat('d F Y') . ' — lunasi sekarang — ' . $this->registration->nomor_registrasi
            : 'Satu langkah lagi: lunasi & buka semua fitur peserta GTR 2026 — ' . $this->registration->nomor_registrasi;

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.payment-reminder',
        );
    }
}
