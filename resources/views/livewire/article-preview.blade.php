@use(Phiki\Grammar\Grammar)
@use(Phiki\Theme\Theme)
<div>
    <div class="mb-4">
        <h2 class="fi-header-heading mb-2">
            Layout preview
        </h2>

        <flux:subheading>
            See how your article will look with the current layout settings.
        </flux:subheading>
    </div>

    @if($source->articles()->count() === 0)
        Can't show article preview since there are no articles yet.
    @else
        <div class="grid gap-4 md:grid-cols-5">
            <div class="col-span-5 md:col-span-3">
                <x-filament::input.wrapper>
                    <x-filament::input.select wire:model.live="selectedArticleId">
                        @foreach($source->articles->take(10) as $article)
                            <option value="{{ $article->id }}">
                                Article: {{ $article->title }}
                            </option>
                        @endforeach
                    </x-filament::input.select>
                </x-filament::input.wrapper>

                @if($articleContent)
                    <div class="fi-prose mt-4 ">
                        {!! $articleContent !!}
                    </div>
                @endif
            </div>

            <div class="col-span-5 md:col-span-2">
                <div class="overflow-auto max-w-full sticky top-20">
                    <livewire:code-preview :code="$originalHtmlContent" />
                </div>
            </div>
        </div>
    @endif
</div>
