#!/bin/bash
# Deploy otomatis HeavyPack Admin (panel.heavypack.id) lewat Cron Jobs cPanel.
#
# Polanya sama dengan scripts/autodeploy.sh di repo website: hosting tidak memberi
# akses shell, jadi cron yang menjalankan git. Bedanya, folder aplikasi di server
# (/home2/heav0538/heavypack-admin, di luar public_html) adalah clone repo ini
# sendiri, sehingga kode langsung terpakai setelah git maju ke commit baru.
#
#   1. git fetch branch main dari GitHub (deploy key read-only lewat alias
#      github-heavypack-admin di ~/.ssh/config).
#   2. Bila ada commit baru, maju (fast-forward) ke commit itu.
#   3. Bila commit yang aktif belum pernah di-deploy:
#      - composer install --no-dev, hanya bila composer.json/lock berubah atau
#        vendor/ belum ada (Composer diunduh sekali, versi dan checksum dikunci);
#      - migrate, publikasi aset Filament, cache config/route/view;
#      - salin aset public/ ke public_html/panel, kecuali yang di sana sudah
#        berupa symlink ke public/.
#
# Cron (cPanel -> Cron Jobs, tiap 5 menit):
#   cd /home2/heav0538/heavypack-admin && git pull -q --ff-only origin main >/dev/null 2>&1; /bin/bash scripts/autodeploy.sh >/dev/null 2>&1
#
# Yang tidak disentuh deploy (dikelola langsung di server): .env, storage/,
# public_html/panel/index.php (meneruskan ke public/index.php folder ini) dan
# public_html/panel/.htaccess (berisi handler PHP 8.4 dari cPanel).
#
# Log hanya ditulis saat ada deploy atau error:
#   /home2/heav0538/logs/heavypack-admin-deploy.log
#
# Untuk uji coba: HEAVYPACK_ADMIN_PHP mengganti PHP, HEAVYPACK_PANEL_PATH mengganti
# tujuan aset, HEAVYPACK_STATE_DIR mengganti folder log/penanda, HEAVYPACK_BRANCH
# mengganti branch.

COMPOSER_VERSION=2.10.3
COMPOSER_SHA256=7a2d379d5b8ffdaa028580ef26494c36d2feef4b178d3dd1473a4dbc5e17c8d6

# Server tidak punya Composer; unduh sekali lalu simpan di luar repo.
ensure_composer() {
    [ -f "$composer" ] && return 0
    mkdir -p "$(dirname "$composer")"
    # Lewat ekstensi curl, karena copy() butuh allow_url_fopen yang belum tentu aktif.
    "$php" -r '$f = fopen($argv[2], "w"); $c = curl_init($argv[1]);
        curl_setopt_array($c, [CURLOPT_FILE => $f, CURLOPT_FOLLOWLOCATION => true, CURLOPT_FAILONERROR => true]);
        $ok = curl_exec($c); fclose($f);
        if (! $ok) { fwrite(STDERR, "unduh composer.phar gagal: ".curl_error($c)."\n"); exit(1); }' \
        "https://getcomposer.org/download/$COMPOSER_VERSION/composer.phar" "$composer.tmp" || return 1
    if [ "$("$php" -r 'echo hash_file("sha256", $argv[1]);' "$composer.tmp")" != "$COMPOSER_SHA256" ]; then
        rm -f "$composer.tmp"
        echo "checksum composer.phar tidak cocok"
        return 1
    fi
    mv "$composer.tmp" "$composer"
}

# Di server, css/js/fonts/images di public_html/panel adalah symlink ke public/
# folder ini, jadi sudah ikut terbarui; hanya yang bukan symlink yang disalin.
sync_panel() {
    local item
    [ -d "$panel" ] || { echo "folder $panel tidak ada"; return 1; }
    for item in css js fonts images favicon.ico robots.txt; do
        [ "$(readlink -f "$panel/$item")" = "$(readlink -f "public/$item")" ] && continue
        /bin/cp -R "public/$item" "$panel"/ || return 1
    done
}

