<?php

namespace App\Services;

use andreskrey\Readability\Configuration;
use andreskrey\Readability\ParseException;
use andreskrey\Readability\Readability;
use App\Enums\ActivityLogChannel;
use App\Enums\ArticleStatus;
use App\Models\Article;
use Illuminate\Support\Facades\Http;

class ReadabilityService
{
    public function __construct(private readonly LogService $logService) {}

    public function parseArticleContent(Article $article): void
    {
        $readability = new Readability(new Configuration);

        $urlPrefix = $article->source->prefix_parse_url;

        $url = ($urlPrefix ?? '').$article->url;

        $response = Http::get($url);

        $html = $response->body();

        if($article->source->html_query_filters) {
            $html = app(HtmlParseService::class)
                ->removeFilteredElements(html: $html, article: $article);
        }

        try {
            $readability->parse($html);

            $content = $readability->getContent();

            $article->update([
                'html_content' => $content,
                'status' => ArticleStatus::Parsed->value,
                'image' => $readability->getImage(),
            ]);
            $this->logService->success(
                message: 'Article parsed successfully',
                channel: ActivityLogChannel::Readability,
                data: [
                    'article_id' => $article->id,
                    'source_id' => $article->source_id,
                ],
            );
        } catch (ParseException $e) {
            $article->update(['status' => ArticleStatus::Failed->value]);
            $this->logService->error(
                message: 'Error parsing article content',
                channel: ActivityLogChannel::Readability,
                data: [
                    'article_id' => $article->id,
                    'error' => $e->getMessage(),
                ],
            );
        }
    }
}
