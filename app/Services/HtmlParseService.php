<?php

namespace App\Services;

use Dom\HTMLDocument as DOMDocument;

class HtmlParseService
{
    public function removeFilteredElements(string $html, array $htmlQueryFilters): string
    {
        $doc = DOMDocument::createFromString($html, LIBXML_NOERROR);

        foreach ($htmlQueryFilters as $filter) {
            if ($filter['selector'] == 'all') {
                foreach ($doc->querySelectorAll($filter['query']) as $node) {
                    $node->remove();
                }
            }

            if ($filter['selector'] == 'first') {
                $doc->querySelector($filter['query'])?->remove();
            }
        }

        libxml_clear_errors();

        return $doc->saveHTML();
    }
}
