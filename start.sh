#!/usr/bin/env bash
#
# Entry point untuk Pterodactyl / Docker / VPS.
# Pterodactyl menyediakan SERVER_PORT & SERVER_IP secara otomatis.
#
set -euo pipefail

cd "$(dirname "$0")"

PORT="${SERVER_PORT:-${PORT:-${APP_PORT:-8080}}}"
HOST="${SERVER_IP:-0.0.0.0}"

if [ "$HOST" = "127.0.0.1" ] || [ -z "$HOST" ]; then
    HOST="0.0.0.0"
fi

# Folder yang harus writable oleh container.
mkdir -p storage/uploads storage/sessions storage/logs

# Buat .env otomatis pada deploy pertama.
if [ ! -f .env ]; then
    echo "[portfolio] .env belum ada — membuat dari .env.example"
    cp .env.example .env

    if command -v php >/dev/null 2>&1; then
        KEY="$(php -r 'echo bin2hex(random_bytes(32));')"
        # Ganti APP_KEY dan kata sandi owner default dengan nilai acak.
        php -r '
            $file = ".env";
            $content = file_get_contents($file);
            $content = preg_replace("~^APP_KEY=.*$~m", "APP_KEY=" . $argv[1], $content);
            $content = preg_replace("~^OWNER_PASSWORD=.*$~m", "OWNER_PASSWORD=" . $argv[2], $content);
            file_put_contents($file, $content);
        ' "$KEY" "Owner-$(php -r 'echo bin2hex(random_bytes(4));')!"
        echo "[portfolio] Kredensial owner dibuat otomatis, cek baris OWNER_EMAIL & OWNER_PASSWORD di .env"
    fi
fi

# Ambil batas upload dari .env bila tidak disediakan environment.
if [ -z "${MAX_UPLOAD_MB:-}" ] && [ -f .env ]; then
    MAX_UPLOAD_MB="$(grep -E '^MAX_UPLOAD_MB=' .env | tail -n 1 | cut -d '=' -f 2 | tr -d '[:space:]')"
fi

if [ -z "${RELEASE_MAX_MB:-}" ] && [ -f .env ]; then
    RELEASE_MAX_MB="$(grep -E '^RELEASE_MAX_MB=' .env | tail -n 1 | cut -d '=' -f 2 | tr -d '[:space:]')"
fi

if [ -z "${BACKUP_MAX_MB:-}" ] && [ -f .env ]; then
    BACKUP_MAX_MB="$(grep -E '^BACKUP_MAX_MB=' .env | tail -n 1 | cut -d '=' -f 2 | tr -d '[:space:]')"
fi

MAX_MB="${MAX_UPLOAD_MB:-6}"
RELEASE_MB="${RELEASE_MAX_MB:-50}"
BACKUP_MB="${BACKUP_MAX_MB:-200}"

# upload_max_filesize berlaku untuk semua unggahan, jadi pakai batas terbesar.
if [ "${RELEASE_MB}" -gt "${MAX_MB}" ] 2>/dev/null; then
    MAX_MB="${RELEASE_MB}"
fi

if [ "${BACKUP_MB}" -gt "${MAX_MB}" ] 2>/dev/null; then
    MAX_MB="${BACKUP_MB}"
fi

POST_MB=$((MAX_MB + 4))

echo "[portfolio] Menjalankan PHP $(php -r 'echo PHP_VERSION;') di http://${HOST}:${PORT}"
exec php \
    -d display_errors=0 \
    -d log_errors=1 \
    -d upload_max_filesize="${MAX_MB}M" \
    -d post_max_size="${POST_MB}M" \
    -d max_file_uploads=20 \
    -d memory_limit=128M \
    -S "${HOST}:${PORT}" \
    -t public \
    router.php
