<?php

namespace App\Services;

use App\Models\Publication;
use App\Settings\ApplicationSettings;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class BookloreApiService
{
    public function __construct(
        private readonly ApplicationSettings $settings
    ) {}

    /* -----------------------------------------------------------------
     | Authentication
     |-----------------------------------------------------------------*/

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
            } catch (Exception) {
                $this->clearTokens();
            }
        }

        if (! $username || ! $password) {
            throw new Exception('Booklore credentials not set.');
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

    private function storeTokens(array $data): void
    {
        $this->settings->booklore_access_token = $data['accessToken'];
        $this->settings->booklore_refresh_token = $data['refreshToken'] ?? $this->settings->booklore_refresh_token;
        $this->settings->booklore_access_token_expires_at = $this->getJwtExpiry($data['accessToken']);
        $this->settings->save();
    }

    private function clearTokens(): void
    {
        $this->settings->booklore_access_token = null;
        $this->settings->booklore_refresh_token = null;
        $this->settings->booklore_access_token_expires_at = null;
        $this->settings->save();
    }

    private function getJwtExpiry(string $jwt): ?Carbon
    {
        $parts = explode('.', $jwt);

        if (count($parts) !== 3) {
            return null;
        }

        $payload = json_decode(
            base64_decode(strtr($parts[1], '-_', '+/')),
            true
        );

        return isset($payload['exp'])
            ? Carbon::createFromTimestamp($payload['exp'])
            : null;
    }

    /* -----------------------------------------------------------------
     | HTTP helpers
     |-----------------------------------------------------------------*/

    private function client()
    {
        return Http::withToken($this->settings->booklore_access_token)
            ->acceptJson();
    }

    private function retryWithRefresh(callable $callback): Response
    {
        $response = $callback();

        if ($response->status() !== 401) {
            return $response;
        }

        $this->refreshToken($this->settings->booklore_url);

        return $callback();
    }

    private function request(string $method, string $url, array $options = []): Response
    {
        return $this->retryWithRefresh(function () use ($method, $url, $options) {
            return $this->client()->send($method, $url, $options);
        });
    }

    /* -----------------------------------------------------------------
     | Public API methods
     |-----------------------------------------------------------------*/

    public function getLibraries(): array
    {
        $response = $this->request(
            'GET',
            $this->settings->booklore_url . '/api/v1/libraries'
        );

        if (! $response->successful()) {
            throw new Exception('Failed to fetch libraries: ' . $response->body());
        }

        return $response->json();
    }

    public function getShelves(): array
    {
        $response = $this->request(
            'GET',
            $this->settings->booklore_url . '/api/v1/shelves'
        );

        if (! $response->successful()) {
            throw new Exception('Failed to fetch libraries: ' . $response->body());
        }

        return $response->json();
    }

    public function assignBooksToShelves(array $bookIds, array $shelvesToAssign = [], array $shelvesToUnassign = []): array
    {
        $url = $this->settings->booklore_url . '/api/v1/books/shelves';

        $payload = [
            'bookIds' => array_values(array_map('intval', $bookIds)),
            'shelvesToAssign' => array_values(array_map('intval', $shelvesToAssign)),
            'shelvesToUnassign' => array_values(array_map('intval', $shelvesToUnassign)),
        ];

        $response = $this->request('POST', $url, [
            'json' => $payload,
        ]);

        if (! $response->successful()) {
            throw new Exception(
                'Failed to assign/unassign books to shelves: ' . $response->status() . ' ' . $response->body()
            );
        }

        return $response->json();
    }

    /**
     * Upload a file to a specific library path (fire-and-forget)
     *
     * @throws Exception
     */
    public function uploadFile(int $libraryId, int $pathId, string $filePath): void
    {
        if (! file_exists($filePath)) {
            throw new Exception("File not found: {$filePath}");
        }

        $url = $this->settings->booklore_url . '/api/v1/files/upload';

        $response = $this->retryWithRefresh(function () use ($url, $libraryId, $pathId, $filePath) {
            return $this->client()
                ->attach(
                    'file',
                    fopen($filePath, 'r'),
                    basename($filePath)
                )
                ->post($url, [
                    'libraryId' => $libraryId,
                    'pathId'    => $pathId,
                ]);
        });

        if (! $response->successful()) {
            throw new Exception(
                'File upload failed: ' . $response->status() . ' ' . $response->body()
            );
        }
    }

    public function getLibraryBooks(int $libraryId): array
    {
        $url = $this->settings->booklore_url . "/api/v1/libraries/{$libraryId}/book";

        $response = $this->retryWithRefresh(function () use ($url) {
            return $this->client()->get($url);
        });

        if (! $response->successful()) {
            throw new Exception(
                'Failed to fetch books: ' . $response->status() . ' ' . $response->body()
            );
        }

        return $response->json() ?: [];
    }

    public function uploadFileAndWaitForBook(
        int $libraryId,
        int $pathId,
        string $filePath,
        string $expectedTitle,
        int $timeoutSeconds = 15,
        int $pollIntervalMs = 500
    ): array {
        if (!file_exists($filePath)) {
            throw new Exception("File not found: {$filePath}");
        }

        $this->uploadFile($libraryId, $pathId, $filePath);

        $startTime = time();
        $matchedBook = null;

        while ((time() - $startTime) < $timeoutSeconds) {
            $books = $this->getLibraryBooks($libraryId);

            foreach ($books as $book) {
                if (isset($book['metadata']['title']) && $book['metadata']['title'] === $expectedTitle) {
                    $matchedBook = $book;
                    break 2;
                }
            }

            usleep($pollIntervalMs * 1000);
        }

        if ($matchedBook === null) {
            throw new Exception(
                "Timeout waiting for book with title '{$expectedTitle}' to appear in library {$libraryId}"
            );
        }

        return $matchedBook;
    }


    public function disconnect(): void
    {
        $this->clearTokens();
        $this->settings->booklore_url = null;
        $this->settings->booklore_username = null;
        $this->settings->save();
    }

    /**
     * @throws Exception
     */
    public function deleteBooks(array $bookIds): void
    {
        $url = $this->settings->booklore_url . '/api/v1/books';

        $response = $this->request('DELETE', $url, [
            'query' => [
                'ids' => implode(',', $bookIds),
            ],
        ]);

        if (! $response->successful()) {
            throw new Exception(
                'Failed to delete books: ' . $response->status() . ' ' . $response->body()
            );
        }
    }
}
