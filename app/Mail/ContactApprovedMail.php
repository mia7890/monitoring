<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactApprovedMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $contactName;
    public string $accessCode;

    public function __construct(string $contactName, string $accessCode)
    {
        $this->contactName = $contactName;
        $this->accessCode = $accessCode;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Registration Approved - Your Access Code for Monitoring System',
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
        $name = e($this->contactName);
        $code = e($this->accessCode);
        $accessUrl = e(route('access'));

        return <<<HTML
        <div style="font-family: 'Inter', Arial, sans-serif; max-width: 520px; margin: 0 auto; padding: 32px; background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0;">
            <div style="text-align: center; margin-bottom: 24px;">
                <h2 style="color: #0f172a; font-size: 20px; font-weight: 800; margin: 0 0 6px 0;">Hytec Power Inc.</h2>
                <p style="color: #b52f32; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; margin: 0;">Monitoring System &bull; Registration Approved</p>
            </div>

            <p style="color: #334155; font-size: 14px; line-height: 1.6; margin: 0 0 16px 0;">
                Hello <strong>{$name}</strong>,
            </p>

            <p style="color: #334155; font-size: 14px; line-height: 1.6; margin: 0 0 20px 0;">
                Your registration has been <strong>approved</strong> by the administrator. Here is your unique Access Code:
            </p>

            <div style="background: #f8fafc; border: 2px solid #22c55e; border-radius: 8px; padding: 18px; text-align: center; margin-bottom: 24px;">
                <span style="display: block; font-size: 11px; color: #15803d; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 6px;">Your Access Code</span>
                <code style="font-size: 24px; font-weight: 800; color: #0f172a; letter-spacing: 2px;">{$code}</code>
            </div>

            <p style="color: #334155; font-size: 14px; line-height: 1.6; margin: 0 0 24px 0;">
                You can now log in using this Access Code to view schedules, manage your profile, and book appointments directly with the Administrator.
            </p>

            <div style="text-align: center; margin-bottom: 24px;">
                <a href="{$accessUrl}" style="background: #2563eb; color: #ffffff; text-decoration: none; padding: 12px 24px; border-radius: 6px; font-weight: 700; font-size: 13px; display: inline-block;">
                    Log In &amp; Book Appointment &rarr;
                </a>
            </div>

            <hr style="border: none; border-top: 1px solid #e2e8f0; margin: 24px 0 16px 0;">
            <p style="color: #94a3b8; font-size: 10px; text-align: center; margin: 0;">
                Hytec Power Inc. &mdash; Monitoring System
            </p>
        </div>
        HTML;
    }
}
