<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TestSmtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $recipientEmail;

    public function __construct(string $recipientEmail)
    {
        $this->recipientEmail = $recipientEmail;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Test Email from Monitoring System (SMTP)',
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
        $recipient = e($this->recipientEmail);
        $timeStr = e(now()->toDayDateTimeString());

        return <<<HTML
        <div style="font-family: 'Inter', Arial, sans-serif; max-width: 520px; margin: 0 auto; padding: 32px; background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0;">
            <div style="text-align: center; margin-bottom: 24px;">
                <h2 style="color: #0f172a; font-size: 20px; font-weight: 800; margin: 0 0 6px 0;">Hytec Power Inc.</h2>
                <p style="color: #b52f32; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; margin: 0;">Monitoring System &bull; SMTP Test</p>
            </div>

            <p style="color: #334155; font-size: 14px; line-height: 1.6; margin: 0 0 16px 0;">
                Hello,
            </p>

            <p style="color: #334155; font-size: 14px; line-height: 1.6; margin: 0 0 20px 0;">
                This test message confirms that your SMTP mail server configuration is active and delivering emails successfully to <strong>{$recipient}</strong>.
            </p>

            <div style="background: #f8fafc; border-left: 4px solid #10b981; border-radius: 6px; padding: 14px; margin-bottom: 20px; font-size: 13px; color: #334155;">
                <strong>Status:</strong> SMTP Mail Verified &amp; Active<br>
                <strong>Timestamp:</strong> {$timeStr}
            </div>

            <hr style="border: none; border-top: 1px solid #e2e8f0; margin: 24px 0 16px 0;">
            <p style="color: #94a3b8; font-size: 10px; text-align: center; margin: 0;">
                Hytec Power Inc. &mdash; Monitoring System
            </p>
        </div>
        HTML;
    }
}
