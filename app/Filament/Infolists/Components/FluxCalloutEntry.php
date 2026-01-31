<?php

declare(strict_types=1);

namespace App\Filament\Infolists\Components;

use Filament\Infolists\Components\Entry;

class FluxCalloutEntry extends Entry
{
    protected string $view = 'filament.infolists.components.callout-entry';

    protected string $color = 'white';

    protected ?string $icon = null;

    public function color(?string $color): static
    {
        $this->color = $color;

        return $this;
    }

    public function icon(?string $icon): static
    {
        $this->icon = $icon;

        return $this;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }
}
