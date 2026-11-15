#!/usr/bin/env bash
#
# cleanup-vps.sh — membersihkan sisa file yang TIDAK terpakai di VPS,
# tanpa menyentuh data server, database, backup, atau volume Pterodactyl.
#
# Pemakaian:
#   bash deploy/cleanup-vps.sh                 # mode laporan (dry-run, default)
#   sudo bash deploy/cleanup-vps.sh --apply    # benar-benar menghapus
#   sudo bash deploy/cleanup-vps.sh --apply --days 14
#
# Yang dibersihkan (hanya ini):
#   1. isi /tmp dan /var/tmp yang lebih tua dari --days (default 7)
#   2. cache paket apt (/var/cache/apt/archives)
#   3. journal systemd lebih tua dari --journal-days (default 14)
#   4. image Docker dangling + cache build (docker image prune -f / builder prune -f)
#
# Yang HANYA dilaporkan (tidak pernah dihapus otomatis):
#   - /var/lib/pterodactyl/volumes   (file server: storage/, database, upload)
#   - /var/lib/pterodactyl/backups   (backup buatan panel)
#   - /var/lib/mysql, /etc, /home, /var/www, /srv, /opt
#
# Tidak pernah menjalankan: docker system prune, docker volume prune,
# docker container prune, rm -rf pada direktori sistem.
#
set -uo pipefail

DAYS=7
JOURNAL_DAYS=14
APPLY=0

usage() {
    sed -n '2,26p' "$0" | sed 's/^# \{0,1\}//'
    exit 0
}

while [ $# -gt 0 ]; do
    case "$1" in
        --apply)        APPLY=1; shift ;;
        --days)         DAYS="${2:-7}"; shift 2 ;;
        --journal-days) JOURNAL_DAYS="${2:-14}"; shift 2 ;;
        -h|--help)      usage ;;
        *) echo "Opsi tidak dikenal: $1 (pakai --help)"; exit 2 ;;
    esac
done

case "$DAYS" in ''|*[!0-9]*) echo "--days harus angka"; exit 2 ;; esac
case "$JOURNAL_DAYS" in ''|*[!0-9]*) echo "--journal-days harus angka"; exit 2 ;; esac

LOG=/var/log/portofolio-cleanup.log
if ! { : >>"$LOG"; } 2>/dev/null; then
    LOG="${HOME:-/root}/portofolio-cleanup.log"
fi

log() { printf '%s %s\n' "$(date '+%F %T')" "$*" | tee -a "$LOG"; }

run() {
    if [ "$APPLY" -eq 1 ]; then
        log "  JALAN : $*"
        if ! eval "$@" >>"$LOG" 2>&1; then log "  GAGAL : $*"; fi
    else
        log "  RENCANA: $*"
    fi
}

human() {
    if command -v numfmt >/dev/null 2>&1; then
        numfmt --to=iec --suffix=B "$1" 2>/dev/null || echo "$1 B"
    else
        echo "$1 B"
    fi
}

size_kb() { du -sk "$1" 2>/dev/null | awk '{print $1+0}'; }

# ---------- pengaman path ----------
PROTECTED_PATHS=(
    "/" "/var" "/var/lib" "/var/log" "/usr" "/usr/local" "/etc" "/home" "/root"
    "/bin" "/sbin" "/boot" "/lib" "/lib64" "/srv" "/opt" "/tmp" "/var/tmp"
    "/var/lib/mysql" "/var/lib/pterodactyl" "/var/lib/pterodactyl/volumes"
    "/var/lib/pterodactyl/backups" "/var/lib/docker" "/var/www"
)

