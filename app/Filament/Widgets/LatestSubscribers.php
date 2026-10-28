<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\SubscriberResource;
use App\Filament\Support\FeedWidget;
use App\Models\Subscriber;

class LatestSubscribers extends FeedWidget
{
    protected int|string|array $columnSpan = ['default' => 'full', 'lg' => 1, 'xl' => 2];

    protected string $view = 'filament.widgets.latest-subscribers';

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        return [
            'total' => $this->formatTotal(Subscriber::count()),
            'url' => SubscriberResource::getUrl('index'),
            'subscribers' => Subscriber::query()->orderByDesc('id')->limit(6)->get()
                ->map(fn (Subscriber $subscriber): array => [
                    'email' => $subscriber->email,
                    'avatar' => $this->avatarFor($subscriber->email),
                    'date' => $this->dateFor($subscriber->created_at),
                ])
                ->all(),
        ];
    }
}
