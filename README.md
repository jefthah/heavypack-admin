# HeavyPack Admin — Filament

Panel admin pengganti `bawaanAdmin`. Laravel 13.36, Filament 5.10, Livewire 4,
PHP 8.5 di lokal (8.4 di server). Aset panel sudah di-build; tidak perlu
`npm run dev`.

Repo: https://github.com/jefthah/heavypack-admin (private), branch `main`.
Kode awalnya diambil dari server (`/home2/heav0538/heavypack-admin`) pada
28 Okt 2026 karena riwayat git pengembangan awal tidak tersedia. Skrip
pengembangan awal (`SERVE-ADMIN.bat`, `.runtime/`, `.scripts/`, `tests/`) tidak
ikut.

## Menjalankan

Database berjalan sendiri sebagai service Windows (lihat
[Service MariaDB](#service-mariadb)). Yang perlu dijalankan hanya kedua aplikasi:

| Aplikasi | Folder | Perintah | Alamat |
|---|---|---|---|
| Website | `Documents\heavypack\derie26` | `composer74 dev` | http://127.0.0.1:8000 |
| Admin | folder ini | `composer dev` | http://127.0.0.1:8001/admin |

Atau keduanya sekaligus dari VS Code: buka **File → Open Workspace from File…**
→ `Documents\heavypack.code-workspace`, lalu `Ctrl+Shift+P` → **Tasks: Run
Task** → **Heavypack: semua**.

Gambar di panel dimuat dari website, jadi jalankan website juga.

Perintah PHP yang tersedia di PATH: `php` dan `composer` (PHP 8.5, untuk admin),
`php74` dan `composer74` (PHP 7.4, untuk website). Perintah ini berupa file
`.bat`, jadi pakai PowerShell atau cmd, bukan Git Bash. Contoh:

```powershell
php artisan heavypack:verify     # cek koneksi, skema tabel, dan folder media
php artisan optimize:clear       # kosongkan cache config/route/view
```

Runtime-nya (PHP, Composer, MariaDB) ada di
`%LOCALAPPDATA%\Programs\HeavypackDev`.

## Login admin

Email dan password ada di **`.local-admin.txt`** (akun `admin@heavypack.local`,
super admin). Ganti password lewat menu profil di kanan atas.

Kalau lupa, setel ulang dari PowerShell di folder ini:

```powershell
php artisan tinker --execute "App\Models\User::where('email', 'admin@heavypack.local')->first()->update(['password' => 'PasswordBaru123'])"
```

## Database

MariaDB 11.4 di **`127.0.0.1:3307`**. Port 3306 adalah service `MySQL80`, database
lain yang tidak terkait.

| Database | Dipakai oleh | User | Isi |
|---|---|---|---|
| `heavypack` | website dan admin | `heavypack_site` (website), `heavypack_content` (admin) | Katalog, artikel, banner, member, subscriber, pesanan |
| `heavypack_admin` | admin | `heavypack_admin` | `admin_users`, `admin_sessions`, cache, jobs, migrations |
| `heavypack_local` | tidak dipakai | – | Dump bersih dari repo (`database/heavypack-local.sql`) |
| `heavypack_filament_dev` | tidak dipakai | – | Database awal pengembangan panel |

`heavypack` adalah salinan data asli, termasuk data pribadi (1.222 akun member,
2.227 email subscriber, 20 akun `bawaanAdmin` lama). Pakai hanya di lokal;
jangan ekspor atau bagikan dump-nya. Website mengirim email dengan
`MAIL_DRIVER=log`, jadi tidak ada email yang benar-benar terkirim.

Admin memakai dua koneksi: koneksi default (`DB_*`) ke `heavypack_admin` untuk
login dan sesi, dan koneksi `catalog` (`CATALOG_DB_*`) ke `heavypack` untuk
konten. `heavypack_content` hanya punya izin pada tabel yang dikelola panel
(lihat `config/legacy.php`); tabel yang hanya-baca tidak bisa diubah dari panel.

### Melihat password

Password **root** MariaDB:

```powershell
(Get-Content "$env:LOCALAPPDATA\HeavypackDev\private\database.json" | ConvertFrom-Json).rootPassword
```

Password **user aplikasi** ada di `.env` masing-masing:

```powershell
# admin: heavypack_admin dan heavypack_content (jalankan di folder ini)
Select-String -Path .env -Pattern '^(DB|CATALOG_DB)_PASSWORD'

# website: heavypack_site
Select-String -Path "$env:USERPROFILE\Documents\heavypack\derie26\.env" -Pattern '^DB_PASSWORD'
```

Kalau password user aplikasi diganti di MariaDB, perbarui juga `.env`-nya lalu
jalankan `php artisan config:clear` (website: `php74 artisan config:clear`).

### Membuka lewat Navicat

**Connection → MariaDB** (atau MySQL):

| Kolom | Isi |
|---|---|
| Host | `127.0.0.1` |
| Port | **`3307`** |
| User Name | `root` |
| Password | password root di atas |

Perubahan di `heavypack` langsung terlihat di website dan admin. Sebelum
mengubah banyak data, buat cadangan lewat klik kanan database → **Dump SQL
File**.

### Service MariaDB

Service `HeavypackMariaDB` menyala otomatis saat Windows mulai (akun
LocalSystem).

| | Lokasi |
|---|---|
| Konfigurasi | `%LOCALAPPDATA%\HeavypackDev\mariadb.ini` |
| Data | `%LOCALAPPDATA%\HeavypackDev\mariadb-data` |
| Log error | `%LOCALAPPDATA%\HeavypackDev\mariadb.log` |

Cek status dengan `Get-Service HeavypackMariaDB`. Menyalakan, mematikan, atau
menghapus service butuh PowerShell **Administrator**. Akun admin pun tetap perlu
lewat konfirmasi UAC, jadi buka dengan:

```powershell
Start-Process powershell -Verb RunAs
```

Lalu di jendela tersebut: `Start-Service HeavypackMariaDB`,
`Stop-Service HeavypackMariaDB`, atau `sc.exe delete HeavypackMariaDB` (data
tidak ikut terhapus).

## Media

Penyimpanan media bersama adalah folder website, `Documents\heavypack`
(`LEGACY_MEDIA_ROOT`), dengan URL publik `http://127.0.0.1:8000`
(`LEGACY_MEDIA_URL`). Upload sementara tersimpan secara privat di
`storage/app/private/catalog-uploads/`, lalu file tervalidasi dipindahkan ke
lokasi yang dibaca website. Gambar produk mengikuti
`produk-hv/{nama_produk}.jpg`; logo pelanggan mengikuti
`customer-hv/{nama_cust}.png`. Upload dikonversi ke format yang dibaca website
lama. Saat nama mesin/pelanggan berubah, gambar disalin ke nama baru.

Gambar dan PDF yang sudah diterbitkan dapat dibaca pengunjung. Upload sementara
hanya dapat dibuka oleh admin yang sudah login. PDF dapat di-upload pada Katalog
dan Manual Book, atau gunakan tautan unduhan yang sudah tersedia.

Folder media (`produk-hv/`, `customer-hv/`, `article/`, `images/`) diabaikan Git
di repo website, jadi upload dari panel tidak muncul di Source Control.

## Modul dan akses

- Produk mesin, kategori mesin, sparepart.
- Artikel, kategori artikel, menu katalog, keyword.
- Banner utama, banner footer, banner iklan, foto perusahaan.
- Logo pelanggan, katalog, manual book.
- Permintaan layanan, pendaftaran garansi, komentar artikel, subscriber.
- Akun admin baru dan profil/password.

Konten mendukung tambah, lihat, edit, dan hapus. Permintaan layanan/subscriber
hanya dapat dilihat. Garansi/komentar dapat dilihat dan dihapus oleh super admin,
mengikuti fungsi admin lama. Kategori atau artikel yang masih digunakan konten
lain dilindungi dari penghapusan.

**Super admin** mengelola seluruh konten dan akun. **Editor konten** menambah dan
mengedit konten, tanpa menghapus atau mengelola akun. Akun nonaktif tidak dapat
menggunakan panel atau media.

Login menggunakan tabel `admin_users` dan hashing Laravel. Akun MD5 di
`admins` lama tidak digunakan sebagai akun login panel baru.

Pemetaan tabel, kunci utama, formulir dan media ada di `config/legacy.php`.
Komponen bersama ada di `app/Filament/Support/`.

## File penting

| File | Isi |
|---|---|
| `.env` | Konfigurasi lokal (database port 3307, media ke folder website) |
| `.env.server` | `.env` asli dari server panel.heavypack.id. **Berisi kredensial produksi**: jangan dibagikan atau di-commit |
| `.local-admin.txt` | Login admin lokal |
| `Documents\heavypack.code-workspace` | Workspace VS Code berisi website + admin dan task untuk menjalankannya |
| `%LOCALAPPDATA%\HeavypackDev\backups\20261028-103622\` | Cadangan `.env` website dan admin sebelum disetel ke database `heavypack` |

Jangan jalankan `php artisan heavypack:prepare-shared`. Perintah itu dibuat untuk
XAMPP (port 3306, root tanpa password, `C:/xampp`) dan menulis ulang `.env`
website.

## Di server

Panel berjalan di https://panel.heavypack.id/admin. `public_html/panel/index.php`
meneruskan request ke `/home2/heav0538/heavypack-admin/public/index.php`.

### Deploy

Folder `/home2/heav0538/heavypack-admin` di server adalah clone repo ini. Alurnya:

1. Kerjakan perubahan, lalu gabungkan ke `main` dan `git push`.
2. Dalam ±5 menit, Cron Jobs di cPanel menjalankan `scripts/autodeploy.sh`:
   menarik `main` dari GitHub, `composer install --no-dev` (hanya bila
   `composer.json`/`composer.lock` berubah), `migrate`, `filament:assets`,
   `optimize`, lalu menyalin `public/` (css, js, fonts, images) ke
   `public_html/panel`.

Hasil deploy dan error tercatat di `/home2/heav0538/logs/heavypack-admin-deploy.log`
(buka lewat File Manager). Server tidak punya Composer; skrip mengunduh
Composer 2.10.3 sekali ke `~/.heavypack-admin-tools/` dan memeriksa checksum-nya.
PHP yang dipakai: `/opt/cpanel/ea-php84/root/usr/bin/php`.

Server menarik dari GitHub dengan deploy key read-only `~/.ssh/heavypack_admin`
lewat alias `github-heavypack-admin` di `~/.ssh/config`. Key `~/.ssh/id_rsa`
milik repo website; GitHub tidak mengizinkan satu deploy key dipakai dua repo.

Jangan mengubah file di folder server: deploy akan berhenti sampai perubahan itu
dibuang. Yang tidak pernah disentuh deploy: `.env`, `storage/`,
`public_html/panel/index.php` dan `public_html/panel/.htaccess` (berisi handler
PHP 8.4 dari cPanel). File yang dihapus dari `public/` tidak ikut terhapus di
`public_html/panel`.

Saat ini panel di server masih **mode uji coba**: `HEAVYPACK_CONTENT_MODE=testing`,
konten dari database salinan `heav0538_hpuji`, dan media di
`storage/app/legacy-media` milik panel sendiri. Perubahan di panel server
**tidak** sampai ke heavypack.id; website live masih dikelola lewat
`bawaanAdmin`.

Produksi memerlukan document root `public/`, PHP yang memenuhi
`composer.lock`, `APP_ENV=production`, `APP_DEBUG=false`, URL HTTPS,
kredensial tersendiri, dan media publik sesuai website. Atur
`LEGACY_MEDIA_ROOT` dan `LEGACY_MEDIA_URL` untuk penyimpanan/URL media.
Direktori sumber diblokir oleh `.htaccess`.
