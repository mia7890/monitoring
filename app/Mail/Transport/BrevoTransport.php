<?php

namespace App\Mail\Transport;

use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\MessageConverter;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;

/**
 * Brevo HTTP API Transport
 *
 * Sends transactional emails via Brevo's REST API (https://api.brevo.com/v3/smtp/email)
 * instead of SMTP, so it works on platforms like Railway that block outbound SMTP ports.
 */
class BrevoTransport extends AbstractTransport
{
    private string $apiKey;

    public function __construct(
        string $apiKey,
        ?EventDispatcherInterface $dispatcher = null,
        ?LoggerInterface $logger = null
    ) {
        parent::__construct($dispatcher, $logger);
        $this->apiKey = $apiKey;
    }

    protected function doSend(SentMessage $message): void
    {
        $email = MessageConverter::toEmail($message->getOriginalMessage());
        $envelope = $message->getEnvelope();

        $payload = $this->buildPayload($email, $envelope);

        $ch = curl_init('https://api.brevo.com/v3/smtp/email');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTPHEADER     => [
                'Accept: application/json',
                'Content-Type: application/json',
                'api-key: ' . $this->apiKey,
            ],
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            throw new \RuntimeException('Brevo API cURL error: ' . $curlError);
        }

        if ($httpCode < 200 || $httpCode >= 300) {
            $body = json_decode($response, true);
            $errorMsg = $body['message'] ?? $response;
            throw new \RuntimeException("Brevo API error (HTTP {$httpCode}): {$errorMsg}");
        }
    }

    private function buildPayload(Email $email, Envelope $envelope): array
    {
        $sender = $envelope->getSender();

        $payload = [
            'sender' => [
                'name'  => $sender->getName() ?: config('mail.from.name', 'Monitoring System'),
                'email' => $sender->getAddress(),
            ],
            'subject' => $email->getSubject() ?? '(No Subject)',
        ];

        // To recipients
        $payload['to'] = array_map(
            fn(Address $addr) => array_filter([
                'email' => $addr->getAddress(),
                'name'  => $addr->getName() ?: null,
            ]),
            $this->getRecipients($email->getTo())
        );

        // CC recipients
        if ($cc = $email->getCc()) {
            $payload['cc'] = array_map(
                fn(Address $addr) => array_filter([
                    'email' => $addr->getAddress(),
                    'name'  => $addr->getName() ?: null,
                ]),
                $this->getRecipients($cc)
            );
        }

        // BCC recipients
        if ($bcc = $email->getBcc()) {
            $payload['bcc'] = array_map(
                fn(Address $addr) => array_filter([
                    'email' => $addr->getAddress(),
                    'name'  => $addr->getName() ?: null,
                ]),
                $this->getRecipients($bcc)
            );
        }

        // HTML content
        if ($html = $email->getHtmlBody()) {
            $payload['htmlContent'] = $html;
        }

        // Text content
        if ($text = $email->getTextBody()) {
            $payload['textContent'] = $text;
        }

        // If neither HTML nor text, provide a fallback
        if (!isset($payload['htmlContent']) && !isset($payload['textContent'])) {
            $payload['textContent'] = '(empty)';
        }

        return $payload;
    }

    /**
     * Ensure we return a flat array of Address objects.
     */
    private function getRecipients(array $addresses): array
    {
        return array_values($addresses);
    }

    public function __toString(): string
    {
        return 'brevo+api://api.brevo.com';
    }
}
