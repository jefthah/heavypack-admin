<x-filament-widgets::widget>
    <x-hp.feed-card
        title="Keyword terbaru"
        description="Kata kunci SEO untuk halaman website."
        icon="heroicon-o-hashtag"
        :total="$total"
        total-label="keyword"
        :url="$url"
        empty="Belum ada keyword"
        :is-empty="blank($keywords)"
    >
        @foreach ($keywords as $keyword)
            <x-hp.feed-item :url="$keyword['url']">
                <span class="hp-feed__avatar hp-feed__avatar--icon hp-avatar--stone" aria-hidden="true">
                    <x-filament::icon icon="heroicon-m-hashtag" />
                </span>

                <span class="hp-feed__body">
                    <span class="hp-feed__primary" title="{{ $keyword['word'] }}">{{ $keyword['word'] }}</span>

                    <span class="hp-feed__secondary">
                        <x-filament::icon icon="heroicon-m-document-text" />
                        {{ $keyword['page'] }}

                        @unless ($keyword['isUsed'])
                            <span
                                class="hp-chip hp-chip--warning"
                                title="Kategori ini tidak cocok dengan halaman mana pun di website, jadi keyword ini tidak ditampilkan."
                            >
                                Tidak dipakai website
                            </span>
                        @endunless
                    </span>
                </span>
            </x-hp.feed-item>
        @endforeach
    </x-hp.feed-card>
</x-filament-widgets::widget>
