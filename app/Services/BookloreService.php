<?php

namespace App\Services;

use App\Settings\ApplicationSettings;
use Exception;
use Illuminate\Support\Facades\Http;

class BookloreService
{

    public function __construct(private readonly ApplicationSettings $settings)
    {
    }

    /**
     * @throws Exception
     */
    public function syncToBooklore(): void
    {
        dd($this->loginAndStoreToken());
    }

    /**
     * Log in to Booklore and store new access & refresh tokens
     */
    private function loginAndStoreToken(): string
    {
        $username = $this->settings->booklore_username;
        $password = $this->settings->booklore_password;

        if (!$username || !$password) {
            throw new Exception('Booklore credentials not set in settings.');
        }

        $response = Http::post($this->settings->booklore_url . '/api/v1/auth/login', [
            'username' => $username,
            'password' => $password,
        ]);

        if (!$response->successful()) {
            throw new Exception('Failed to log in to Booklore: ' . $response->body());
        }

        $data = $response->json();

        $this->settings->booklore_access_token = $data['accessToken'];
        $this->settings->booklore_refresh_token = $data['refreshToken'];
        $this->settings->booklore_access_token_expires_at = now()->addSeconds(3600);
        $this->settings->save();

        return $data['accessToken'];
    }

}
