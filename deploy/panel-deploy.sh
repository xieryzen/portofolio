#!/usr/bin/env bash
#
# panel-deploy.sh — deploy / update aplikasi ke server Pterodactyl TANPA SSH,
# memakai **Client API** panel (upload ZIP + decompress + restart).
#
# Kenapa lewat API? Karena tidak semua lingkungan punya akses SSH ke VPS.
# Cukup buat satu API key di panel, lalu jalankan skrip ini dari komputer mana pun.
#
# Pemakaian:
#   export PTERO_PANEL_URL="https://panel.hexnitystore.com"
#   export PTERO_SERVER_ID="<uuid server>"
#   export PTERO_API_KEY="<api key panel>"
#   bash deploy/panel-deploy.sh --dry-run          # lihat rencana, tanpa menyentuh server
#   bash deploy/panel-deploy.sh                    # backup + upload + restart
#   bash deploy/panel-deploy.sh --tag v1.8.2 --no-backup
#   bash deploy/panel-deploy.sh --package-url https://contoh/portofolio-v1.8.2.zip
#
# Membuat API key: panel → klik foto profil → **API Credentials** → *Create API Key*
#   (permission: read & write). Simpan di password manager; jangan commit ke repo.
#
# Aman untuk data: ZIP rilis tidak memuat `storage/` dan `.env`, jadi database,
# gambar, file rilis, dan konfigurasi server tidak tertimpa.
#
set -uo pipefail

PANEL_URL="${PTERO_PANEL_URL:-https://panel.hexnitystore.com}"
SERVER_ID="${PTERO_SERVER_ID:-}"
API_KEY="${PTERO_API_KEY:-}"
REPO="${PTERO_REPO:-xieryzen/portofolio}"
TAG="${PTERO_TAG:-}"
DO_BACKUP=1
DRY=0
DOMAIN="${PTERO_DOMAIN:-portofolio.xiero.biz.id}"
PKG_URL="${PTERO_PACKAGE_URL:-}"

usage() {
    cat <<'TXT'
panel-deploy.sh — deploy/update ke server Pterodactyl lewat Client API (tanpa SSH).

Pemakaian:
  export PTERO_PANEL_URL="https://panel.hexnitystore.com"
  export PTERO_SERVER_ID="<uuid server>"
  export PTERO_API_KEY="<api key panel>"
  bash deploy/panel-deploy.sh [opsi]

Opsi:
  --tag vX.Y.Z          Rilis yang dipasang (default: rilis terbaru dari GitHub)
  --package-url URL     Paket ZIP kustom (mirror/internal), menggantikan GitHub
  --server UUID         Server ID (menimpa PTERO_SERVER_ID)
  --panel URL           URL panel (menimpa PTERO_PANEL_URL)
  --domain domain       Domain untuk cek akhir (default portofolio.xiero.biz.id)
  --no-backup           Lewati pembuatan backup panel
  --dry-run             Tampilkan rencana panggilan API, tidak menyentuh server
  -h, --help            Tampilkan bantuan ini

Alur: baca info server → backup → unduh ZIP rilis → upload → ekstrak → restart → cek domain.
Membuat API key: panel → foto profil → API Credentials → Create API Key (read & write).
TXT
    exit 0
}

while [ $# -gt 0 ]; do
    case "$1" in
        --tag)        TAG="${2:-}"; shift 2 ;;
        --server)     SERVER_ID="${2:-}"; shift 2 ;;
        --panel)      PANEL_URL="${2:-}"; shift 2 ;;
        --repo)       REPO="${2:-}"; shift 2 ;;
        --domain)     DOMAIN="${2:-}"; shift 2 ;;
        --package-url) PKG_URL="${2:-}"; shift 2 ;;
        --no-backup)  DO_BACKUP=0; shift ;;
        --dry-run)    DRY=1; shift ;;
        -h|--help)    usage ;;
        *) echo "Opsi tidak dikenal: $1 (pakai --help)"; exit 2 ;;
    esac
done

PANEL_URL="${PANEL_URL%/}"
WORKDIR="$(mktemp -d)"
trap 'rm -rf "$WORKDIR"' EXIT

