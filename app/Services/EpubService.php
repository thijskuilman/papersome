<?php

namespace App\Services;

use App\Dto\ArticleChapter;
use App\Dto\ArticleImage;
use App\Models\Article;
use App\Models\Publication;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use PHPEpub\EpubBuilder;
use Illuminate\Support\Facades\Storage;

class EpubService
{
    public function createEpubFor(Publication $publication, Collection $articles): ?string
    {
        $epub = new EpubBuilder;

        $name = $publication->collection->name . ' - ' . now()->toDateString();

        $epub->setTitle($name)
            ->setAuthor('Various authors')
            ->setLanguage('en')
            ->setDescription('');

        $epub->getMetadata()->setIdentifier(Str::slug($name));

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
        foreach ($articles as $article) {
            $chapters[] = $this->createChapter($article, $epub);
        }

        // Set cover image
        if ($publication->cover_image && Storage::disk('public')->exists($publication->cover_image)) {
            $epub->setCover(Storage::disk('public')->path($publication->cover_image));
        }

        // Add Table of Contents
        $this->addTableOfContents($chapters, $epub);

        // Add chapters to EPUB
        foreach ($chapters as $chapter) {
            $epub->addChapter($chapter->title, $chapter->content, $chapter->fileName);
        }

        // Save EPUB on public disk
        $filename = Str::slug($name) . '.epub';
        $relativePath = 'epubs/' . $filename;
        $fullPath = Storage::disk('public')->path($relativePath);

        if (!is_dir(dirname($fullPath))) {
            mkdir(dirname($fullPath), 0755, true);
        }

        try {
            $epub->save($fullPath);
        } catch (\Exception $e) {
            // TODO: Error logging
            return null;
        }

        return $relativePath;
    }

    private function addTableOfContents(array $chapters, EpubBuilder $epub): void
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

    private function createChapter(Article $article, EpubBuilder $epub): ArticleChapter
    {
        $html = '<style>
            body { color: #000; }
            h1, h2, h3, p { color: #000; }
        </style>';

        // Main image
        if ($article->image) {
            $mainImage = $this->storeTempImage($article->image, "main-$article->id");

            if ($mainImage) {
                $epub->addImage($mainImage->tempPath);
                $html .= "<img src=\"../Images/{$mainImage->fileName}\" alt=\"{$article->title}\" style=\"max-width:100%; height:auto;\" />";
            }
        }

        // Process inline content images
        $processedContent = $this->processContentImages($article->html_content, $article, $epub);

        // Add article title
        $html .= Blade::render(
            '<h1>{{ $article->title }}</h1>{!! $content !!}',
            [
                'article' => $article,
                'content' => $processedContent,
            ]
        );

        return new ArticleChapter(
            title: $article->title,
            fileName: 'chapter-' . $article->id . '.xhtml',
            content: $html
        );
    }

    /**
     * Store a temporary image on the local disk for EPUB processing
     */
    private function storeTempImage(string $imageUrl, string $fileNamePrefix): ?ArticleImage
    {
        try {
            $response = Http::get($imageUrl);
            if (! $response->ok()) return null;

            $pathInfo = pathinfo(parse_url($imageUrl, PHP_URL_PATH));
            $extension = strtolower($pathInfo['extension'] ?? 'jpg');
            if (!in_array($extension, ['jpg','jpeg','png','gif','webp'])) $extension = 'jpg';

            $fileName = "$fileNamePrefix.$extension";
            $tempPath = "temp/epub-images/$fileName";

            Storage::disk('local')->put($tempPath, $response->body());

            return new ArticleImage(
                tempPath: Storage::disk('local')->path($tempPath),
                fileName: $fileName
            );
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function processContentImages(string $html, Article $article, EpubBuilder $epub): string
    {
        libxml_use_internal_errors(true);

        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        $xpath = new \DOMXPath($dom);

        $imgNodes = $xpath->query('//img');

        foreach ($imgNodes as $index => $img) {
            $src = $img->getAttribute('src');
            if (! $src || str_starts_with($src, '../Images/')) continue;

            $image = $this->storeTempImage($src, "article-{$article->id}-img-{$index}");
            if (! $image) continue;

            $epub->addImage($image->tempPath);
            $img->setAttribute('src', '../Images/'.$image->fileName);
            $img->setAttribute('alt', $img->getAttribute('alt') ?: $article->title);

            // Replace <picture> wrapper with <img> if present
            $parent = $img->parentNode;
            if ($parent && $parent->nodeName === 'picture') {
                $parent->parentNode->replaceChild($img, $parent);
            }
        }

        // Return inner XHTML without XML declaration
        $body = $dom->getElementsByTagName('body')->item(0);
        $innerHtml = '';
        if ($body) {
            foreach ($body->childNodes as $child) {
                $innerHtml .= $dom->saveXML($child);
            }
        } else {
            $innerHtml = $dom->saveXML();
        }

        return preg_replace('/^<\?xml.*?\?>\s*/', '', $innerHtml);
    }
}
