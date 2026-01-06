<?php

namespace App\Services;

use App\Settings\ApplicationSettings;
use Exception;
use Illuminate\Support\Facades\Http;

class BookloreService
{
    public function __construct(private readonly ApplicationSettings $settings) {}

    /**
     * @throws Exception
     */
    public function syncToBooklore(): void
    {
        dd($this->getAccessToken());
    }

    /**
     * Log in to Booklore and store new access & refresh tokens
     *
     * @throws Exception
     */
    public function getAccessToken(bool $storeToken = true, ?string $username = null, ?string $password = null, ?string $url = null): string
    {
        $username = $username ?? $this->settings->booklore_username;
        $password = $password ?? $this->settings->booklore_password;
        $url = $url ?? $this->settings->booklore_url;

        if (! $username || ! $password) {
            throw new Exception('Booklore credentials not set in settings.');
        }

        $response = Http::post($url.'/api/v1/auth/login', [
            'username' => $username,
            'password' => $password,
        ]);

        if (! $response->successful()) {
            throw new Exception('Failed to log in to Booklore: '.$response->body());
        }

        $data = $response->json();

        if($storeToken) {
            $this->settings->booklore_access_token = $data['accessToken'];
            $this->settings->booklore_refresh_token = $data['refreshToken'];
            $this->settings->booklore_access_token_expires_at = now()->addSeconds(3600);
            $this->settings->save();
        }

        return $data['accessToken'];
    }
}
