<?php

namespace App\Filament\Resources\MenuResource\Pages;

use App\Filament\Resources\KategoriResource;
use App\Filament\Resources\MenuResource;
use App\Models\Menu;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;

/**
 * Daftar Menu katalog: satu tab per brand (Heavypack, Heavypro), baris kelompok yang langsung bisa
 * diseret (wire:sort) atau dipindah dengan tombol panah, dan tiap perubahan langsung tersimpan.
 * Urutan dihitung per brand, sama seperti filter mesin di website (MachineDirectory: urutan, lalu id).
 */
class ListMenu extends Page
{
    protected static string $resource = MenuResource::class;

    protected string $view = 'filament.pages.menu-katalog';

    /** Brand yang sedang dibuka; diingat di alamat (?tab=heavypro). */
    #[Url(as: 'tab')]
    public string $activeTab = 'heavypack';

    public function mount(): void
    {
        if (! in_array($this->activeTab, ['heavypack', 'heavypro'], true)) {
            $this->activeTab = 'heavypack';
        }
    }

    public function getTitle(): string
    {
        return 'Menu katalog';
    }

    public function getBreadcrumb(): ?string
    {
        return 'Daftar';
    }

    public function getPageClasses(): array
    {
        return ['hp-list', 'hp-menus'];
    }

    public function updatedActiveTab(): void
    {
        $this->mount();
        unset($this->kelompok);
    }

    /** Boleh mengubah urutan, mengedit, dan menambah: sama dengan hak edit di panel. */
    public function bisaUbah(): bool
    {
        return MenuResource::canCreate();
    }

    /**
     * Kelompok di tab aktif, berurutan seperti di website.
     *
     * @return Collection<int, Menu>
     */
    #[Computed]
    public function kelompok(): Collection
    {
        return MenuResource::kelompokBrand($this->activeTab)->get();
    }

    /**
     * Tab brand untuk tampilan tab bersama (filament.tables.list-tabs).
     *
     * @return array<string, Tab>
     */
    public function tabs(): array
    {
        $heavypro = Menu::query()->where('brand', 'heavypro')->count();

        return [
            'heavypack' => Tab::make('Heavypack')->badge(Menu::query()->count() - $heavypro),
            'heavypro' => Tab::make('Heavypro')->badge($heavypro),
        ];
    }

    /** Peringatan di bawah tab bila ada kelompok di brand ini yang belum tampil di filter website. */
    public function peringatan(): ?string
    {
        $jumlah = $this->kelompok->reject(fn (Menu $menu): bool => MenuResource::tampil($menu))->count();

        return $jumlah > 0
            ? $jumlah.' kelompok belum tampil di website karena belum ada jenis mesin di dalamnya yang berisi mesin.'
            : null;
    }

    // ---- Dipanggil dari tampilan (wire:sort dan tombol panah) ----

    /** wire:sort: kelompok $id dilepas di posisi $posisi (0 = paling atas) dalam brand-nya. */
    public function urutkan(mixed $id, mixed $posisi): void
    {
        abort_unless($this->bisaUbah(), 403);

        $menu = Menu::query()->find((int) $id);
        if (! $menu) {
            return;
        }

        $brand = $menu->brand === 'heavypro' ? 'heavypro' : 'heavypack';
        $ids = MenuResource::kelompokBrand($brand)->whereKeyNot($menu->getKey())->pluck('id')->all();
        array_splice($ids, max(0, min((int) $posisi, count($ids))), 0, [$menu->getKey()]);

        DB::connection($menu->getConnectionName())->transaction(function () use ($ids): void {
            foreach ($ids as $index => $kelompokId) {
                Menu::query()->whereKey($kelompokId)->update(['urutan' => $index + 1]);
            }
        });

        unset($this->kelompok);

        Notification::make()
            ->title('Urutan disimpan')
            ->body('Website langsung memakai urutan baru.')
            ->success()
            ->send();
    }

