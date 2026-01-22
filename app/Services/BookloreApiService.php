<?php

namespace App\Services;

use App\Enums\ActivityLogChannel;
use App\Settings\ApplicationSettings;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class BookloreApiService
{
    public function __construct(
        private readonly ApplicationSettings $settings,
        private readonly LogService $logService,
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
            $this->logService->info(
                message: 'Using cached Booklore access token',
                channel: ActivityLogChannel::Booklore,
                data: [
                    'username' => $username,
                    'expires_at' => (string) $this->settings->booklore_access_token_expires_at,
                ],
            );

            return $this->settings->booklore_access_token;
        }

        if ($this->settings->booklore_refresh_token) {
            try {
                $this->logService->info(
                    message: 'Refreshing Booklore token',
                    channel: ActivityLogChannel::Booklore,
                );

                return $this->refreshToken($url);
            } catch (Exception $e) {
                $this->logService->error(
                    message: 'Failed to refresh Booklore token, clearing tokens',
                    channel: ActivityLogChannel::Booklore,
                    data: [
                        'error' => $e->getMessage(),
                    ],
                );
                $this->clearTokens();
            }
        }

        throw_if(! $username || ! $password, new Exception('Booklore credentials not set.'));

        $response = Http::post($url.'/api/v1/auth/login', [
            'username' => $username,
            'password' => $password,
        ]);

        if (! $response->successful()) {
            $this->logService->error(
                message: 'Failed to log in to Booklore',
                channel: ActivityLogChannel::Booklore,
                data: [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ],
            );
            throw new Exception('Failed to log in to Booklore: '.$response->body());
        }

        $this->settings->booklore_username = $username;
        $this->settings->booklore_url = $url;
        $this->settings->save();

        $data = $response->json();

        $this->storeTokens($data);

        $this->logService->success(
            message: 'Logged in to Booklore successfully',
            channel: ActivityLogChannel::Booklore,
            data: [
                'username' => $username,
            ],
        );

        return $data['accessToken'];
    }

    public function refreshToken(?string $url = null): string
    {
        $refreshToken = $this->settings->booklore_refresh_token;

        throw_unless($refreshToken, new Exception('No refresh token available.'));

        $response = Http::post($url.'/api/v1/auth/refresh', [
            'refreshToken' => $refreshToken,
        ]);

        if (! $response->successful()) {
            $this->clearTokens();
            $this->logService->error(
                message: 'Failed to refresh Booklore token',
                channel: ActivityLogChannel::Booklore,
                data: [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ],
            );
            throw new Exception('Failed to refresh Booklore token: '.$response->body());
        }

        $data = $response->json();

        $this->storeTokens($data);

        $this->logService->success(
            message: 'Refreshed Booklore token successfully',
            channel: ActivityLogChannel::Booklore,
        );

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

        $this->logService->info(
            message: 'Access token expired, attempting refresh',
            channel: ActivityLogChannel::Booklore,
        );
        $this->refreshToken($this->settings->booklore_url);

        return $callback();
    }

    private function request(string $method, string $url, array $options = []): Response
    {
        return $this->retryWithRefresh(fn () => $this->client()->send($method, $url, $options));
    }

    /* -----------------------------------------------------------------
     | Public API methods
     |-----------------------------------------------------------------*/

    public function getLibraries(): array
    {
        $response = $this->request(
            'GET',
            $this->settings->booklore_url.'/api/v1/libraries'
        );

        if (! $response->successful()) {
            $this->logService->error(
                message: 'Failed to fetch libraries',
                channel: ActivityLogChannel::Booklore,
                data: [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ],
            );
            throw new Exception('Failed to fetch libraries: '.$response->body());
        }

        return $response->json();
    }

    public function getShelves(): array
    {
        $response = $this->request(
            'GET',
            $this->settings->booklore_url.'/api/v1/shelves'
        );

        if (! $response->successful()) {
            $this->logService->error(
                message: 'Failed to fetch shelves',
                channel: ActivityLogChannel::Booklore,
                data: [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ],
            );
            throw new Exception('Failed to fetch libraries: '.$response->body());
        }

        return $response->json();
    }

    public function assignBooksToShelves(array $bookIds, array $shelvesToAssign = [], array $shelvesToUnassign = []): array
    {
        $url = $this->settings->booklore_url.'/api/v1/books/shelves';

        $payload = [
            'bookIds' => array_values(array_map(intval(...), $bookIds)),
            'shelvesToAssign' => array_values(array_map(intval(...), $shelvesToAssign)),
            'shelvesToUnassign' => array_values(array_map(intval(...), $shelvesToUnassign)),
        ];

        $response = $this->request('POST', $url, [
            'json' => $payload,
        ]);

        if (! $response->successful()) {
            $this->logService->error(
                message: 'Failed to assign/unassign books to shelves',
                channel: ActivityLogChannel::Booklore,
                data: [
                    'response_status' => $response->status(),
                    'response_body' => $response->body(),
                ]
            );
            throw new Exception(
                'Failed to assign/unassign books to shelves: '.$response->status().' '.$response->body()
            );
        }

        $this->logService->success(
            message: 'Assigned/unassigned books to shelves',
            channel: ActivityLogChannel::Booklore,
            data: [
                'book_ids' => $bookIds,
                'shelves_to_assign' => $shelvesToAssign,
                'shelves_to_unassign' => $shelvesToUnassign,
            ],
        );

        return $response->json();
    }

    /**
     * Upload a file to a specific library path (fire-and-forget)
     *
     * @throws Exception
     */
    public function uploadFile(int $libraryId, int $pathId, string $filePath): void
    {
        throw_unless(file_exists($filePath), new Exception("File not found: {$filePath}"));

        $url = $this->settings->booklore_url.'/api/v1/files/upload';

        $response = $this->retryWithRefresh(fn () => $this->client()
            ->attach(
                'file',
                fopen($filePath, 'r'),
                basename($filePath)
            )
            ->post($url, [
                'libraryId' => $libraryId,
                'pathId' => $pathId,
            ]));

        if (! $response->successful()) {
            $this->logService->error(
                message: 'File upload to Booklore failed',
                channel: ActivityLogChannel::Booklore,
                data: [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'library_id' => $libraryId,
                    'path_id' => $pathId,
                    'file' => basename($filePath),
                ],
            );
            throw new Exception(
                'File upload failed: '.$response->status().' '.$response->body()
            );
        }

        $this->logService->success(
            message: 'File uploaded to Booklore',
            channel: ActivityLogChannel::Booklore,
            data: [
                'library_id' => $libraryId,
                'path_id' => $pathId,
                'file' => basename($filePath),
            ],
        );
    }

    public function getLibraryBooks(int $libraryId): array
    {
        $url = $this->settings->booklore_url."/api/v1/libraries/{$libraryId}/book";

        $response = $this->retryWithRefresh(fn () => $this->client()->get($url));

        if (! $response->successful()) {
            $this->logService->error(
                message: 'Failed to fetch books from library',
                channel: ActivityLogChannel::Booklore,
                data: [
                    'library_id' => $libraryId,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ],
            );
            throw new Exception(
                'Failed to fetch books: '.$response->status().' '.$response->body()
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
        throw_unless(file_exists($filePath), new Exception("File not found: {$filePath}"));

        $this->logService->info(
            message: 'Uploading file and waiting for book to appear',
            channel: ActivityLogChannel::Booklore,
            data: [
                'library_id' => $libraryId,
                'expected_title' => $expectedTitle,
                'timeout_seconds' => $timeoutSeconds,
                'poll_interval_ms' => $pollIntervalMs,
            ],
        );

        $this->uploadFile($libraryId, $pathId, $filePath);

        $startTime = \Carbon\Carbon::now()->getTimestamp();
        $matchedBook = null;

        while ((\Carbon\Carbon::now()->getTimestamp() - $startTime) < $timeoutSeconds) {
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
            $this->logService->error(
                message: 'Timeout waiting for uploaded book to appear',
                channel: ActivityLogChannel::Booklore,
                data: [
                    'library_id' => $libraryId,
                    'expected_title' => $expectedTitle,
                    'timeout_seconds' => $timeoutSeconds,
                ],
            );
            throw new Exception(
                "Timeout waiting for book with title '{$expectedTitle}' to appear in library {$libraryId}"
            );
        }

        $this->logService->success(
            message: 'Uploaded book appeared in library',
            channel: ActivityLogChannel::Booklore,
            data: [
                'library_id' => $libraryId,
                'book_id' => $matchedBook['id'] ?? null,
                'title' => $expectedTitle,
            ],
        );

        return $matchedBook;
    }

    public function disconnect(): void
    {
        $this->clearTokens();
        $this->settings->booklore_url = null;
        $this->settings->booklore_username = null;
        $this->settings->save();
        $this->logService->info(
            message: 'Disconnected from Booklore and cleared credentials',
            channel: ActivityLogChannel::Booklore,
        );
    }

    /**
     * @throws Exception
     */
    public function deleteBooks(array $bookIds): void
    {
        $url = $this->settings->booklore_url.'/api/v1/books';

        $response = $this->request('DELETE', $url, [
            'query' => [
                'ids' => implode(',', $bookIds),
            ],
        ]);

        if (! $response->successful()) {
            $this->logService->error(
                message: 'Failed to delete books from Booklore',
                channel: ActivityLogChannel::Booklore,
                data: [
                    'book_ids' => $bookIds,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ],
            );
            throw new Exception(
                'Failed to delete books: '.$response->status().' '.$response->body()
            );
        }

        $this->logService->success(
            message: 'Deleted books from Booklore',
            channel: ActivityLogChannel::Booklore,
            data: [
                'book_ids' => $bookIds,
            ],
        );
    }
}
