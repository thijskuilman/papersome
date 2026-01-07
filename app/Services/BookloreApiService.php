<?php

namespace App\Services;

use App\Settings\ApplicationSettings;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class BookloreApiService
{
    public function __construct(private readonly ApplicationSettings $settings) {}

    /**
     * Get a valid access token (reuse, refresh, or login)
     *
     * @throws Exception
     */
    public function login(string $username, string $password, ?string $url = null): string
    {
        if (
            $username === $this->settings->booklore_username &&
            $this->settings->booklore_access_token &&
            $this->settings->booklore_access_token_expires_at?->isFuture()
        ) {
            return $this->settings->booklore_access_token;
        }

        if ($this->settings->booklore_refresh_token) {
            try {
                return $this->refreshToken($url);
            } catch (Exception $e) {
                $this->clearTokens();
            }
        }

        if (! $username || ! $password) {
            throw new Exception('Booklore credentials not set in settings.');
        }

        $response = Http::post($url . '/api/v1/auth/login', [
            'username' => $username,
            'password' => $password,
        ]);

        if (! $response->successful()) {
            throw new Exception('Failed to log in to Booklore: ' . $response->body());
        }

        $this->settings->booklore_username = $username;
        $this->settings->booklore_url = $url;
        $this->settings->save();

        $data = $response->json();

        $this->storeTokens($data);

        return $data['accessToken'];
    }

    /**
     * Refresh JWT token using refresh token
     *
     * @throws Exception
     */
    public function refreshToken(?string $url = null): string
    {
        $refreshToken = $this->settings->booklore_refresh_token;

        if (! $refreshToken) {
            throw new Exception('No refresh token available.');
        }

        $response = Http::post($url . '/api/v1/auth/refresh', [
            'refreshToken' => $refreshToken,
        ]);

        if (! $response->successful()) {
            $this->clearTokens();
            throw new Exception('Failed to refresh Booklore token: ' . $response->body());
        }

        $data = $response->json();

        $this->storeTokens($data);

        return $data['accessToken'];
    }

    /**
     * Store access & refresh tokens and compute expiry from JWT
     */
    private function storeTokens(array $data): void
    {
        $this->settings->booklore_access_token = $data['accessToken'];
        $this->settings->booklore_refresh_token = $data['refreshToken'] ?? $this->settings->booklore_refresh_token;
        $this->settings->booklore_access_token_expires_at = $this->getJwtExpiry($data['accessToken']);
        $this->settings->save();
    }

    /**
     * Clear all tokens
     */
    private function clearTokens(): void
    {
        $this->settings->booklore_access_token = null;
        $this->settings->booklore_refresh_token = null;
        $this->settings->booklore_access_token_expires_at = null;
        $this->settings->save();
    }

    /**
     * Decode JWT and return expiry as Carbon
     */
    private function getJwtExpiry(string $jwt): ?Carbon
    {
        $parts = explode('.', $jwt);

        if (count($parts) !== 3) {
            return null;
        }

        $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);

        return isset($payload['exp']) ? Carbon::createFromTimestamp($payload['exp']) : null;
    }

    /**
     * Send a request with auto-refresh on 401
     *
     * @throws Exception
     */
    private function request(string $method, string $url, array $options = []): Response
    {
        $accessToken = $this->settings->booklore_access_token;
        $client = Http::withToken($accessToken)->acceptJson();

        $response = $client->send($method, $url, $options);

        // Auto-refresh if 401
        if ($response->status() === 401) {
            try {
                $newToken = $this->refreshToken($this->settings->booklore_url);

                $client = Http::withToken($newToken)->acceptJson();
                $response = $client->send($method, $url, $options);
            } catch (Exception $e) {
                return $response;
            }
        }

        return $response;
    }

    public function disconnect(): void
    {
        $this->clearTokens();
        $this->settings->booklore_url = null;
        $this->settings->booklore_username = null;
        $this->settings->save();
    }

    /**
     * Fetch libraries from Booklore
     *
     * @throws Exception
     */
    public function getLibraries(): array
    {
        $response = $this->request('GET', $this->settings->booklore_url . '/api/v1/libraries');

        if (! $response->successful()) {
            throw new Exception('Failed to fetch libraries: ' . $response->body());
        }

        return $response->json();
    }
}
