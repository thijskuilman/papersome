<x-dynamic-component
    :component="$getEntryWrapperView()"
    :entry="$entry"
>
    <flux:callout :color="$getColor()" :icon="$getIcon()">
        @php
            $state = $getState()
        @endphp
        @if(!$state)
            <flux:icon.loading class="size-5"  />
        @else
            <flux:callout.text>
                {{ $state }}
            </flux:callout.text>
        @endif
    </flux:callout>
</x-dynamic-component>
