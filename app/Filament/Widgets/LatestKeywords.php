<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\KeywordResource;
use App\Filament\Support\FeedWidget;
use App\Models\Kategori;
use App\Models\Keyword;
use Illuminate\Support\Str;

class LatestKeywords extends FeedWidget
{
    protected int|string|array $columnSpan = ['default' => 'full', 'lg' => 1, 'xl' => 2];

    protected string $view = 'filament.widgets.latest-keywords';

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        // The website only reads keywords whose category is "index" or a machine category URL.
        $websiteCategories = Kategori::query()->distinct()->pluck('url_kategori')->all();

        return [
            'total' => $this->formatTotal(Keyword::count()),
            'url' => KeywordResource::getUrl('index'),
            'keywords' => Keyword::query()->orderByDesc('id')->limit(6)->get()
                ->map(fn (Keyword $keyword): array => [
                    'word' => $keyword->word,
                    'page' => $keyword->kategori === 'index' ? 'Beranda' : Str::ucfirst(str_replace('-', ' ', (string) $keyword->kategori)),
                    'isUsed' => $keyword->kategori === 'index' || in_array($keyword->kategori, $websiteCategories, true),
                    'url' => KeywordResource::canEdit($keyword) ? KeywordResource::getUrl('edit', ['record' => $keyword]) : null,
                ])
                ->all(),
        ];
    }
}
