<div>
    @if($largeCode && !$content)
        <flux:callout icon="code-bracket" variant="secondary" inline>
            <flux:callout.heading>
                Large source code detected
            </flux:callout.heading>
            <flux:callout.text>
                It might take a while to show the source code.
            </flux:callout.text>
            <x-slot name="actions">
                <flux:button variant="primary" wire:click="getContent">
                    Load source code
                </flux:button>
            </x-slot>
        </flux:callout>
    @endif

    @if($content)
        {!! $content !!}
    @endif
</div>
