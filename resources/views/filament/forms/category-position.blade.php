{{-- Pengganti kolom "Urutan di kelompok": posisi kategori di filter website, diatur dengan menyeret di daftar. --}}
<div class="hp-position">
    <span class="hp-position__icon" aria-hidden="true">
        <x-filament::icon icon="heroicon-m-bars-arrow-down" />
    </span>

    <div class="hp-position__body">
        @switch($mode)
            @case('current')
                <p class="hp-position__text">
                    Urutan ke-<b>{{ $position }}</b> dari {{ $total }} jenis di <b>{{ $groupName }}</b>.
                </p>
                <p class="hp-position__note">
                    @if (! $hasProducts)
                        Belum tampil di filter website karena belum ada produk.
                    @else
                        Urutan tombol jenis mesin di filter website.
                    @endif
                </p>
                @break

            @case('move')
                <p class="hp-position__text">
                    Setelah disimpan, pindah ke urutan terakhir (ke-<b>{{ $position }}</b>) di <b>{{ $groupName }}</b>.
                </p>
                <p class="hp-position__note">Urutannya bisa diatur lagi di daftar Kategori mesin.</p>
                @break

            @case('new')
                <p class="hp-position__text">
                    Kategori baru ditaruh paling akhir (ke-<b>{{ $position }}</b>) di <b>{{ $groupName }}</b>.
                </p>
                <p class="hp-position__note">Urutannya bisa diatur setelah disimpan.</p>
                @break

            @default
                <p class="hp-position__text">Pilih kelompok dulu. Kategori ditaruh paling akhir di kelompoknya.</p>
        @endswitch
    </div>

    @if (filled($url ?? null))
        <a href="{{ $url }}" class="hp-position__link">
            Atur urutan
            <x-filament::icon icon="heroicon-m-arrow-right" />
        </a>
    @endif
</div>
