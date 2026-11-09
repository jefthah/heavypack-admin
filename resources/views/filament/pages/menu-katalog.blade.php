{{--
    Daftar Menu katalog (desain A): satu tab per brand, baris ringkas yang langsung bisa diseret.
    Pegangan ⋮⋮ = wire:sort:handle (seret), juga bisa difokus lalu digeser dengan panah atas/bawah.
--}}
<x-filament-panels::page>
    @php
        $bisaUbah = $this->bisaUbah();
        $namaBrand = $this->activeTab === 'heavypro' ? 'Heavypro' : 'Heavypack';
    @endphp

    <section class="hp-mk" aria-label="Kelompok mesin {{ $namaBrand }}">
        @include('filament.tables.list-tabs', [
            'tabs' => $this->tabs(),
            'activeTab' => $this->activeTab,
            'label' => 'Brand di filter website',
            'notice' => $this->peringatan(),
            'hint' => $bisaUbah && $this->kelompok->count() > 1
                ? 'Seret ⋮⋮ untuk mengubah urutan. Tersimpan otomatis dan langsung dipakai website.'
                : null,
        ])

        @if ($this->kelompok->isEmpty())
            <div class="hp-mk-empty">
                <x-filament::icon icon="heroicon-o-bars-3" />
                <h2>Belum ada kelompok {{ $namaBrand }}</h2>
                <p>Tambahkan kelompok baru, atau pindahkan kelompok ke {{ $namaBrand }} lewat kolom Brand di filter di halaman edit.</p>
            </div>
        @else
            <ol class="hp-mk-list" @if ($bisaUbah) wire:sort="urutkan" @endif aria-label="Urutan kelompok {{ $namaBrand }} di filter website">
                @foreach ($this->kelompok as $menu)
                    @php
                        $posisi = $loop->iteration;
                        $nama = trim((string) $menu->menu);
                        $slug = trim((string) $menu->url_menu);
                        $isi = \App\Filament\Resources\MenuResource::isi($menu);
                        $kosong = $isi['jenis'] - $isi['tampil'];
                        $tampil = $isi['tampil'] > 0;
                        $alamatJenis = \App\Filament\Resources\MenuResource::alamatJenis($menu);
                        $alamatEdit = \App\Filament\Resources\MenuResource::getUrl('edit', ['record' => $menu]);
                    @endphp

                    <li class="hp-mk-row" wire:key="kelompok-{{ $menu->id }}" @if ($bisaUbah) wire:sort:item="{{ $menu->id }}" @endif>
                        @if ($bisaUbah)
                            <button
                                type="button"
                                class="hp-mk-grip"
                                wire:sort:handle
                                wire:keydown.arrow-up.prevent="geser({{ $menu->id }}, 'atas')"
                                wire:keydown.arrow-down.prevent="geser({{ $menu->id }}, 'bawah')"
                                aria-label="Pindahkan {{ $nama }}, posisi {{ $posisi }}. Seret, atau tekan panah atas atau bawah."
                                title="Seret untuk mengubah urutan"
                            >
                                <svg viewBox="0 0 16 16" aria-hidden="true"><circle cx="5.5" cy="3.5" r="1.25" /><circle cx="10.5" cy="3.5" r="1.25" /><circle cx="5.5" cy="8" r="1.25" /><circle cx="10.5" cy="8" r="1.25" /><circle cx="5.5" cy="12.5" r="1.25" /><circle cx="10.5" cy="12.5" r="1.25" /></svg>
                            </button>
                        @endif

                        <span class="hp-mk-pos" aria-hidden="true">{{ $posisi }}</span>

                        <div class="hp-mk-name">
                            @if ($bisaUbah)
                                <a href="{{ $alamatEdit }}" class="hp-mk-title">{{ $nama }}</a>
                            @else
                                <span class="hp-mk-title">{{ $nama }}</span>
                            @endif
                            <span class="hp-mk-sub">
                                <span>/mesin/{{ $slug }}</span>
                                @if ($menu->industri)
                                    <span class="hp-menu-chip" title="Juga tampil saat pengunjung memilih Heavypack Industries">Juga di Industries</span>
                                @endif
                            </span>
                        </div>

                        {{-- Isi: jumlah jenis yang tampil di filter website ("3 jenis") dan mesinnya; mengarah ke jenis-jenisnya. --}}
                        @php $metaTag = $alamatJenis ? 'a' : 'span'; @endphp
                        <{{ $metaTag }} class="hp-mk-meta" @if ($alamatJenis) href="{{ $alamatJenis }}" title="Lihat dan susun jenis mesin di kelompok ini" @endif>
                            {{ $isi['tampil'] }} jenis · {{ number_format($isi['mesin'], 0, ',', '.') }} mesin
                            @if ($kosong > 0)
                                <span class="hp-mk-meta__warn">· {{ $kosong }} jenis kosong</span>
                            @endif
                        </{{ $metaTag }}>

                        <span
                            @class(['hp-mk-status', 'hp-mk-status--off' => ! $tampil])
                            title="{{ $tampil ? 'Tampil di filter mesin website' : 'Belum tampil: belum ada jenis mesin di kelompok ini yang berisi mesin' }}"
                        >
                            <x-filament::icon :icon="$tampil ? 'heroicon-m-check-circle' : 'heroicon-m-eye-slash'" />
                            {{ $tampil ? 'Tampil' : 'Tidak tampil' }}
                        </span>

                        <x-filament::dropdown placement="bottom-end" class="hp-mk-more">
                            <x-slot name="trigger">
                                <x-filament::icon-button icon="heroicon-m-ellipsis-horizontal" color="gray" :label="'Aksi untuk '.$nama" />
                            </x-slot>

                            <x-filament::dropdown.list>
                                @if ($bisaUbah)
                                    <x-filament::dropdown.list.item tag="a" :href="$alamatEdit" icon="heroicon-m-pencil-square">
                                        Edit kelompok
                                    </x-filament::dropdown.list.item>
                                @endif

                                @if ($alamatJenis)
                                    <x-filament::dropdown.list.item tag="a" :href="$alamatJenis" icon="heroicon-m-squares-2x2">
                                        Lihat jenis mesin
                                    </x-filament::dropdown.list.item>
                                @endif

                                @if ($slug !== '')
                                    <x-filament::dropdown.list.item tag="a" :href="\App\Filament\Resources\MenuResource::websiteLink('mesin/'.$slug)" target="_blank" icon="heroicon-m-arrow-top-right-on-square">
                                        Lihat di website
                                    </x-filament::dropdown.list.item>
                                @endif

                                @if (\App\Filament\Resources\MenuResource::canDelete($menu))
                                    <x-filament::dropdown.list.item color="danger" icon="heroicon-m-trash" wire:click="mountAction('hapus', { id: {{ $menu->id }} })">
                                        Hapus kelompok
                                    </x-filament::dropdown.list.item>
                                @endif
                            </x-filament::dropdown.list>
                        </x-filament::dropdown>
                    </li>
                @endforeach
            </ol>
        @endif
    </section>
</x-filament-panels::page>
