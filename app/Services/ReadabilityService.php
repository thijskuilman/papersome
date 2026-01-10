<?php

namespace App\Services;

use andreskrey\Readability\Configuration;
use andreskrey\Readability\ParseException;
use andreskrey\Readability\Readability;
use App\Enums\ArticleStatus;
use App\Models\Article;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

class ReadabilityService
{
    public function parseArticleContent(Article $article): void
    {
        $readability = new Readability(new Configuration);

        $urlPrefix = $article->source->prefix_parse_url;

        $url = ($urlPrefix ?? '').$article->url;

        $response = Http::get($url);

        $html = $response->body();

        try {
            $readability->parse($html);

            $content = $readability->getContent();

            $article->update([
                'html_content' => $content,
                'status' => ArticleStatus::Parsed->value,
                'image' => $readability->getImage(),
            ]);

        } catch (ParseException $e) {
            $article->update(['status' => ArticleStatus::Failed->value]);
            Log::error(sprintf('Error parsing article with ID '.$article.': %s', $e->getMessage()));
        }
    }

    private function sanitizeHtml(string $html): string
    {
        $config = new HtmlSanitizerConfig;

        // Allow safe standard elements like <p>, <b>, <i>, etc.
        $config = $config->allowSafeElements();

        $elements = ['h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'ul', 'ol', 'li', 'figure', 'figcaption', 'img', 'a'];
        foreach ($elements as $el) {
            $config = $config->allowElement($el, '*'); // '*' = all standard attributes
        }

        $config = $config
            ->allowAttribute('src', ['img'])
            ->allowAttribute('alt', ['img'])
            ->allowAttribute('href', ['a'])
            ->allowAttribute('title', ['a']);

        $sanitizer = new HtmlSanitizer($config);

        return $sanitizer->sanitize($html);
    }
}
