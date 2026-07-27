<?php

namespace Webkul\Shop\Mail\Checkout;

use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Webkul\Shop\Mail\Mailable;

class GuestEmailOtp extends Mailable
{
    /**
     * Create a new mailable instance.
     */
    public function __construct(
        public string $toEmail,
        public string $code,
        public int $expiryMinutes,
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            to: [
                new Address($this->toEmail),
            ],
            subject: trans('shop::app.guest_email_verification.mail.subject'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'shop::emails.checkout.guest-otp',
        );
    }
}
