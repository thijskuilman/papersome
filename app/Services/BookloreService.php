<?php

namespace App\Services;

use App\Models\Publication;
use App\Settings\ApplicationSettings;
use Exception;
use Illuminate\Support\Facades\DB;

class BookloreService
{
    public function __construct(private readonly BookloreApiService $bookloreApiService, private readonly ApplicationSettings $settings) {}

    /**
     * @throws Exception
     */
    public function uploadPublication(Publication $publication): void
    {
        $book = $this->bookloreApiService->uploadFileAndWaitForBook(
            libraryId: $this->settings->booklore_library_id,
            pathId: 1,
            filePath: \Storage::disk('public')->path($publication->epub_file_path),
            expectedTitle: $publication->title
        );

        $publication->booklore_book_id = $book['id'];
        $publication->save();
    }

    public function requestBookDeletion(int $bookId): void
    {
        $existing = DB::table('booklore_deletion_requests')
            ->where('book_id', $bookId)
            ->first();

        if ($existing) {
            return;
        }

        DB::table('booklore_deletion_requests')->insert([
            'book_id' => $bookId,
            'deletion_requested_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function unassignFromKoboShelves(int $bookId): void
    {
        $shelves = $this->bookloreApiService->getShelves();

        $koboShelfIds = collect($shelves)
            ->where('name', 'Kobo')
            ->pluck('id')
            ->all();

        $this->bookloreApiService->assignBooksToShelves(
            bookIds: [$bookId],
            shelvesToUnassign: $koboShelfIds
        );
    }
}
