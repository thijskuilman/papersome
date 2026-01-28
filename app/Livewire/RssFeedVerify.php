<?php

namespace App\Livewire;

use App\Enums\SourceFormEvent;
use App\Services\FeedService;
use App\Services\ReadabilityService;
use Livewire\Attributes\On;
use Livewire\Component;

class RssFeedVerify extends Component
{
    public ?string $url = null;

    public ?bool $isValid = null;

    public bool $paywallDetected = false;

    public ?string $errorMessage = null;

    public ?string $articleContent = null;

    public ?string $testedPermalink = null;

    public ?string $testedTitle = null;

    #[On(SourceFormEvent::ResetRssVerification->value)]
    public function resetVerification(): void
    {
        $this->reset();
    }

    #[On(SourceFormEvent::StartRssVerification->value)]
    public function verifyFeedUrl(?string $url): void
    {
        $this->isValid = null;
        $this->url = $url;

        $fetchedItems = app(FeedService::class)->fetchRssItems($url);
        try {
            $this->testedPermalink = $fetchedItems->first()['permalink'];

            $readability = app(ReadabilityService::class)->parseWithReadability(
                url: $this->testedPermalink,
            );

            if (str_contains((string) $readability->getContent(), 'paywall')) {
                $this->paywallDetected = true;
            }
            $this->isValid = true;

            $this->articleContent = $readability->getContent();

            $this->testedTitle = $readability->getTitle();
        } catch (\Exception $e) {
            $this->isValid = false;
            $this->errorMessage = $e->getMessage();
        }

    }

    public function render(): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
    {
        return view('livewire.rss-feed-verify');
    }
}
