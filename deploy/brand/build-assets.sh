#!/usr/bin/env bash
#
# Membangun ulang aset identitas Xiero (logo, favicon, ikon PWA, banner, OG cover)
# dari satu sumber: deploy/brand/logo-source.png (+ banner-bg.jpg)
#
# Pemakaian:  bash deploy/brand/build-assets.sh [sumber-logo.png]
#
set -euo pipefail

cd "$(dirname "$0")/../.."

SRC="${1:-deploy/brand/logo-source.png}"
OUT_ICONS="public/icons"
OUT_IMG="public/assets/img"

[ -f "$SRC" ] || { echo "Sumber logo tidak ditemukan: $SRC"; exit 1; }
command -v convert >/dev/null || { echo "Butuh ImageMagick (convert)"; exit 1; }

mkdir -p "$OUT_ICONS" "$OUT_IMG"

# Logo dipangkas agar tidak ada ruang kosong berlebih.
convert "$SRC" -trim +repage -depth 8 -strip /tmp/brand-src.png

# ---------- 1) Logo transparan 512x512 (header / unggah manual) ----------
convert /tmp/brand-src.png -resize 440x440 -background none -gravity center -extent 512x512 \
    -depth 8 -strip -define png:compression-level=9 "$OUT_ICONS/logo-xiero-512.png"

# ---------- 2) Ikon: kanvas persegi membulat bergradasi + logo ----------
roundsquare() { # $1=ukuran $2=keluaran
    local s=$1 out=$2
    local r=$(( s * 22 / 100 ))
    convert -size ${s}x${s} gradient:'#173a5c-#060d18' \
        \( -size ${s}x${s} xc:none -fill white -draw "roundrectangle 0,0,$((s - 1)),$((s - 1)),$r,$r" \) \
        -alpha off -compose CopyOpacity -composite "$out"
}

make_icon() { # $1=ukuran $2=keluaran $3=persen-lebar-logo
    local s=$1 out=$2 pct=$3
    local inner=$(( s * pct / 100 ))
    local r=$(( s * 22 / 100 ))

    roundsquare "$s" /tmp/brand-bg.png
    convert /tmp/brand-src.png -resize ${inner}x${inner} -background none -gravity center \
        -extent ${inner}x${inner} /tmp/brand-mark.png

    if [ "$s" -ge 180 ]; then
        # Glow lembut hanya untuk ikon besar; ikon kecil harus tetap tajam.
        convert /tmp/brand-mark.png -background none -blur 0x$(( s / 36 )) \
            -fill '#66d0ff' -colorize 50% /tmp/brand-glow.png
        convert /tmp/brand-bg.png \
            \( /tmp/brand-glow.png -gravity center \) -compose over -composite \
            \( /tmp/brand-mark.png -gravity center \) -compose over -composite \
            /tmp/brand-stack.png
    else
        convert /tmp/brand-bg.png \
            \( /tmp/brand-mark.png -gravity center \) -compose over -composite \
            /tmp/brand-stack.png
    fi

    # Garis tepi halus supaya ikon tetap tegas di latar gelap/terang.
    convert /tmp/brand-stack.png \
        \( -size ${s}x${s} xc:none -fill none -stroke 'rgba(122,203,255,0.30)' \
           -strokewidth $(( s / 128 + 1 )) -draw "roundrectangle 1,1,$((s - 2)),$((s - 2)),$r,$r" \) \
        -compose over -composite -depth 8 -strip -define png:compression-level=9 "$out"

    if [ "$s" -lt 180 ]; then
        convert "$out" -unsharp 0x0.8+1.1+0 -depth 8 -strip -define png:compression-level=9 "$out"
    fi
}

make_icon 512 "$OUT_ICONS/icon-512.png" 64
make_icon 512 "$OUT_ICONS/favicon-512.png" 64
make_icon 512 "$OUT_ICONS/icon-maskable-512.png" 52
make_icon 192 "$OUT_ICONS/icon-192.png" 64
make_icon 180 "$OUT_ICONS/apple-touch-icon.png" 64
make_icon 32 "$OUT_ICONS/favicon-32.png" 72

# ---------- 3) Banner hero 1920x1080 ----------
BG="${BANNER_BG:-deploy/brand/banner-bg.jpg}"
if [ -f "$BG" ]; then
    convert "$BG" -resize 1920x1080^ -gravity center -extent 1920x1080 -modulate 100,112,100 /tmp/banner-base.png
else
    convert -size 1920x1080 gradient:'#0a1526-#03070e' /tmp/banner-base.png
fi

# Aura gelap di area logo agar kontras tetap terjaga.
convert -size 1920x1080 xc:none -fill 'rgba(3,8,16,0.72)' \
    -draw "circle 1370,540 1370,180" -blur 0x120 /tmp/banner-halo.png

convert /tmp/brand-src.png -resize 620x620 -background none -gravity center -extent 620x620 /tmp/banner-mark.png
convert /tmp/banner-mark.png -background none -blur 0x26 -fill '#59c8ff' -colorize 50% /tmp/banner-glow.png

convert /tmp/banner-base.png \
    /tmp/banner-halo.png -compose over -composite \
    \( /tmp/banner-glow.png -gravity east -geometry +240+0 \) -compose screen -composite \
    \( /tmp/banner-mark.png -gravity east -geometry +300+0 \) -compose over -composite \
    -strip -quality 88 "$OUT_IMG/hero-banner.jpg"

convert "$OUT_IMG/hero-banner.jpg" -strip -quality 82 "$OUT_IMG/hero-banner.webp"

# ---------- 4) Gambar pratinjau tautan (OG) 1200x630 ----------
convert "$OUT_IMG/hero-banner.jpg" -resize 1200x630^ -gravity center -extent 1200x630 \
    -strip -quality 86 "$OUT_IMG/og-cover.jpg"

echo "Selesai:"
identify "$OUT_ICONS/logo-xiero-512.png" "$OUT_ICONS/favicon-32.png" "$OUT_ICONS/icon-192.png" \
    "$OUT_ICONS/icon-512.png" "$OUT_ICONS/apple-touch-icon.png" "$OUT_IMG/hero-banner.jpg" \
    "$OUT_IMG/hero-banner.webp" "$OUT_IMG/og-cover.jpg"
