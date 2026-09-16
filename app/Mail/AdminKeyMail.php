<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AdminKeyMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $adminKey;

    public function __construct(string $adminKey)
    {
        $this->adminKey = $adminKey;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Monitoring System Admin Key',
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: $this->buildHtml(),
        );
    }

    private function buildHtml(): string
    {
        $key = e($this->adminKey);

        return <<<HTML
        <div style="font-family: 'Inter', Arial, sans-serif; max-width: 480px; margin: 0 auto; padding: 32px; background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0;">
            <div style="text-align: center; margin-bottom: 24px;">
                <h2 style="color: #0f172a; font-size: 20px; font-weight: 800; margin: 0 0 6px 0;">Hytec Power Inc.</h2>
                <p style="color: #b52f32; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; margin: 0;">Monitoring System</p>
            </div>

            <p style="color: #334155; font-size: 14px; line-height: 1.6; margin: 0 0 20px 0;">
                You requested your admin access key. Here it is:
            </p>

            <div style="background: #f8fafc; border: 2px solid #e2e8f0; border-radius: 8px; padding: 16px; text-align: center; margin-bottom: 20px;">
                <code style="font-size: 20px; font-weight: 800; color: #0f172a; letter-spacing: 1px;">{$key}</code>
            </div>

            <p style="color: #64748b; font-size: 12px; line-height: 1.5; margin: 0;">
                For security, do not share this key. If you did not request this, you can safely ignore this email.
            </p>

            <hr style="border: none; border-top: 1px solid #e2e8f0; margin: 24px 0 16px 0;">
            <p style="color: #94a3b8; font-size: 10px; text-align: center; margin: 0;">
                Hytec Power Inc. — Monitoring System
            </p>
        </div>
        HTML;
    }
}
