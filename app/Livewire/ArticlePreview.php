<?php

namespace App\Livewire;

use Dom\HTMLDocument as DOMDocument;
use App\Enums\SourceFormEvent;
use App\Models\Article;
use App\Models\Source;
use Filament\Notifications\Notification;
use Livewire\Attributes\Lazy;
use Livewire\Attributes\On;
use Livewire\Component;

#[Lazy]
class ArticlePreview extends Component
{
    public Source $source;

    public array $htmlQueryFilters = [];

    public ?int $selectedArticleId = null;

    public ?string $articleContent = null;

    public string $originalHtmlContent = '';

    public function mount(Source $source): void
    {
        $this->selectedArticleId = $source->articles->first()?->id;

        if ($this->selectedArticleId) {
            $this->refreshArticleContent(showNotification: false);
        }
    }

    #[On(SourceFormEvent::HtmlQueryFiltersUpdated->value)]
    public function filtersUpdated(array $filters = []): void
    {
        $this->htmlQueryFilters = $filters;
        $this->refreshArticleContent();
    }

    public function updatedSelectedArticleId(): void
    {
        $this->refreshArticleContent();
    }

    public function refreshArticleContent(bool $showNotification = true): void
    {
        $article = Article::find($this->selectedArticleId);

        if($article) {
            $this->originalHtmlContent = $article->original_html_content ?? "";
        } else {
            return;
        }

        try {
            $doc = DOMDocument::createFromString($article->original_html_content, LIBXML_NOERROR);

            foreach ($this->htmlQueryFilters  as $filter) {
                if(!$filter['query']) {
                    continue;
                }

                if ($filter['selector'] == 'all') {
                    foreach ($doc->querySelectorAll($filter['query']) as $node) {
                        $node->setAttribute('class', 'papersome-filter');
                    }
                }

                if ($filter['selector'] == 'first') {
                    $doc->querySelector($filter['query'])?->setAttribute('class', 'papersome-filter');
                }
            }

            libxml_clear_errors();

            $body = $doc->getElementsByTagName('body')->item(0);

            $html = '';
            foreach ($body->childNodes as $child) {
                $html .= $doc->saveHTML($child);
            }

            $this->articleContent = $html;

            if($showNotification) {
                Notification::make()
                    ->title('Refreshed article preview')
                    ->success()
                    ->send();
            }
        } catch (\Throwable) {

        }

    }

    public function render()
    {
        return view('livewire.article-preview');
    }
}
