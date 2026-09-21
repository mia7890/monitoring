<?php

namespace App\Services;

class SmtpConnectivity
{
    public static function failureReason(): ?string
    {
        if (config('mail.default') !== 'smtp' || app()->runningUnitTests()) {
            return null;
        }

        $host = (string) config('mail.mailers.smtp.host', 'smtp.resend.com');
        $port = (int) config('mail.mailers.smtp.port', 587);
        $encryption = (string) config('mail.mailers.smtp.encryption', 'tls');
        $scheme = $encryption === 'ssl' ? 'ssl://' : 'tcp://';
        $connection = @stream_socket_client(
            $scheme . $host . ':' . $port,
            $errno,
            $error,
            2
        );

        if ($connection === false) {
            return "SMTP server {$host}:{$port} is unreachable: {$error} ({$errno})";
        }

        fclose($connection);

        return null;
    }
}