    /** Tombol panah (keyboard) pada pegangan: geser satu posisi ke atas atau ke bawah. */
    public function geser(mixed $id, string $arah): void
    {
        $posisi = $this->kelompok->search(fn (Menu $menu): bool => $menu->getKey() === (int) $id);
        if ($posisi === false) {
            return;
        }

        $tujuan = $arah === 'atas' ? $posisi - 1 : $posisi + 1;
        if ($tujuan < 0 || $tujuan >= $this->kelompok->count()) {
            return;
        }

        $this->urutkan($id, $tujuan);
    }

    /** "Hapus kelompok" di menu ⋯; hanya untuk kelompok yang boleh dihapus (lihat LegacyResource::canDelete). */
    public function hapusAction(): Action
    {
        return Action::make('hapus')
            ->label('Hapus kelompok')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading(fn (array $arguments): string => 'Hapus '.trim((string) Menu::query()->find($arguments['id'] ?? 0)?->menu).'?')
            ->modalDescription('Kelompok ini hilang dari filter website. Kelompok yang masih dipakai kategori mesin tidak dapat dihapus.')
            ->modalSubmitActionLabel('Hapus kelompok')
            ->action(function (array $arguments): void {
                $menu = Menu::query()->find($arguments['id'] ?? 0);
                if (! $menu || ! MenuResource::canDelete($menu)) {
                    Notification::make()->title('Kelompok tidak dapat dihapus')->body('Kelompok ini masih dipakai kategori mesin.')->danger()->send();

                    return;
                }

                $menu->delete();
                unset($this->kelompok);
                Notification::make()->title('Kelompok dihapus')->success()->send();
            });
    }

    protected function getHeaderActions(): array
    {
        return $this->bisaUbah()
            ? [Action::make('tambah')->label('Tambah kelompok')->icon('heroicon-m-plus')->url(MenuResource::getUrl('create'))]
            : [];
    }

    /**
     * Kartu ringkasan seperti di daftar Kategori mesin: jumlah kelompok (dan yang tampil di website),
     * kelompok per brand dengan tautan ke tab-nya, dan jenis mesin di dalamnya.
     */
    public function getHeader(): ?View
    {
        $kelompok = Menu::query()->get(['id', 'url_menu', 'brand']);
        $tampil = $kelompok->filter(fn (Menu $menu): bool => MenuResource::tampil($menu))->count();
        $heavypro = $kelompok->where('brand', 'heavypro')->count();
        $jenis = $kelompok->sum(fn (Menu $menu): int => MenuResource::isi($menu)['jenis']);
        $jenisTampil = $kelompok->sum(fn (Menu $menu): int => MenuResource::isi($menu)['tampil']);
        $angka = fn (int $jumlah): string => number_format($jumlah, 0, ',', '.');

        return view('filament.menu-header', [
            'metrics' => [
                [
                    'label' => 'Total kelompok',
                    'value' => $angka($kelompok->count()),
                    'unit' => 'kelompok',
                    'icon' => 'heroicon-o-bars-3',
                    'primary' => true,
                    'note' => $angka($tampil).' tampil di filter website',
                    'warning' => $kelompok->count() > $tampil ? $angka($kelompok->count() - $tampil).' belum tampil' : null,
                ],
                [
                    'label' => 'Heavypack',
                    'value' => $angka($kelompok->count() - $heavypro),
                    'unit' => 'kelompok',
                    'icon' => 'heroicon-o-cube',
                    'note' => 'Mesin kemasan',
                    'url' => MenuResource::getUrl('index', ['tab' => 'heavypack']),
                    'linkLabel' => 'Atur urutan',
                ],
                [
                    'label' => 'Heavypro',
                    'value' => $angka($heavypro),
                    'unit' => 'kelompok',
                    'icon' => 'heroicon-o-fire',
                    'note' => 'Pengolahan makanan',
                    'url' => MenuResource::getUrl('index', ['tab' => 'heavypro']),
                    'linkLabel' => 'Atur urutan',
                ],
                [
                    'label' => 'Jenis mesin',
                    'value' => $angka($jenis),
                    'unit' => 'jenis',
                    'icon' => 'heroicon-o-squares-2x2',
                    'note' => $angka($jenisTampil).' sudah berisi mesin',
                    'url' => KategoriResource::getUrl('index'),
                    'linkLabel' => 'Lihat jenis',
                ],
            ],
        ]);
    }
}
