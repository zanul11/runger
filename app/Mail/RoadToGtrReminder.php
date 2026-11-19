<?php

namespace App\Mail;

use App\Models\GtrRegistration;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** H-1 Road to GTR: pengingat untuk yang sudah daftar, ajakan daftar untuk yang belum. */
class RoadToGtrReminder extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public GtrRegistration $registration,
        public bool $registered,
        public int $remaining,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Besok! Road to GTR — Minggu, 15 November 2026, 06:00 WITA 🌱',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.road-to-gtr-reminder',
        );
    }
}
