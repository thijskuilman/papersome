<?php

namespace App\Services;

use App\Enums\ActivityLogChannel;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class BookloreApiService
{
    public function __construct(
        private readonly LogService $logService,
    ) {}

    /* -----------------------------------------------------------------
     | Authentication
     |-----------------------------------------------------------------*/

    public function login(User $user, string $username, string $password, ?string $url = null): string
    {
        $url ??= $user->booklore_url;

        if (
            $username === $user->booklore_username &&
            $user->booklore_access_token &&
            $user->booklore_access_token_expires_at?->isFuture()
        ) {
            $this->logService->info(
                message: 'Using cached Booklore access token',
                channel: ActivityLogChannel::Booklore,
                data: [
                    'username' => $username,
                    'expires_at' => (string) $user->booklore_access_token_expires_at,
                ],
            );

            return (string) $user->booklore_access_token;
        }

        if ($user->booklore_refresh_token) {
            try {
                $this->logService->info(
                    message: 'Refreshing Booklore token',
                    channel: ActivityLogChannel::Booklore,
                );

                return $this->refreshToken($user, $url);
            } catch (Exception $e) {
                $this->logService->error(
                    message: 'Failed to refresh Booklore token, clearing tokens',
                    channel: ActivityLogChannel::Booklore,
                    data: [
                        'error' => $e->getMessage(),
                    ],
                );
                $this->clearTokens($user);
            }
        }

        throw_if(! $username || ! $password, new Exception('Booklore credentials not set.'));

        $response = Http::post(rtrim((string) $url, '/').'/api/v1/auth/login', [
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

        $user->booklore_username = $username;
        $user->booklore_url = $url;
        $user->save();

        $data = $response->json();

        $this->storeTokens($user, $data);

        $this->logService->success(
            message: 'Logged in to Booklore successfully',
            channel: ActivityLogChannel::Booklore,
            data: [
                'username' => $username,
            ],
        );

        return $data['accessToken'];
    }

    public function refreshToken(User $user, ?string $url = null): string
    {
        $refreshToken = $user->booklore_refresh_token;

        throw_unless($refreshToken, new Exception('No refresh token available.'));

        $response = Http::post(rtrim((string) ($url ?? $user->booklore_url), '/').'/api/v1/auth/refresh', [
            'refreshToken' => $refreshToken,
        ]);

        if (! $response->successful()) {
            $this->clearTokens($user);
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

        $this->storeTokens($user, $data);

        $this->logService->success(
            message: 'Refreshed Booklore token successfully',
            channel: ActivityLogChannel::Booklore,
        );

        return $data['accessToken'];
    }

    private function storeTokens(User $user, array $data): void
    {
        $user->booklore_access_token = $data['accessToken'];
        $user->booklore_refresh_token = $data['refreshToken'] ?? $user->booklore_refresh_token;
        $user->booklore_access_token_expires_at = $this->getJwtExpiry($data['accessToken']);
        $user->save();
    }

    private function clearTokens(User $user): void
    {
        $user->booklore_access_token = null;
        $user->booklore_refresh_token = null;
        $user->booklore_access_token_expires_at = null;
        $user->save();
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

    private function client(User $user)
    {
        return Http::withToken((string) $user->booklore_access_token)
            ->acceptJson();
    }

    private function retryWithRefresh(User $user, callable $callback): Response
    {
        $response = $callback();

        if ($response->status() !== 401) {
            return $response;
        }

        $this->logService->info(
            message: 'Access token expired, attempting refresh',
            channel: ActivityLogChannel::Booklore,
        );
        $this->refreshToken($user, $user->booklore_url);

        return $callback();
    }

    private function request(User $user, string $method, string $url, array $options = []): Response
    {
        return $this->retryWithRefresh($user, fn () => $this->client($user)->send($method, $url, $options));
    }

    /* -----------------------------------------------------------------
     | Public API methods
     |-----------------------------------------------------------------*/

    public function getLibraries(User $user): array
    {
        $response = $this->request(
            $user,
            'GET',
            rtrim((string) $user->booklore_url, '/').'/api/v1/libraries'
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

    public function getShelves(User $user): array
    {
        $response = $this->request(
            $user,
            'GET',
            rtrim((string) $user->booklore_url, '/').'/api/v1/shelves'
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

    public function assignBooksToShelves(User $user, array $bookIds, array $shelvesToAssign = [], array $shelvesToUnassign = []): array
    {
        $url = rtrim((string) $user->booklore_url, '/').'/api/v1/books/shelves';

        $payload = [
            'bookIds' => array_values(array_map(intval(...), $bookIds)),
            'shelvesToAssign' => array_values(array_map(intval(...), $shelvesToAssign)),
            'shelvesToUnassign' => array_values(array_map(intval(...), $shelvesToUnassign)),
        ];

        $response = $this->request($user, 'POST', $url, [
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
    public function uploadFile(User $user, int $libraryId, int $pathId, string $filePath): void
    {
        throw_unless(file_exists($filePath), new Exception("File not found: {$filePath}"));

        $url = rtrim((string) $user->booklore_url, '/').'/api/v1/files/upload';

        $response = $this->retryWithRefresh($user, fn () => $this->client($user)
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

    public function getLibraryBooks(User $user, int $libraryId): array
    {
        $url = rtrim((string) $user->booklore_url, '/')."/api/v1/libraries/{$libraryId}/book";

        $response = $this->retryWithRefresh($user, fn () => $this->client($user)->get($url));

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
        User $user,
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

        $this->uploadFile($user, $libraryId, $pathId, $filePath);

        $startTime = Carbon::now()->getTimestamp();
        $matchedBook = null;

        while ((Carbon::now()->getTimestamp() - $startTime) < $timeoutSeconds) {
            $books = $this->getLibraryBooks($user, $libraryId);

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

    public function disconnect(User $user): void
    {
        $this->clearTokens($user);
        $user->booklore_url = null;
        $user->booklore_username = null;
        $user->save();
        $this->logService->info(
            message: 'Disconnected from Booklore and cleared credentials',
            channel: ActivityLogChannel::Booklore,
        );
    }

    /**
     * @throws Exception
     */
    public function deleteBooks(User $user, array $bookIds): void
    {
        $url = rtrim((string) $user->booklore_url, '/').'/api/v1/books';

        $response = $this->request($user, 'DELETE', $url, [
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

    public function getLibrary(User $user, int $libraryId): array
    {
        $url = rtrim((string) $user->booklore_url, '/')."/api/v1/libraries/{$libraryId}";

        $response = $this->request($user, 'GET', $url);

        if (! $response->successful()) {
            $this->logService->error(
                message: 'Failed to fetch library details',
                channel: ActivityLogChannel::Booklore,
                data: [
                    'library_id' => $libraryId,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ],
            );

            throw new Exception(
                'Failed to fetch library: '.$response->status().' '.$response->body()
            );
        }

        return $response->json();
    }
}
