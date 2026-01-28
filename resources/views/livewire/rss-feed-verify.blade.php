<div class="flex flex-col gap-4">
    @if($isValid !== null)
        <div class="mb-2">
            @if($isValid === true)
                @if($paywallDetected)
                    <flux:callout icon="exclamation-triangle" color="amber">
                        <flux:callout.heading>Possible paywall detected</flux:callout.heading>

                        <flux:callout.text>
                            Successfully obtained articles from this feed, but it seems to be behind a paywall.
                            <flux:callout.link @click="$dispatch('open-modal', { id: 'view-article' })">View tested
                                article
                            </flux:callout.link>
                        </flux:callout.text>
                    </flux:callout>
                @else
                    <flux:callout icon="check" color="green">
                        <flux:callout.text>
                            Successfully obtained articles from this feed.
                            <flux:callout.link @click="$dispatch('open-modal', { id: 'view-article' })">View tested
                                article
                            </flux:callout.link>
                        </flux:callout.text>
                    </flux:callout>
                @endif

            @endif

            @if($isValid === false)
                <flux:callout icon="x-mark" color="red">
                    <flux:callout.heading>Something went wrong</flux:callout.heading>

                    <flux:callout.text>
                        Could not read articles from this feed: {{ $errorMessage }}
                    </flux:callout.text>
                </flux:callout>
            @endif
        </div>
    @endif

    @if($isValid)
        <x-filament::button
            wire:click="$dispatch('submit-form')"
            class="w-full"
            color="primary"
        >
            Create
        </x-filament::button>
    @else
        <x-filament::button
            disabled
            class="w-full"
            color="primary"
        >
            <x-filament::loading-indicator wire:loading class="h-5 w-5"/>

            Create
        </x-filament::button>
    @endif

    <x-filament::modal id="view-article" width="5xl" slide-over>
        <flux:callout>
            <flux:callout.text>
                We tested the following article:

                <x-filament::link :href="$testedPermalink">
                    {{ $testedTitle }}
                </x-filament::link>
            </flux:callout.text>

            <flux:callout.text>
                You will be able to finetune the parsing of articles after creating the source.
            </flux:callout.text>
        </flux:callout>
        <div class="fi-prose">
            {!! $articleContent !!}
        </div>
    </x-filament::modal>
</div>
