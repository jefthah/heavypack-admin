<x-filament-widgets::widget>
    <x-hp.feed-card
        title="Subscriber terbaru"
        description="Email yang mendaftar newsletter di website."
        icon="heroicon-o-envelope"
        :total="$total"
        total-label="subscriber"
        :url="$url"
        empty="Belum ada subscriber"
        :is-empty="blank($subscribers)"
    >
        @foreach ($subscribers as $subscriber)
            <x-hp.feed-item>
                <span class="hp-feed__avatar hp-avatar--{{ $subscriber['avatar']['tone'] }}" aria-hidden="true">
                    {{ $subscriber['avatar']['initial'] }}
                </span>

                <span class="hp-feed__body">
                    <span class="hp-feed__primary" title="{{ $subscriber['email'] }}">{{ $subscriber['email'] }}</span>

                    @if ($subscriber['date']['relative'])
                        <time
                            class="hp-feed__secondary"
                            datetime="{{ $subscriber['date']['iso'] }}"
                            title="{{ $subscriber['date']['full'] }}"
                        >
                            Mendaftar {{ $subscriber['date']['relative'] }}
                        </time>
                    @endif
                </span>
            </x-hp.feed-item>
        @endforeach
    </x-hp.feed-card>
</x-filament-widgets::widget>
