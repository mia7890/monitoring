<?php

namespace App\Services;

use App\Models\FaeUser;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class GoogleService
{
    public const GOOGLE_AUTH_ENDPOINT = 'https://accounts.google.com/o/oauth2/v2/auth';
    public const GOOGLE_TOKEN_ENDPOINT = 'https://oauth2.googleapis.com/token';
    public const GOOGLE_USERINFO_ENDPOINT = 'https://www.googleapis.com/oauth2/v3/userinfo';
    public const GOOGLE_CALENDAR_ENDPOINT = 'https://www.googleapis.com/calendar/v3/calendars/primary/events';
    public const GOOGLE_GMAIL_ENDPOINT = 'https://gmail.googleapis.com/gmail/v1/users/me/messages/send';

    public static function getClientId(): string
    {
        $settingVal = Setting::get('google_client_id');
        if (is_string($settingVal) && trim($settingVal) !== '') {
            return trim($settingVal);
        }

        return (string) config('services.google.client_id', env('GOOGLE_CLIENT_ID', ''));
    }

    public static function getClientSecret(): string
    {
        $settingVal = Setting::get('google_client_secret');
        if (is_string($settingVal) && trim($settingVal) !== '') {
            return trim($settingVal);
        }

        return (string) config('services.google.client_secret', env('GOOGLE_CLIENT_SECRET', ''));
    }

    public static function getRedirectUri(): string
    {
        $settingVal = Setting::get('google_redirect_uri');
        if (is_string($settingVal) && trim($settingVal) !== '') {
            return trim($settingVal);
        }

        if (!app()->runningInConsole() && request()->hasHeader('Host')) {
            return request()->schemeAndHttpHost() . '/auth/google/callback';
        }

        $envVal = config('services.google.redirect_uri', env('GOOGLE_REDIRECT_URI'));
        if (is_string($envVal) && trim($envVal) !== '') {
            return trim($envVal);
        }

        return route('google.callback', [], true);
    }



    public static function isConfigured(): bool
    {
        return self::getClientId() !== '' && self::getClientSecret() !== '';
    }

    /**
     * Generate the Google OAuth authorization URL.
     */
    public static function getAuthUrl(string $mode = 'login', array $extra = []): string
    {
        $statePayload = array_merge([
            'mode' => $mode,
            'csrf' => csrf_token(),
            'ts' => time(),
        ], $extra);

        $state = base64_encode(json_encode($statePayload));
        Session::put('google_oauth_state', $state);

        $scopes = [
            'openid',
            'https://www.googleapis.com/auth/userinfo.email',
            'https://www.googleapis.com/auth/userinfo.profile',
            'https://www.googleapis.com/auth/calendar.events',
            'https://www.googleapis.com/auth/gmail.send',
            'https://www.googleapis.com/auth/drive.file',
        ];


        $params = [
            'client_id' => self::getClientId(),
            'redirect_uri' => self::getRedirectUri(),
            'response_type' => 'code',
            'scope' => implode(' ', $scopes),
            'access_type' => 'offline',
            'prompt' => 'consent',
            'include_granted_scopes' => 'true',
            'state' => $state,
        ];

        return self::GOOGLE_AUTH_ENDPOINT . '?' . http_build_query($params);
    }

    /**
     * Exchange authorization code for OAuth tokens.
     */
    public static function exchangeCode(string $code): ?array
    {
        try {
            $response = Http::asForm()->post(self::GOOGLE_TOKEN_ENDPOINT, [
                'code' => $code,
                'client_id' => self::getClientId(),
                'client_secret' => self::getClientSecret(),
                'redirect_uri' => self::getRedirectUri(),
                'grant_type' => 'authorization_code',
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Google OAuth token exchange failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return null;
        } catch (\Throwable $e) {
            Log::error('Google OAuth token exchange exception: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Refresh an expired access token using a refresh token.
     */
    public static function refreshAccessToken(string $refreshToken): ?array
    {
        try {
            $response = Http::asForm()->post(self::GOOGLE_TOKEN_ENDPOINT, [
                'client_id' => self::getClientId(),
                'client_secret' => self::getClientSecret(),
                'refresh_token' => $refreshToken,
                'grant_type' => 'refresh_token',
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Google OAuth token refresh failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return null;
        } catch (\Throwable $e) {
            Log::error('Google OAuth token refresh exception: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Fetch user profile info with access token.
     */
    public static function getUserInfo(string $accessToken): ?array
    {
        try {
            $response = Http::withToken($accessToken)->get(self::GOOGLE_USERINFO_ENDPOINT);
            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Google Userinfo request failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return null;
        } catch (\Throwable $e) {
            Log::error('Google Userinfo exception: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Ensures an access token is valid, refreshing if needed.
     */
    public static function getValidAccessToken(
        ?string $accessToken,
        ?string $refreshToken,
        ?string $expiresAt,
        ?callable $onRefresh = null
    ): ?string {
        if (!$accessToken && !$refreshToken) {
            return null;
        }

        $isExpired = false;
        if ($expiresAt) {
            $expiry = Carbon::parse($expiresAt);
            if (Carbon::now()->addSeconds(60)->greaterThanOrEqualTo($expiry)) {
                $isExpired = true;
            }
        }

        if (!$isExpired && $accessToken) {
            return $accessToken;
        }

        if ($refreshToken) {
            $refreshed = self::refreshAccessToken($refreshToken);
            if ($refreshed && !empty($refreshed['access_token'])) {
                $newAccessToken = $refreshed['access_token'];
                $newExpiresIn = (int) ($refreshed['expires_in'] ?? 3600);
                $newExpiresAt = Carbon::now()->addSeconds($newExpiresIn)->toDateTimeString();

                if ($onRefresh) {
                    $onRefresh($newAccessToken, $newExpiresAt);
                }

                return $newAccessToken;
            }
        }

        return $accessToken;
    }

    /**
     * Sync event to Google Calendar API.
     */
    public static function syncCalendarEvent(
        string $accessToken,
        ?string $refreshToken,
        ?string $expiresAt,
        array $eventData,
        ?callable $onRefresh = null
    ): array {
        $validToken = self::getValidAccessToken($accessToken, $refreshToken, $expiresAt, $onRefresh);
        if (!$validToken) {
            return ['success' => false, 'error' => 'No valid Google access token available.'];
        }

        try {
            $payload = [
                'summary' => $eventData['title'] ?? 'Monitoring Task / Event',
                'description' => $eventData['description'] ?? '',
            ];

            if (!empty($eventData['location'])) {
                $payload['location'] = $eventData['location'];
            }

            if (!empty($eventData['start_datetime']) && !empty($eventData['end_datetime'])) {
                $payload['start'] = [
                    'dateTime' => Carbon::parse($eventData['start_datetime'])->toRfc3339String(),
                    'timeZone' => config('app.timezone', 'Asia/Manila'),
                ];
                $payload['end'] = [
                    'dateTime' => Carbon::parse($eventData['end_datetime'])->toRfc3339String(),
                    'timeZone' => config('app.timezone', 'Asia/Manila'),
                ];
            } elseif (!empty($eventData['date'])) {
                $dateStr = Carbon::parse($eventData['date'])->format('Y-m-d');
                $endDateStr = !empty($eventData['end_date'])
                    ? Carbon::parse($eventData['end_date'])->addDay()->format('Y-m-d')
                    : Carbon::parse($eventData['date'])->addDay()->format('Y-m-d');

                $payload['start'] = ['date' => $dateStr];
                $payload['end'] = ['date' => $endDateStr];
            }

            $response = Http::withToken($validToken)->timeout(30)->post(self::GOOGLE_CALENDAR_ENDPOINT, $payload);

            if ($response->successful()) {
                $json = $response->json();
                return [
                    'success' => true,
                    'event_id' => $json['id'] ?? null,
                    'html_link' => $json['htmlLink'] ?? null,
                ];
            }

            Log::error('Google Calendar event creation failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return [
                'success' => false,
                'error' => 'Google Calendar API error: ' . ($response->json()['error']['message'] ?? $response->body()),
            ];
        } catch (\Throwable $e) {
            Log::error('Google Calendar event exception: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Send email using Gmail API.
     */
    public static function sendGmailMessage(
        string $accessToken,
        ?string $refreshToken,
        ?string $expiresAt,
        string $to,
        string $subject,
        string $bodyHtml,
        ?callable $onRefresh = null
    ): array {
        $validToken = self::getValidAccessToken($accessToken, $refreshToken, $expiresAt, $onRefresh);
        if (!$validToken) {
            return ['success' => false, 'error' => 'No valid Google access token available.'];
        }

        try {
            $rawMessage  = "To: {$to}\r\n";
            $rawMessage .= "Subject: =?utf-8?B?" . base64_encode($subject) . "?=\r\n";
            $rawMessage .= "MIME-Version: 1.0\r\n";
            $rawMessage .= "Content-Type: text/html; charset=utf-8\r\n";
            $rawMessage .= "Content-Transfer-Encoding: base64\r\n\r\n";
            $rawMessage .= chunk_split(base64_encode($bodyHtml));

            $encodedRaw = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($rawMessage));

            $response = Http::withToken($validToken)->timeout(30)->post(self::GOOGLE_GMAIL_ENDPOINT, [
                'raw' => $encodedRaw,
            ]);

            if ($response->successful()) {
                $json = $response->json();
                return [
                    'success' => true,
                    'message_id' => $json['id'] ?? null,
                ];
            }

            Log::error('Gmail API send failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return [
                'success' => false,
                'error' => 'Gmail API error: ' . ($response->json()['error']['message'] ?? $response->body()),
            ];
        } catch (\Throwable $e) {
            Log::error('Gmail API send exception: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Save Admin Google connection.
     */
    public static function saveAdminGoogleConnection(array $userInfo, array $tokens): void
    {
        Setting::set('admin_google_id', $userInfo['sub'] ?? '');
        Setting::set('admin_google_email', $userInfo['email'] ?? '');
        Setting::set('admin_google_name', $userInfo['name'] ?? '');
        Setting::set('admin_google_avatar', $userInfo['picture'] ?? '');

        if (!empty($tokens['access_token'])) {
            Setting::set('admin_google_access_token', $tokens['access_token']);
        }
        if (!empty($tokens['refresh_token'])) {
            Setting::set('admin_google_refresh_token', $tokens['refresh_token']);
        }

        $expiresIn = (int) ($tokens['expires_in'] ?? 3600);
        Setting::set('admin_google_token_expires_at', Carbon::now()->addSeconds($expiresIn)->toDateTimeString());
    }

    /**
     * Disconnect Admin Google account.
     */
    public static function disconnectAdmin(): void
    {
        Setting::set('admin_google_id', null);
        Setting::set('admin_google_email', null);
        Setting::set('admin_google_name', null);
        Setting::set('admin_google_avatar', null);
        Setting::set('admin_google_access_token', null);
        Setting::set('admin_google_refresh_token', null);
        Setting::set('admin_google_token_expires_at', null);
    }

    /**
     * Save FAE User Google connection.
     */
    public static function saveFaeGoogleConnection(int $faeId, array $userInfo, array $tokens): bool
    {
        $fae = FaeUser::find($faeId);
        if (!$fae) {
            return false;
        }

        $expiresIn = (int) ($tokens['expires_in'] ?? 3600);
        $expiresAt = Carbon::now()->addSeconds($expiresIn)->toDateTimeString();

        $updateData = [
            'google_id' => $userInfo['sub'] ?? null,
            'google_email' => $userInfo['email'] ?? null,
            'google_avatar' => $userInfo['picture'] ?? null,
            'google_access_token' => $tokens['access_token'] ?? $fae->google_access_token,
            'google_token_expires_at' => $expiresAt,
        ];

        if (!empty($tokens['refresh_token'])) {
            $updateData['google_refresh_token'] = $tokens['refresh_token'];
        }

        if (empty($fae->email) && !empty($userInfo['email'])) {
            $updateData['email'] = $userInfo['email'];
        }

        return $fae->update($updateData);
    }

    /**
     * Disconnect FAE User Google account.
     */
    public static function disconnectFae(int $faeId): bool
    {
        $fae = FaeUser::find($faeId);
        if (!$fae) {
            return false;
        }

        return $fae->update([
            'google_id' => null,
            'google_email' => null,
            'google_avatar' => null,
            'google_access_token' => null,
            'google_refresh_token' => null,
            'google_token_expires_at' => null,
        ]);
    }

    /**
     * Upload file to Google Drive.
     */
    public static function uploadFileToDrive(
        string $accessToken,
        ?string $refreshToken,
        ?string $expiresAt,
        string $fileContents,
        string $filename,
        string $mimeType = 'application/octet-stream',
        ?callable $onRefresh = null,
        ?string $description = null,
        ?string $targetMimeType = null
    ): array {
        $validToken = self::getValidAccessToken($accessToken, $refreshToken, $expiresAt, $onRefresh);
        if (!$validToken) {
            return ['success' => false, 'error' => 'No valid Google access token available.'];
        }

        try {
            $boundary = '-------314159265358979323846';
            $delimiter = "\r\n--" . $boundary . "\r\n";
            $closeDelimiter = "\r\n--" . $boundary . "--";

            $metadata = [
                'name' => $filename,
                'mimeType' => $targetMimeType ?: $mimeType,
            ];
            if (!empty($description)) {
                $metadata['description'] = $description;
            }

            $multipartBody = $delimiter .
                "Content-Type: application/json; charset=UTF-8\r\n\r\n" .
                json_encode($metadata) .
                $delimiter .
                "Content-Type: {$mimeType}\r\n" .
                "Content-Transfer-Encoding: base64\r\n\r\n" .
                base64_encode($fileContents) .
                $closeDelimiter;

            $response = Http::withToken($validToken)
                ->timeout(60)
                ->connectTimeout(15)
                ->withHeaders([
                    'Content-Type' => 'multipart/related; boundary=' . $boundary,
                ])
                ->withBody($multipartBody, 'multipart/related; boundary=' . $boundary)
                ->post('https://www.googleapis.com/upload/drive/v3/files?uploadType=multipart&fields=id,name,description,webViewLink');

            if ($response->successful()) {
                $json = $response->json();
                return [
                    'success' => true,
                    'file_id' => $json['id'] ?? null,
                    'web_view_link' => $json['webViewLink'] ?? ('https://drive.google.com/file/d/' . ($json['id'] ?? '') . '/view'),
                ];
            }

            Log::error('Google Drive file upload failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return [
                'success' => false,
                'error' => 'Google Drive upload error: ' . ($response->json()['error']['message'] ?? $response->body()),
            ];
        } catch (\Throwable $e) {
            Log::error('Google Drive upload exception: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}