say()  { printf '\033[1m%s\033[0m\n' "$*"; }
info() { printf '  %s\n' "$*"; }
die()  { printf '\033[31mERROR:\033[0m %s\n' "$*" >&2; exit 1; }

for dep in curl python3 unzip; do
    command -v "$dep" >/dev/null || die "butuh '$dep' di PATH."
done

if [ -z "$SERVER_ID" ] || [ -z "$API_KEY" ]; then
    die "PTERO_SERVER_ID dan PTERO_API_KEY wajib diisi (lihat --help)."
fi

# ---------- helper API ----------
LAST_BODY="$WORKDIR/last.json"
LAST_CODE=0

api() { # api METHOD PATH [JSON]
    local method="$1" path="$2" data="${3:-}" url
    url="$PANEL_URL/api/client/servers/$SERVER_ID$path"
    if [ "$DRY" -eq 1 ]; then
        info "[dry-run] $method ${path}${data:+  body=$data}"
        LAST_CODE=200; : >"$LAST_BODY"
        if [ "$path" = "/files/upload" ]; then
            printf '%s' '{"attributes":{"url":"https://dry-run.invalid/upload"}}' >"$LAST_BODY"
        fi
        return 0
    fi
    local args=(-sS -o "$LAST_BODY" -w '%{http_code}' -X "$method"
        -H "Authorization: Bearer $API_KEY"
        -H "Accept: application/json"
        -H "Content-Type: application/json")
    [ -n "$data" ] && args+=(-d "$data")
    LAST_CODE="$(curl "${args[@]}" "$url")" || die "Gagal menghubungi panel ($url)."
    case "$LAST_CODE" in
        2*) return 0 ;;
        401|403) die "API key ditolak (HTTP $LAST_CODE). Cek permission key: read & write." ;;
        404) die "Server/alur tidak ditemukan (HTTP 404). Cek PTERO_SERVER_ID." ;;
        429) die "Kena rate limit panel (HTTP 429). Tunggu sebentar lalu ulangi." ;;
        *) info "HTTP $LAST_CODE: $(head -c 300 "$LAST_BODY")"; return 1 ;;
    esac
}

json_field() { # json_field dotted.path file
    python3 - "$1" "$2" <<'PY'
import json, sys
path, file = sys.argv[1], sys.argv[2]
try:
    data = json.load(open(file))
except Exception:
    sys.exit(1)
cur = data
for key in path.split('.'):
    if cur is None:
        break
    if isinstance(cur, list):
        try:
            cur = cur[int(key)]
        except Exception:
            cur = None
    elif isinstance(cur, dict):
        cur = cur.get(key)
    else:
        cur = None
print('' if cur is None or isinstance(cur, (dict, list)) else cur)
PY
}

# ---------- 1. info server ----------
say "[1/5] Membaca informasi server"
if [ "$DRY" -eq 0 ]; then
    api GET "" || die "Tidak bisa membaca detail server."
    NAME="$(json_field attributes.name "$LAST_BODY")"
    IDENT="$(json_field attributes.identifier "$LAST_BODY")"
    STATE="$(json_field attributes.status "$LAST_BODY")"
    info "Nama     : ${NAME:-?}"
    info "ID pendek: ${IDENT:-?}"
    info "Status   : ${STATE:-?}"
else
    api GET ""
fi

# ---------- 2. tentukan tag rilis ----------
say "[2/5] Menentukan rilis"
if [ -n "$PKG_URL" ]; then
    [ -n "$TAG" ] || TAG="$(basename "$PKG_URL" .zip | sed 's/^portofolio-//')"
    URL="$PKG_URL"
elif [ -n "$TAG" ]; then
    URL="https://github.com/$REPO/releases/download/$TAG/portofolio-$TAG.zip"
else
    if [ "$DRY" -eq 1 ]; then
        TAG="vX.Y.Z"
    else
        TAG="$(curl -sS "https://api.github.com/repos/$REPO/releases/latest" \
               | python3 -c 'import json,sys;print(json.load(sys.stdin).get("tag_name",""))')"
        [ -n "$TAG" ] || die "Tidak bisa membaca rilis terbaru dari GitHub."
    fi
    URL="https://github.com/$REPO/releases/download/$TAG/portofolio-$TAG.zip"
