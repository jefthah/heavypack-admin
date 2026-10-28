<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\CatalogStats;
use App\Filament\Widgets\DashboardHero;
use App\Filament\Widgets\LatestArticles;
use App\Filament\Widgets\LatestComments;
use App\Filament\Widgets\LatestKeywords;
use App\Filament\Widgets\LatestProducts;
use App\Filament\Widgets\LatestSubscribers;
use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Contracts\Support\Htmlable;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationLabel = 'Dashboard';

    /**
     * The hero widget greets the admin, so the default page heading is hidden.
     */
    public function getHeading(): string|Htmlable|null
    {
        return null;
    }

    /**
     * @return array<class-string>
     */
    public function getWidgets(): array
    {
        return [
            DashboardHero::class,
            CatalogStats::class,
            LatestProducts::class,
            LatestArticles::class,
            LatestKeywords::class,
            LatestSubscribers::class,
            LatestComments::class,
        ];
    }

    /**
     * Six columns on wide screens: latest products and articles take half each,
     * keywords, subscribers and comments take a third each.
     *
     * @return int|array<string, int>
     */
    public function getColumns(): int|array
    {
        return ['default' => 1, 'lg' => 2, 'xl' => 6];
    }

    /**
     * @return array<int, string>
     */
    public function getPageClasses(): array
    {
        return ['hp-dashboard'];
    }
}
