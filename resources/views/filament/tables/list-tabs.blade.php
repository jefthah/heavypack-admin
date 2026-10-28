<div class="hp-table-tabs">
    <x-filament::tabs contained :label="$label">
        @foreach ($tabs as $key => $tab)
            <x-filament::tabs.item
                :active="$activeTab === (string) $key"
                :badge="$tab->getBadge()"
                wire:click="$set('activeTab', '{{ $key }}')"
                wire:loading.attr="disabled"
            >
                {{ $tab->getLabel() }}
            </x-filament::tabs.item>
        @endforeach
    </x-filament::tabs>

    @if (filled($notice ?? null))
        <p class="hp-table-notice" role="note">
            <x-filament::icon icon="heroicon-m-exclamation-triangle" />
            <span>{{ $notice }}</span>
        </p>
    @endif
</div>