fi

ZIP="portofolio-$TAG.zip"
info "Rilis    : $TAG"
info "Paket    : $URL"

if [ "$DRY" -eq 0 ]; then
    info "Mengunduh paket..."
    curl -fsSL --retry 3 -o "$WORKDIR/$ZIP" "$URL" || die "Unduhan gagal: $URL"
    unzip -tq "$WORKDIR/$ZIP" >/dev/null || die "ZIP rusak / bukan arsip valid."
    info "Ukuran   : $(du -h "$WORKDIR/$ZIP" | cut -f1)"
fi

# ---------- 3. backup ----------
say "[3/5] Backup sebelum deploy"
if [ "$DO_BACKUP" -eq 0 ]; then
    info "Dilewati (--no-backup)."
elif api POST "/backups" "{\"name\":\"sebelum-deploy-$TAG-$(date +%Y%m%d-%H%M)\",\"ignored_files\":[]}"; then
    BK_UUID="$(json_field attributes.uuid "$LAST_BODY")"
    info "Backup dibuat: ${BK_UUID:-?} (diproses di latar oleh Wings)."
    if [ "$DRY" -eq 0 ] && [ -n "$BK_UUID" ]; then
        for _ in $(seq 1 20); do
            sleep 3
            api GET "/backups/$BK_UUID" || break
            done_at="$(json_field attributes.completed_at "$LAST_BODY")"
            failed="$(json_field attributes.is_successful "$LAST_BODY")"
            [ -n "$done_at" ] && { info "Backup selesai."; break; }
            [ "$failed" = "False" ] && { info "Backup gagal, lanjut tanpa backup."; break; }
        done
    fi
else
    info "Backup tidak bisa dibuat (kuota penuh?). Lanjut — pastikan kamu punya salinan storage/."
fi

# ---------- 4. upload + decompress ----------
say "[4/5] Upload & ekstrak paket ke /home/container"
if [ "$DRY" -eq 1 ]; then
    api GET "/files/upload"
    info "[dry-run] curl -F files=@$ZIP <signed-url>"
    api POST "/files/decompress" "{\"root\":\"/\",\"file\":\"$ZIP\"}"
else
    api GET "/files/upload" || die "Tidak bisa meminta URL upload."
    SIGNED="$(json_field attributes.url "$LAST_BODY")"
    [ -n "$SIGNED" ] || die "Panel tidak mengembalikan signed URL."
    UP_CODE="$(curl -sS -o "$WORKDIR/up.txt" -w '%{http_code}' -X POST -F "files=@$WORKDIR/$ZIP" "$SIGNED")"
    [ "${UP_CODE:0:1}" = "2" ] || die "Upload gagal (HTTP $UP_CODE): $(head -c 300 "$WORKDIR/up.txt")"
    info "Upload selesai ($ZIP)."
    api POST "/files/decompress" "{\"root\":\"/\",\"file\":\"$ZIP\"}" \
        || die "Ekstrak gagal. Buka Files → unzip manual, atau cek pesan di atas."
    info "Ekstrak selesai. Folder storage/ dan .env tidak tersentuh."
fi

# ---------- 5. restart + verifikasi ----------
say "[5/5] Restart & verifikasi"
api POST "/power" '{"signal":"restart"}' && info "Perintah restart dikirim."
if [ "$DRY" -eq 0 ]; then
    sleep 6
    api GET "" && info "Status server: $(json_field attributes.status "$LAST_BODY")"
    if [ -n "$DOMAIN" ]; then
        code="$(curl -sS -m 15 -o /dev/null -w '%{http_code}' "https://$DOMAIN/" 2>/dev/null || true)"
        info "Cek https://$DOMAIN/ → HTTP ${code:-000}"
        [ "$code" = "200" ] && info "Aplikasi merespons normal." \
            || info "Belum 200 — cek Console panel & log start.sh."
    fi
fi

say "SELESAI"
info "Langkah manual berikutnya (sekali saja):"
info "  1. Login owner, ganti kata sandi & aktifkan 2FA."
info "  2. Admin → Pengaturan untuk identitas situs."
info "  3. Bila ada perubahan schema, buka halaman publik sekali agar migrasi berjalan."
