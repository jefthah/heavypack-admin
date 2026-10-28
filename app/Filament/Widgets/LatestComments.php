<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\ArtikelResource;
use App\Filament\Resources\KomentarResource;
use App\Filament\Support\FeedWidget;
use App\Models\Artikel;
use App\Models\Komentar;
use Illuminate\Support\Str;

class LatestComments extends FeedWidget
{
    protected int|string|array $columnSpan = ['default' => 'full', 'xl' => 2];

    protected string $view = 'filament.widgets.latest-comments';

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $comments = Komentar::query()->orderByDesc('id')->limit(4)->get();
        $articles = Artikel::query()->whereIn('id', $comments->pluck('id_article')->filter()->unique())->get()->keyBy('id');

        return [
            'total' => $this->formatTotal(Komentar::count()),
            'url' => KomentarResource::getUrl('index'),
            'comments' => $comments->map(function (Komentar $comment) use ($articles): array {
                $article = $articles->get($comment->id_article);
                $text = (string) $comment->isi_komentar;

                return [
                    'name' => filled($comment->name) ? $comment->name : 'Tanpa nama',
                    'avatar' => $this->avatarFor($comment->name),
                    'date' => $this->dateFor($comment->created_at),
                    'excerpt' => Str::limit(Str::squish(html_entity_decode(strip_tags($text))), 140),
                    'hasLink' => (bool) preg_match('~https?://|www\.~i', $text),
                    'article' => $article?->judul,
                    'articleUrl' => $article && ArtikelResource::canEdit($article) ? ArtikelResource::getUrl('edit', ['record' => $article]) : null,
                ];
            })->all(),
        ];
    }
}
