<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\ArtikelResource;
use App\Filament\Resources\ProdukResource;
use App\Filament\Resources\SparepartResource;
use Filament\Widgets\Widget;

class DashboardHero extends Widget
{
    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.widgets.dashboard-hero';

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $now = now();

        return [
            'greeting' => $this->greetingFor($now->hour),
            'name' => auth()->user()?->name,
            'date' => $now->translatedFormat('l, j F Y'),
            'environmentLabel' => config('heavypack.environment_label'),
            'isSharedCatalog' => config('heavypack.content_mode') === 'shared',
            'actions' => $this->getQuickActions(),
        ];
    }

    private function greetingFor(int $hour): string
    {
        return match (true) {
            $hour < 11 => 'Selamat pagi',
            $hour < 15 => 'Selamat siang',
            $hour < 18 => 'Selamat sore',
            default => 'Selamat malam',
        };
    }

    /**
     * @return array<int, array{label: string, icon: string, url: string, primary: bool}>
     */
    private function getQuickActions(): array
    {
        $actions = [
            ['resource' => ProdukResource::class, 'label' => 'Tambah produk', 'icon' => 'heroicon-m-plus', 'primary' => true],
            ['resource' => SparepartResource::class, 'label' => 'Tambah sparepart', 'icon' => 'heroicon-m-wrench-screwdriver', 'primary' => false],
            ['resource' => ArtikelResource::class, 'label' => 'Tulis artikel', 'icon' => 'heroicon-m-pencil-square', 'primary' => false],
        ];

        return collect($actions)
            ->filter(fn (array $action): bool => $action['resource']::canCreate())
            ->map(fn (array $action): array => [
                'label' => $action['label'],
                'icon' => $action['icon'],
                'url' => $action['resource']::getUrl('create'),
                'primary' => $action['primary'],
            ])
            ->values()
            ->all();
    }
}
