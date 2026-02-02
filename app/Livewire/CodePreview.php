<?php

namespace App\Livewire;

use Livewire\Attributes\Reactive;
use Livewire\Component;
use Phiki\Adapters\Laravel\Facades\Phiki;
use Phiki\Grammar\Grammar;
use Phiki\Theme\Theme;

class CodePreview extends Component
{
    public ?string $content = null;

    #[Reactive]
    public string $code;

    public bool $largeCode;

    public function mount(): void
    {
        $this->initialize();
    }

    public function initialize(): void
    {
        $this->largeCode = strlen($this->code) > 9000;

        if (! $this->largeCode) {
            $this->getContent();
        }
    }

    public function updatedCode(): void
    {
        $this->content = null;
        $this->initialize();
    }

    public function getContent(): void
    {
        $this->content = Phiki::codeToHtml(
            $this->code,
            grammar: Grammar::Html,
            theme: Theme::GithubDark
        )->toString();
    }

    public function render(): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
    {
        return view('livewire.code-preview');
    }
}
