<?php

namespace App\Services;

use App\Dto\ArticleChapter;
use App\Dto\ArticleImage;
use App\Models\Article;
use App\Models\Collection;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use PHPEpub\EpubBuilder;
use Storage;

class EpubService
{
    public function createEpubFor(Collection $collection): ?string {

        $epub = new EpubBuilder();

        $name = $collection->name . ' - ' . now()->toDateString();

        $epub->setTitle($name)
            ->setAuthor('Various authors')
            ->setLanguage('en')
            ->setDescription('');

        $epub->getMetadata()->setIdentifier('collection-' . $collection->id);

        // Accessibility metadata
        $epub->addAccessMode('textual')
            ->addAccessMode('visual')
            ->addAccessibilityFeature('structuralNavigation')
            ->addAccessibilityFeature('alternativeText')
            ->addAccessibilityFeature('readingOrder')
            ->addAccessibilityFeature('tableOfContents')
            ->addAccessibilityHazard('none')
            ->setAccessibilitySummary('This EPUB includes all articles from the collection and is fully navigable and accessible.');

        // Build chapters
        $chapters = [];
        foreach ($collection->sources as $source) {
            foreach ($source->articles as $article) {
                $chapters[] = $this->createChapter($article, $epub);
            }
        }

        // Build epub
        $this->addTableOfContents($chapters, $epub);
        foreach ($chapters as $chapter) {
            $epub->addChapter($chapter->title, $chapter->content, $chapter->fileName);
        }

        // Save epub and download it
        $filename = Str::slug($name) . '.epub';
        $tmpPath = storage_path('app/epubs/' . $filename);
        if (!is_dir(dirname($tmpPath))) {
            mkdir(dirname($tmpPath), 0755, true);
        }

        try {
            $epub->save($tmpPath);
        } catch (\Exception $e) {
            // TODO: Error logging
            return null;
        }

        return $tmpPath;
    }

    private function addTableOfContents(array $chapters, &$epub): void
    {
        $tocHtml = '<h1>Table of Contents</h1><ul>';
        foreach ($chapters as $chapter) {
            $tocHtml .= sprintf(
                '<li><a href="%s">%s</a></li>',
                $chapter->fileName,
                htmlspecialchars($chapter->title)
            );
        }
        $tocHtml .= '</ul>';

        $epub->addChapter('Table of Contents', $tocHtml, 'toc.xhtml');
    }

    private function storeMainImage(Article $article): ?ArticleImage
    {
        $imageUrl = $article->image;

        $response = Http::get($imageUrl);

        if ($response->ok()) {
            // Get original file extension from URL
            $pathInfo = pathinfo(parse_url($imageUrl, PHP_URL_PATH));
            $extension = strtolower($pathInfo['extension'] ?? 'jpg'); // fallback to jpg if missing

            // Validate allowed image types
            if (!in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                $extension = 'jpg';
            }

            $fileName = "main-$article->id.$extension";
            $tempPath = "temp/articles/$article->id/$fileName";
            Storage::put($tempPath, $response->body());

            // TODO: Clean up afterwards
            //  Storage::delete($tempPath);

            return new ArticleImage(
                tempPath: Storage::path($tempPath),
                fileName: $fileName
            );
        }

        return null;
    }

    private function createChapter(Article $article, &$epub): ArticleChapter
    {
        $html = '<style>
            body { color: #000000; }
            h1, h2, h3, p { color: #000000; }
        </style>';

        // Download main image
        if ($article->image) {
            $mainImagePath = $this->storeMainImage($article);

            if ($mainImagePath) {
                $epub->addImage($mainImagePath->tempPath);
                $html .= "<img src=\"../Images/$mainImagePath->fileName\" alt=\"{$article->title}\" style=\"max-width:100%; height:auto;\"/>";
            }
        }
        $html .= Blade::render(
            '<h1>{{ $article->title }}</h1>{!! $article->html_content !!}',
            ['article' => $article]
        );

        return new ArticleChapter(
            title: $article->title,
            fileName: 'chapter-' . $article->id . '.xhtml',
            content: $html,
        );
    }
}
