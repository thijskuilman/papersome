<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">
            Feed
        </x-slot>

        <flux:input label="URL" value="{{ route('opds.user', ['user' => auth()->id()]) }}" readonly copyable />
    </x-filament::section>
</x-filament-panels::page>
