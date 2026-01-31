<?php

namespace App\Services;

use App\Enums\ActivityLogChannel;
use App\Models\Publication;
use App\Settings\ApplicationSettings;
use Exception;
use Illuminate\Support\Facades\DB;

class BookloreService
{
    public function __construct(private readonly BookloreApiService $bookloreApiService, private readonly ApplicationSettings $settings, private readonly LogService $logService) {}

    /**
     * @throws Exception
     */
    public function uploadPublication(Publication $publication): void
    {
        $this->logService->info(
            message: 'Uploading publication to Booklore',
            channel: ActivityLogChannel::Booklore,
            data: [
                'publication_id' => $publication->id,
                'title' => $publication->title,
            ],
        );

        try {
            $book = $this->bookloreApiService->uploadFileAndWaitForBook(
                libraryId: $this->settings->booklore_library_id,
                pathId: $this->settings->booklore_path_id,
                filePath: \Storage::disk('public')->path($publication->epub_file_path),
                expectedTitle: $publication->title
            );

            $publication->booklore_book_id = $book['id'];
            $publication->save();

            $this->logService->success(
                message: 'Publication uploaded to Booklore',
                channel: ActivityLogChannel::Booklore,
                data: [
                    'publication_id' => $publication->id,
                    'book_id' => $book['id'] ?? null,
                ],
            );
        } catch (Exception $e) {
            $this->logService->error(
                message: 'Failed to upload publication to Booklore',
                channel: ActivityLogChannel::Booklore,
                data: [
                    'publication_id' => $publication->id,
                    'error' => $e->getMessage(),
                ],
            );
            throw $e;
        }
    }

    public function requestBookDeletion(int $bookId): void
    {
        $existing = DB::table('booklore_deletion_requests')
            ->where('book_id', $bookId)
            ->first();

        if ($existing) {
            $this->logService->info(
                message: 'Booklore deletion already requested for book',
                channel: ActivityLogChannel::Booklore,
                data: [
                    'book_id' => $bookId,
                ],
            );

            return;
        }

        DB::table('booklore_deletion_requests')->insert([
            'book_id' => $bookId,
            'deletion_requested_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->logService->success(
            message: 'Requested Booklore deletion for book',
            channel: ActivityLogChannel::Booklore,
            data: [
                'book_id' => $bookId,
            ],
        );
    }

    public function unassignFromKoboShelves(int $bookId): void
    {
        $shelves = $this->bookloreApiService->getShelves();

        $koboShelfIds = collect($shelves)
            ->where('name', 'Kobo')
            ->pluck('id')
            ->all();

        $this->logService->info(
            message: "Unassigning book #$bookId from Kobo shelves",
            channel: ActivityLogChannel::Booklore,
            data: [
                'kobo_shelf_ids' => $koboShelfIds,
            ]
        );

        try {
            $this->bookloreApiService->assignBooksToShelves(
                bookIds: [$bookId],
                shelvesToUnassign: $koboShelfIds
            );

            $this->logService->success(
                message: 'Unassigned book from Kobo shelves',
                channel: ActivityLogChannel::Booklore,
                data: [
                    'book_id' => $bookId,
                    'kobo_shelf_ids' => $koboShelfIds,
                ]
            );
        } catch (Exception $e) {
            $this->logService->error(
                message: 'Failed to unassign book from Kobo shelves',
                channel: ActivityLogChannel::Booklore,
                data: [
                    'book_id' => $bookId,
                    'error' => $e->getMessage(),
                ]
            );
            throw $e;
        }
    }

    /**
     * @throws Exception
     */
    public function getLibraryPaths(int $libraryId): array
    {
        $library = $this->bookloreApiService->getLibrary($libraryId);

        return $library['paths'] ?? [];
    }
}