# Dipanggil dari main; memakai variabel php, panel, composer dan deployed miliknya.
# Sengaja tanpa set -e: di dalam kondisi if, bash mengabaikannya.
deploy_steps() {
    if [ ! -f vendor/autoload.php ] || [ -z "$deployed" ] \
        || ! git diff --quiet "$deployed" HEAD -- composer.json composer.lock 2>/dev/null; then
        ensure_composer || return 1
        "$php" -d memory_limit=1536M "$composer" install --no-dev --no-interaction \
            --prefer-dist --optimize-autoloader --no-progress --no-ansi || return 1
    fi
    "$php" artisan migrate --force --no-interaction --no-ansi \
        && "$php" artisan filament:assets --no-ansi \
        && "$php" artisan optimize --no-ansi \
        && sync_panel
}

main() {
    local repo state_dir log_file state_file lock_dir composer branch="${HEAVYPACK_BRANCH:-main}"
    local php="${HEAVYPACK_ADMIN_PHP:-/opt/cpanel/ea-php84/root/usr/bin/php}"
    local panel="${HEAVYPACK_PANEL_PATH:-$HOME/public_html/panel}"
    repo="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
    state_dir="${HEAVYPACK_STATE_DIR:-$HOME}"
    log_file="$state_dir/logs/heavypack-admin-deploy.log"
    state_file="$state_dir/.heavypack-admin-deployed-commit"
    lock_dir="$state_dir/.heavypack-admin-deploy.lock"
    composer="$state_dir/.heavypack-admin-tools/composer-$COMPOSER_VERSION.phar"

    mkdir -p "$state_dir/logs"

    log() { echo "$(date '+%Y-%m-%d %H:%M:%S') $*" >> "$log_file"; }

    # Cegah dua proses jalan bersamaan; kunci yang tertinggal > 30 menit dianggap basi.
    if ! mkdir "$lock_dir" 2>/dev/null; then
        if [ -n "$(find "$lock_dir" -maxdepth 0 -mmin +30 2>/dev/null)" ]; then
            rmdir "$lock_dir" 2>/dev/null
            mkdir "$lock_dir" 2>/dev/null || return 0
            log "PERINGATAN: kunci basi dihapus"
        else
            return 0
        fi
    fi
    # Path kunci di-expand sekarang: saat EXIT, variabel lokal fungsi ini sudah hilang.
    trap "rmdir \"$lock_dir\" 2>/dev/null" EXIT

    cd "$repo" || { log "GAGAL: folder repo $repo tidak ada"; return 1; }

    if [ "$(git rev-parse --abbrev-ref HEAD)" != "$branch" ]; then
        log "GAGAL: branch aktif bukan $branch"; return 1
    fi
    # Hanya file yang dilacak git: file liar seperti error_log buatan PHP cPanel
    # tidak boleh menghentikan deploy, tetapi edit manual pada kode harus.
    if [ -n "$(git status --porcelain --untracked-files=no)" ]; then
        log "GAGAL: ada perubahan lokal di folder server, deploy dibatalkan"
        git status --porcelain --untracked-files=no >> "$log_file"
        return 1
    fi

    local fetch_output
    if ! fetch_output="$(git fetch -q origin "$branch" 2>&1)"; then
        log "GAGAL: git fetch: $fetch_output"; return 1
    fi

    if [ "$(git rev-parse HEAD)" != "$(git rev-parse "origin/$branch")" ]; then
        local merge_output
        if ! merge_output="$(git merge --ff-only -q "origin/$branch" 2>&1)"; then
            log "GAGAL: tidak bisa fast-forward ke origin/$branch: $merge_output"; return 1
        fi
    fi

    local head deployed
    head="$(git rev-parse HEAD)"
    deployed="$(cat "$state_file" 2>/dev/null)"
    [ "$head" = "$deployed" ] && return 0

    log "MULAI deploy $(git log -1 --oneline)"
    local task_output
    if task_output="$(deploy_steps 2>&1)"; then
        echo "$head" > "$state_file"
        log "SELESAI deploy $(git rev-parse --short HEAD)"
    else
        log "GAGAL: langkah deploy error, commit $(git rev-parse --short HEAD) akan dicoba lagi di putaran cron berikutnya"
        echo "$task_output" >> "$log_file"
    fi
}

main "$@"; exit $?