is_protected() {
    local target="$1" cleaned p
    cleaned="$(readlink -f -- "$target" 2>/dev/null || printf '%s' "$target")"
    [ -z "$cleaned" ] && return 0
    for p in "${PROTECTED_PATHS[@]}"; do
        if [ "$cleaned" = "$p" ]; then return 0; fi
    done
    # Tolak apa pun di luar /tmp atau /var/tmp.
    case "$cleaned" in
        /tmp/*|/var/tmp/*) return 1 ;;
        *) return 0 ;;
    esac
}

log "==============================================================="
log "Bersih-bersih VPS — mode: $([ "$APPLY" -eq 1 ] && echo 'HAPUS (--apply)' || echo 'LAPORAN (dry-run)')"
log "Host: $(hostname 2>/dev/null) | Tanggal: $(date '+%F %T') | Log: $LOG"
[ "$(id -u)" -eq 0 ] || log "PERINGATAN: bukan root — sebagian langkah akan dilewati."

# ---------- 1. /tmp & /var/tmp ----------
log ""
log "[1/4] Berkas lama di /tmp & /var/tmp (lebih dari ${DAYS} hari)"
for d in /tmp /var/tmp; do
    [ -d "$d" ] || continue
    total_kb=0; count=0; shown=0
    while IFS= read -r -d '' entry; do
        if is_protected "$entry"; then
            log "  LEWATI (dilindungi): $entry"
            continue
        fi
        kb="$(size_kb "$entry")"; kb="${kb:-0}"
        total_kb=$((total_kb + kb))
        count=$((count + 1))
        if [ "$shown" -lt 10 ]; then
            log "  - $entry ($(human $((kb * 1024))))"
            shown=$((shown + 1))
        fi
        if [ "$APPLY" -eq 1 ]; then
            rm -rf -- "$entry" && log "    dihapus" || log "    GAGAL hapus"
        fi
    done < <(find "$d" -mindepth 1 -maxdepth 1 -mtime +"$DAYS" \
                ! -name 'systemd-*' ! -name 'pterodactyl*' ! -name 'wings*' \
                ! -name 'php*' ! -name 'mysql*' ! -name '.X11*' ! -name 'snap*' \
                -print0 2>/dev/null)
    log "  Ringkasan $d: $count entri, $(human $((total_kb * 1024)))"
done

# ---------- 2. cache apt ----------
log ""
log "[2/4] Cache paket apt"
APT_CACHE=/var/cache/apt/archives
if [ -d "$APT_CACHE" ]; then
    log "  Ukuran sekarang: $(human $(($(size_kb "$APT_CACHE") * 1024)))"
    if command -v apt-get >/dev/null 2>&1; then
        run "apt-get clean"
        run "apt-get autoclean -y"
    else
        log "  apt-get tidak ada — dilewati."
    fi
else
    log "  Tidak ada $APT_CACHE — dilewati."
fi

# ---------- 3. journal ----------
log ""
log "[3/4] Journal systemd (lebih dari ${JOURNAL_DAYS} hari)"
if command -v journalctl >/dev/null 2>&1; then
    log "  Pemakaian: $(journalctl --disk-usage 2>/dev/null | tail -1)"
    run "journalctl --vacuum-time=${JOURNAL_DAYS}d"
else
    log "  journalctl tidak ada — dilewati."
fi

# ---------- 4. Docker ----------
log ""
log "[4/4] Docker (hanya image dangling + cache build)"
if command -v docker >/dev/null 2>&1 && docker info >/dev/null 2>&1; then
    dangling=$(docker images -f dangling=true -q 2>/dev/null | wc -l | tr -d ' ')
    log "  Image dangling: ${dangling}"
    run "docker image prune -f"
    run "docker builder prune -f"
    log "  Catatan: image bertag (dipakai panel) TIDAK dihapus; volume & container tidak disentuh."
else
    log "  Docker tidak tersedia / daemon mati — dilewati."
fi

# ---------- laporan: area Pterodactyl (read-only) ----------
log ""
log "== Laporan area Pterodactyl (TIDAK dihapus otomatis) =="
PTERO=/var/lib/pterodactyl
if [ -d "$PTERO" ]; then
    for sub in volumes backups; do
        if [ -d "$PTERO/$sub" ]; then
            log "  $PTERO/$sub : $(human $(($(size_kb "$PTERO/$sub") * 1024)))"
        fi
    done
    if [ -d "$PTERO/backups" ]; then
        nb=$(find "$PTERO/backups" -mindepth 1 -maxdepth 1 -type d 2>/dev/null | wc -l | tr -d ' ')
        log "  Jumlah folder backup: ${nb}"
        find "$PTERO/backups" -mindepth 1 -maxdepth 1 -type d -printf '%TY-%Tm-%Td  %p\n' 2>/dev/null \
            | sort | head -5 | sed 's/^/    /' | while IFS= read -r line; do log "  $line"; done
        log "  Hapus backup lama dari PANEL (tab Backups) atau manual setelah dicek:"
        log "    du -sh $PTERO/backups/* | sort -h | tail -5"
        log "    rm -rf $PTERO/backups/<uuid-backup>   # hanya setelah yakin tidak dibutuhkan"
    fi
    if [ -d "$PTERO/volumes" ]; then
        log "  10 folder server terbesar:"
        du -sh "$PTERO"/volumes/* 2>/dev/null | sort -h | tail -10 | sed 's/^/    /' \
            | while IFS= read -r line; do log "  $line"; done
    fi
else
    log "  $PTERO tidak ada (Wings mungkin di mesin lain)."
fi

log ""
log "== Ruang disk =="
df -h / 2>/dev/null | sed 's/^/  /' | while IFS= read -r line; do log "$line"; done

log ""
if [ "$APPLY" -eq 1 ]; then
    log "SELESAI. Ruang setelah pembersihan:"
    df -h / 2>/dev/null | tail -1 | sed 's/^/  /' | while IFS= read -r line; do log "$line"; done
else
    log "Ini hanya LAPORAN. Jalankan ulang dengan --apply untuk menghapus."
fi
log "Log lengkap: $LOG"
