<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class PasswordChangedByAdminMail extends Mailable
{
    public function __construct(
        public readonly User $user,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Password Has Been Changed',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.password-changed-by-admin',
        );
    }
}
