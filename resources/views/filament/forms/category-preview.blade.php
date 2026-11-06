<div class="hp-preview">
    <div class="hp-serp" role="group" aria-label="Pratinjau hasil pencarian Google">
        <div class="hp-serp__site">
            <span class="hp-serp__favicon" aria-hidden="true">
                <img src="{{ asset('images/heavypack-favicon.ico') }}" alt="">
            </span>

            <span class="hp-serp__source">
                <span class="hp-serp__name">Heavypack Indonesia</span>
                <span class="hp-serp__url">{{ $host }} › mesin › {{ filled($group ?? null) ? $group : '…' }} › {{ filled($slug) ? $slug : '…' }}</span>
            </span>
        </div>

        <p @class(['hp-serp__title', 'hp-preview__missing' => blank($title)])>
            {{ $title ?? 'Judul halaman belum diisi' }}
        </p>

        <p @class(['hp-serp__snippet', 'hp-preview__missing' => blank($snippet)])>
            {{ $snippet ?? 'Deskripsi belum diisi, jadi Google memilih teks sendiri dari halaman.' }}
        </p>
    </div>

    <div class="hp-preview__intro">
        <span class="hp-preview__label">Teks pembuka di halaman kategori</span>

        <p @class(['hp-preview__missing' => blank($intro)])>
            {{ $intro ?? 'Deskripsi belum diisi.' }}
        </p>
    </div>

    @if (filled($url))
        <a href="{{ $url }}" target="_blank" rel="noopener" class="hp-preview__link">
            Buka halaman di website
            <x-filament::icon icon="heroicon-m-arrow-top-right-on-square" />
        </a>
    @else
        <p class="hp-preview__hint">Isi slug kategori agar halaman ini punya alamat di website.</p>
    @endif
</div>
