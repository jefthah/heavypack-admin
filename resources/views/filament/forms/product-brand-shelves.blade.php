{{--
    Rak brand di halaman /mesin website yang memuat mesin ini (ProdukResource::rakWebsite), di bawah
    Kategori dan Merek pada form Produk mesin. Heavypack Industries bukan merek, melainkan rak berisi
    mesin Heavypack dari kelompok yang dicentang "Juga di Heavypack Industries" di Menu katalog.
--}}
<div class="hp-shelves">
    @if ($rak !== null)
        <div class="hp-shelves__row">
            <span class="hp-shelves__label">Tampil di website, rak brand:</span>
            @forelse ($rak as $kunci => $nama)
                <span class="hp-pill hp-pill--brand hp-pill--{{ $kunci }}">{{ $nama }}</span>
            @empty
                <span class="hp-shelves__none">tidak ada (hanya di "Semua mesin" dan halaman kategorinya)</span>
            @endforelse
        </div>
    @endif

    @foreach ($catatan as $teks)
        <p class="hp-shelves__note">{{ $teks }}</p>
    @endforeach

    @if ($peringatan)
        <p class="hp-shelves__warning" role="alert">
            <x-filament::icon icon="heroicon-m-exclamation-triangle" />
            <span>{{ $peringatan }}</span>
        </p>
    @endif
</div>
