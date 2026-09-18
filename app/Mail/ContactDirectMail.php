<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactDirectMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $contactName;
    public string $mailSubject;
    public string $mailMessage;

    public function __construct(string $contactName, string $mailSubject, string $mailMessage)
    {
        $this->contactName = $contactName;
        $this->mailSubject = $mailSubject;
        $this->mailMessage = $mailMessage;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->mailSubject,
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
        $subject = e($this->mailSubject);
        $messageHtml = nl2br(e($this->mailMessage));
        $portalUrl = e(route('access'));

        return <<<HTML
        <div style="font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; max-width: 540px; margin: 0 auto; padding: 32px 24px; background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; color: #1e293b;">
            <div style="text-align: center; margin-bottom: 24px; border-bottom: 1px solid #f1f5f9; padding-bottom: 16px;">
                <h2 style="color: #0f172a; font-size: 20px; font-weight: 800; margin: 0 0 6px 0;">Hytec Power Inc.</h2>
                <p style="color: #2563eb; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; margin: 0;">Monitoring &amp; Operations Notice</p>
            </div>

            <p style="color: #334155; font-size: 14px; line-height: 1.6; margin: 0 0 16px 0;">
                Hello <strong>{$name}</strong>,
            </p>

            <div style="background: #f8fafc; border-left: 4px solid #2563eb; border-radius: 4px; padding: 14px 16px; margin-bottom: 20px;">
                <span style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 4px;">Subject</span>
                <strong style="font-size: 14px; color: #0f172a;">{$subject}</strong>
            </div>

            <div style="color: #334155; font-size: 14px; line-height: 1.7; margin: 0 0 24px 0; background: #ffffff; padding: 12px 4px;">
                {$messageHtml}
            </div>

            <div style="text-align: center; margin-bottom: 24px;">
                <a href="{$portalUrl}" style="background: #2563eb; color: #ffffff; text-decoration: none; padding: 10px 20px; border-radius: 6px; font-weight: 700; font-size: 13px; display: inline-block;">
                    Access Monitoring Portal &rarr;
                </a>
            </div>

            <hr style="border: none; border-top: 1px solid #e2e8f0; margin: 24px 0 16px 0;">
            <p style="color: #94a3b8; font-size: 11px; text-align: center; margin: 0; line-height: 1.5;">
                This message was dispatched directly via the Hytec Power Monitoring System.<br>
                Please do not reply directly to this automated email address.
            </p>
        </div>
        HTML;
    }
}
