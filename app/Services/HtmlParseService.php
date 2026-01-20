<?php

namespace App\Services;

use App\Models\Article;
use Dom\HTMLDocument as DOMDocument;

class HtmlParseService
{
    public function removeFilteredElements(string $html, Article $article): string {
        $doc = DOMDocument::createFromString($html, LIBXML_NOERROR);

        foreach ($article->source->html_query_filters as $filter) {
            if($filter['selector'] == 'all') {
                foreach ($doc->querySelectorAll($filter['query']) as $node) {
                    $node->remove();
                }
            }

            if($filter['selector'] == 'first') {
                $doc->querySelector($filter['query'])?->remove();
            }
        }

        libxml_clear_errors();

        return $doc->saveHTML();
    }
}
