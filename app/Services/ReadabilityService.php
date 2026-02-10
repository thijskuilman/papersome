<?php

namespace App\Services;

use App\Enums\ActivityLogChannel;
use App\Enums\ArticleStatus;
use App\Models\Article;
use fivefilters\Readability\Configuration;
use fivefilters\Readability\ParseException;
use fivefilters\Readability\Readability;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class ReadabilityService
{
    public function __construct(
        private readonly LogService $logService,
        private readonly SearchReplaceService $searchReplaceService,
    ) {}

    public function setHtmlContentForArticle(Article $article): void
    {
        $urlPrefix = $article->source->prefix_parse_url;
        $url = ($urlPrefix ?? '').$article->url;

        try {
            $readability = $this->parseWithReadability(
                url: $url,
            );

            $rawHtml = $readability->getContent();

            $processedHtml = $article->source->search_replace ? $this->searchReplaceService->apply(
                rules: $article->source->search_replace,
                subject: $rawHtml,
            ) : $rawHtml;

            $article->update([
                'html_content' => $article->source->html_query_filters ? app(HtmlParseService::class)
                    ->removeFilteredElements(
                        html: $processedHtml,
                        htmlQueryFilters: $article->source->html_query_filters,
                    ) : $processedHtml,
                'original_html_content' => $rawHtml,
                'image' => $readability->getImage(),
                'status' => ArticleStatus::Parsed->value,
            ]);

            $this->logService->success(
                message: 'Article parsed successfully',
                channel: ActivityLogChannel::Readability,
                data: [
                    'article_id' => $article->id,
                    'source_id' => $article->source_id,
                ],
            );
        } catch (\Throwable $e) {
            $article->update(['status' => ArticleStatus::Failed->value]);

            $this->logService->error(
                message: 'Error parsing article content',
                channel: ActivityLogChannel::Readability,
                data: [
                    'article_id' => $article->id,
                    'source_id' => $article->source_id,
                    'error' => $e->getMessage(),
                ],
            );
        }
    }

    /**
     * @throws ParseException|RequestException|\Throwable
     */
    public function parseWithReadability(string $url): ?Readability
    {
        $response = Http::get($url)->throw();

        $html = $response->body();

        $readability = new Readability(new Configuration);

        $readability->parse($html);

        return $readability;
    }
}
