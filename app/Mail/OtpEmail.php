<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OtpEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $email,
        public int $otp,
        public string $purpose = 'login',
        public ?string $login_url = null,
    ) {
        $this->email = $email;
        $this->otp = $otp;
        $this->purpose = $purpose;
        $this->login_url = $login_url;
    }

    public function envelope(): Envelope
    {
        $subject = match ($this->purpose) {
            'registration' => 'Verify Your AfriQwest Account',
            'login' => 'Your AfriQwest Login Code',
            default => 'Your AfriQwest Verification Code',
        };

        return new Envelope(
            subject: $subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'otpMail',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
