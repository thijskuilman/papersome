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
    public function createEpubFor(Collection $collection): ?string
    {
        $epub = new EpubBuilder;

        $name = $collection->name.' - '.now()->toDateString();

        $epub->setTitle($name)
            ->setAuthor('Various authors')
            ->setLanguage('en')
            ->setDescription('');

        $epub->getMetadata()->setIdentifier('collection-'.$collection->id);

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

        // Build EPUB
        $this->addTableOfContents($chapters, $epub);
        foreach ($chapters as $chapter) {
            $epub->addChapter($chapter->title, $chapter->content, $chapter->fileName);
        }

        // Save EPUB
        $filename = Str::slug($name).'.epub';
        $relativePath = 'epubs/'.$filename;
        $tmpPath = storage_path('app/'.$relativePath);
        if (! is_dir(dirname($tmpPath))) {
            mkdir(dirname($tmpPath), 0755, true);
        }

        try {
            $epub->save($tmpPath);
        } catch (\Exception $e) {
            // TODO: Error logging
            return null;
        }

        return $relativePath;
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
            $pathInfo = pathinfo(parse_url($imageUrl, PHP_URL_PATH));
            $extension = strtolower($pathInfo['extension'] ?? 'jpg');

            if (! in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                $extension = 'jpg';
            }

            $fileName = "main-$article->id.$extension";
            $tempPath = "temp/articles/$article->id/$fileName";
            Storage::put($tempPath, $response->body());

            return new ArticleImage(
                tempPath: Storage::path($tempPath),
                fileName: $fileName
            );
        }

        return null;
    }

    private function storeInlineImage(string $imageUrl, int $articleId, int $index): ?ArticleImage
    {
        try {
            $response = Http::get($imageUrl);

            if (! $response->ok()) {
                return null;
            }

            $pathInfo = pathinfo(parse_url($imageUrl, PHP_URL_PATH));
            $extension = strtolower($pathInfo['extension'] ?? 'jpg');

            if (! in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                $extension = 'jpg';
            }

            $fileName = "article-{$articleId}-img-{$index}.{$extension}";
            $tempPath = "temp/articles/{$articleId}/{$fileName}";

            Storage::put($tempPath, $response->body());

            return new ArticleImage(
                tempPath: Storage::path($tempPath),
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

        // Find all <img> tags
        $imgNodes = $xpath->query('//img');

        /** @var \DOMElement $img */
        foreach ($imgNodes as $index => $img) {
            $src = $img->getAttribute('src');
            if (! $src || str_starts_with($src, '../Images/')) {
                continue;
            }

            $image = $this->storeInlineImage($src, $article->id, $index);
            if (! $image) {
                continue;
            }

            $epub->addImage($image->tempPath);
            $img->setAttribute('src', '../Images/'.$image->fileName);
            $img->setAttribute('alt', $img->getAttribute('alt') ?: $article->title);

            // If <img> is inside <picture>, replace <picture> with <img> itself
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
                $innerHtml .= $dom->saveXML($child); // ensures self-closing <img />
            }
        } else {
            $innerHtml = $dom->saveXML();
        }

        // Remove XML declaration if present
        return preg_replace('/^<\?xml.*?\?>\s*/', '', $innerHtml);
    }

    private function createChapter(Article $article, &$epub): ArticleChapter
    {
        $html = '<style>
            body { color: #000000; }
            h1, h2, h3, p { color: #000000; }
        </style>';

        // Main image
        if ($article->image) {
            $mainImage = $this->storeMainImage($article);

            if ($mainImage) {
                $epub->addImage($mainImage->tempPath);
                $html .= "<img src=\"../Images/$mainImage->fileName\" alt=\"{$article->title}\" style=\"max-width:100%; height:auto;\"/>";
            }
        }

        // Process all images inside content
        $processedContent = $this->processContentImages($article->html_content, $article, $epub);

        $html .= Blade::render(
            '<h1>{{ $article->title }}</h1>{!! $content !!}',
            [
                'article' => $article,
                'content' => $processedContent,
            ]
        );

        return new ArticleChapter(
            title: $article->title,
            fileName: 'chapter-'.$article->id.'.xhtml',
            content: $html
        );
    }
}
