<x-filament-widgets::widget>
    <x-hp.feed-card
        title="Komentar terbaru"
        description="Komentar pengunjung pada artikel blog."
        icon="heroicon-o-chat-bubble-left-right"
        :total="$total"
        total-label="komentar"
        :url="$url"
        empty="Belum ada komentar"
        :is-empty="blank($comments)"
    >
        @foreach ($comments as $comment)
            <x-hp.feed-item top>
                <span class="hp-feed__avatar hp-avatar--{{ $comment['avatar']['tone'] }}" aria-hidden="true">
                    {{ $comment['avatar']['initial'] }}
                </span>

                <span class="hp-feed__body">
                    <span class="hp-feed__line">
                        <span class="hp-feed__primary" title="{{ $comment['name'] }}">{{ $comment['name'] }}</span>

                        @if ($comment['date']['relative'])
                            <time
                                class="hp-feed__time"
                                datetime="{{ $comment['date']['iso'] }}"
                                title="{{ $comment['date']['full'] }}"
                            >
                                {{ $comment['date']['relative'] }}
                            </time>
                        @endif
                    </span>

                    @if (filled($comment['excerpt']))
                        <span class="hp-feed__excerpt">{{ $comment['excerpt'] }}</span>
                    @endif

                    @if ($comment['hasLink'] || filled($comment['article']))
                        <span class="hp-feed__context">
                            @if ($comment['hasLink'])
                                <span class="hp-chip hp-chip--warning" title="Komentar berisi link. Periksa apakah ini spam.">
                                    Berisi link
                                </span>
                            @endif

                            @if (filled($comment['article']))
                                @if ($comment['articleUrl'])
                                    <a href="{{ $comment['articleUrl'] }}" class="hp-feed__context-link" title="{{ $comment['article'] }}">
                                        {{ \Illuminate\Support\Str::limit($comment['article'], 48) }}
                                    </a>
                                @else
                                    <span title="{{ $comment['article'] }}">{{ \Illuminate\Support\Str::limit($comment['article'], 48) }}</span>
                                @endif
                            @endif
                        </span>
                    @endif
                </span>
            </x-hp.feed-item>
        @endforeach
    </x-hp.feed-card>
</x-filament-widgets::widget>
