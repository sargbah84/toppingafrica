<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\SearchConsoleData;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class GoogleSearchConsoleService
{
    private ?string $accessToken = null;

    public function __construct(
        private readonly string $clientId,
        private readonly string $clientSecret,
        private readonly string $refreshToken,
        private readonly string $siteUrl,
    ) {}

    public static function fromConfig(): ?self
    {
        $clientId = config('services.google.client_id');
        $clientSecret = config('services.google.client_secret');
        $refreshToken = config('services.google.search_console_refresh_token');
        $siteUrl = config('services.google.search_console_site_url');

        if (!$clientId || !$clientSecret || !$refreshToken || !$siteUrl) {
            return null;
        }

        return new self($clientId, $clientSecret, $refreshToken, $siteUrl);
    }

    public static function isConfigured(): bool
    {
        return !empty(config('services.google.client_id'))
            && !empty(config('services.google.client_secret'))
            && !empty(config('services.google.search_console_refresh_token'))
            && !empty(config('services.google.search_console_site_url'));
    }

    public static function hasCredentials(): bool
    {
        return !empty(config('services.google.client_id'))
            && !empty(config('services.google.client_secret'));
    }

    public static function needsConnection(): bool
    {
        return self::hasCredentials() && !self::isConfigured();
    }

    public static function getOAuthUrl(): string
    {
        $params = http_build_query([
            'client_id' => config('services.google.client_id'),
            'redirect_uri' => route('admin.settings.gsc-callback'),
            'response_type' => 'code',
            'scope' => 'https://www.googleapis.com/auth/webmasters.readonly',
            'access_type' => 'offline',
            'prompt' => 'consent',
        ]);

        return "https://accounts.google.com/o/oauth2/v2/auth?{$params}";
    }

    public static function exchangeCode(string $code): ?array
    {
        $response = Http::post('https://oauth2.googleapis.com/token', [
            'client_id' => config('services.google.client_id'),
            'client_secret' => config('services.google.client_secret'),
            'code' => $code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => route('admin.settings.gsc-callback'),
        ]);

        if ($response->successful()) {
            return $response->json();
        }

        Log::error('GSC token exchange failed', ['response' => $response->body()]);

        return null;
    }

    private function getAccessToken(): ?string
    {
        if ($this->accessToken) {
            return $this->accessToken;
        }

        $response = Http::post('https://oauth2.googleapis.com/token', [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'refresh_token' => $this->refreshToken,
            'grant_type' => 'refresh_token',
        ]);

        if ($response->successful()) {
            $this->accessToken = $response->json('access_token');

            return $this->accessToken;
        }

        Log::error('GSC access token refresh failed', ['response' => $response->body()]);

        return null;
    }

    public function fetchAndStore(int $days = 28): bool
    {
        $token = $this->getAccessToken();
        if (!$token) {
            return false;
        }

        $startDate = now()->subDays($days)->format('Y-m-d');
        $endDate = now()->subDay()->format('Y-m-d');

        $response = Http::withToken($token)
            ->post("https://www.googleapis.com/webmasters/v3/sites/{$this->encodedSiteUrl()}/searchAnalytics/query", [
                'startDate' => $startDate,
                'endDate' => $endDate,
                'dimensions' => ['query', 'page', 'date', 'device', 'country'],
                'rowLimit' => 5000,
            ]);

        if (!$response->successful()) {
            Log::error('GSC data fetch failed', ['status' => $response->status(), 'body' => $response->body()]);

            return false;
        }

        $rows = $response->json('rows', []);

        // Clear old data for this date range and insert fresh
        SearchConsoleData::where('date', '>=', $startDate)
            ->where('date', '<=', $endDate)
            ->delete();

        foreach (array_chunk($rows, 500) as $chunk) {
            $records = [];
            foreach ($chunk as $row) {
                $keys = $row['keys'] ?? [];
                $records[] = [
                    'query' => $keys[0] ?? null,
                    'page' => $keys[1] ?? null,
                    'date' => $keys[2] ?? $startDate,
                    'device' => $keys[3] ?? null,
                    'country' => $keys[4] ?? null,
                    'clicks' => (int) ($row['clicks'] ?? 0),
                    'impressions' => (int) ($row['impressions'] ?? 0),
                    'ctr' => (float) ($row['ctr'] ?? 0),
                    'position' => (float) ($row['position'] ?? 0),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            SearchConsoleData::insert($records);
        }

        Log::info('GSC data synced', ['rows' => count($rows), 'period' => "{$startDate} to {$endDate}"]);

        return true;
    }

    private function encodedSiteUrl(): string
    {
        return urlencode($this->siteUrl);
    }
}
