{{-- Pratinjau kategori: hasil Google (judul + 155 karakter) dan halaman kategori di website
     (kelompok, nama, ringkasan 180 karakter, lalu teks lengkap di "Tentang …"), dipotong seperti aslinya. --}}
<div class="hp-preview">
    <div class="hp-preview__block">
        <span class="hp-preview__label">
            <x-filament::icon icon="heroicon-m-magnifying-glass" />
            Hasil di Google
        </span>

        <div class="hp-serp" role="group" aria-label="Pratinjau hasil pencarian Google">
            <div class="hp-serp__site">
                <span class="hp-serp__favicon" aria-hidden="true">
                    <img src="{{ asset('images/heavypack-favicon.ico') }}" alt="">
                </span>

                <span class="hp-serp__source">
                    <span class="hp-serp__name">Heavypack Indonesia</span>
                    <span class="hp-serp__url">{{ $host }} › mesin › {{ filled($group) ? $group : '…' }} › {{ filled($slug) ? $slug : '…' }}</span>
                </span>
            </div>

            <p @class(['hp-serp__title', 'hp-preview__missing' => blank($title)])>
                {{ $title ?? 'Judul halaman belum diisi' }}
            </p>

            <p @class(['hp-serp__snippet', 'hp-preview__missing' => blank($snippet)])>
                {{ $snippet ?? 'Deskripsi belum diisi, jadi Google memilih teks sendiri dari halaman.' }}
            </p>
        </div>
    </div>

    <div class="hp-preview__block">
        <span class="hp-preview__label">
            <x-filament::icon icon="heroicon-m-computer-desktop" />
            Halaman di website
        </span>

        <div class="hp-site" role="group" aria-label="Pratinjau halaman kategori di website">
            <div class="hp-site__hero">
                <span class="hp-site__kicker">{{ $groupName ?? 'Kelompok belum dipilih' }}</span>
                <p @class(['hp-site__name', 'hp-preview__missing' => blank($name)])>{{ filled($name) ? $name : 'Nama kategori' }}</p>
                <p @class(['hp-site__intro', 'hp-preview__missing' => blank($intro)])>{{ $intro ?? 'Deskripsi belum diisi.' }}</p>
            </div>

            <div class="hp-site__machines" aria-hidden="true">
                <span></span><span></span><span></span>
                <small>Daftar mesin</small>
            </div>

            @if (filled($about))
                <div class="hp-site__about">
                    <p class="hp-site__about-title">Tentang {{ filled($name) ? $name : '…' }}</p>
                    <div class="hp-site__about-body">{!! $about !!}</div>
                </div>
            @elseif (filled($intro))
                <p class="hp-site__short">Teks sependek ini cukup tampil sebagai ringkasan di atas. Bagian "Tentang …" muncul bila lebih dari ±180 karakter.</p>
            @endif
        </div>
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
