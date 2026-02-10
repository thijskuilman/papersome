<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">
            Feed
        </x-slot>

        <flux:input
            label="Feed URL"
            description="Copy this URL to subscribe to your OPDS feed in any compatible application."
            value="{{ route('opds.user', ['user' => auth()->id()]) }}"
            readonly copyable/>
    </x-filament::section>
</x-filament-panels::page>
