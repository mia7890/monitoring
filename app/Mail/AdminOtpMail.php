<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AdminOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $otp;

    public function __construct(string $otp)
    {
        $this->otp = $otp;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Admin Verification Code: ' . $this->otp,
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: $this->buildHtml(),
        );
    }

    public function buildHtml(): string
    {
        $code = e($this->otp);

        return <<<HTML
        <div style="font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; max-width: 480px; margin: 0 auto; padding: 32px; background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 2px 8px rgba(0,0,0,0.04);">
            <div style="text-align: center; margin-bottom: 24px;">
                <h2 style="color: #0f172a; font-size: 20px; font-weight: 800; margin: 0 0 6px 0;">Hytec Power Inc.</h2>
                <p style="color: #b52f32; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; margin: 0;">Monitoring System &bull; Admin Security</p>
            </div>

            <p style="color: #334155; font-size: 14px; line-height: 1.6; margin: 0 0 16px 0;">
                A sign-in request was initiated for the <strong>Administrator Workspace</strong>. Use the following 6-digit verification code to complete your login:
            </p>

            <div style="background: #f8fafc; border: 2px dashed #cbd5e1; border-radius: 10px; padding: 20px; text-align: center; margin: 20px 0;">
                <span style="font-size: 32px; font-weight: 800; color: #b52f32; letter-spacing: 6px; font-family: monospace;">{$code}</span>
            </div>

            <p style="color: #64748b; font-size: 12px; line-height: 1.5; margin: 0 0 8px 0;">
                ⏱ This code is valid for <strong>10 minutes</strong>.
            </p>
            <p style="color: #64748b; font-size: 12px; line-height: 1.5; margin: 0;">
                If you did not attempt to sign in to the administrator workspace, please verify your credentials immediately.
            </p>

            <hr style="border: none; border-top: 1px solid #e2e8f0; margin: 24px 0 16px 0;">
            <p style="color: #94a3b8; font-size: 10px; text-align: center; margin: 0;">
                Hytec Power Inc. — Monitoring System &copy; 2026
            </p>
        </div>
        HTML;
    }
}
