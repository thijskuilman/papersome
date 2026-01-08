<?php

namespace App\Services;

use App\Models\Publication;
use App\Settings\ApplicationSettings;
use Exception;

readonly class BookloreService
{
    public function __construct(private BookloreApiService $bookloreApiService, private ApplicationSettings $settings) {}

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

    public function deletePublication(Publication $publication): void {
        $this->bookloreApiService->deleteBooks([$publication->booklore_book_id]);
    }
}
