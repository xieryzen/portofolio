#!/usr/bin/env bash
#
# Membangun ulang aset identitas Xiero dari satu sumber:
#   deploy/brand/logo-source.png   (logo transparan)
#   deploy/brand/banner-bg.jpg     (tekstur latar 16:9)
#
# Pemakaian:  bash deploy/brand/build-assets.sh [sumber-logo.png]
#
# Prinsip desain:
#   - ikon besar memakai logo metalik dengan bayangan netral (tanpa glow berlebih)
#   - ikon kecil (32 px) memakai siluet putih agar tetap tajam dan tidak "lumer"
#   - banner murni tekstur (grid + jalur sirkuit tipis + butir halus), tanpa logo,
#     supaya tidak bertabrakan dengan foto profil di kolom kanan hero
#
set -euo pipefail

cd "$(dirname "$0")/../.."

SRC="${1:-deploy/brand/logo-source.png}"
BG="${BANNER_BG:-deploy/brand/banner-bg.jpg}"
OUT_ICONS="public/icons"
OUT_IMG="public/assets/img"

[ -f "$SRC" ] || { echo "Sumber logo tidak ditemukan: $SRC"; exit 1; }
command -v convert >/dev/null || { echo "Butuh ImageMagick (convert)"; exit 1; }

mkdir -p "$OUT_ICONS" "$OUT_IMG" /tmp/brand
TMP=/tmp/brand
rm -f "$TMP"/*.png

# Logo dipangkas & disiapkan sekali.
convert "$SRC" -trim +repage -depth 8 -strip "$TMP/src.png"
convert "$TMP/src.png" -resize 440x440 -background none -gravity center -extent 512x512 \
    -depth 8 -strip -define png:compression-level=9 "$OUT_ICONS/logo-xiero-512.png"

# ---------- 1) Kanvas ikon: gradasi navy + kilau atas sangat halus ----------
icon_base() { # $1=ukuran $2=keluaran
    local s=$1 out=$2 r=$(( $1 * 23 / 100 ))
    # gradasi navy + kilau lembut di bagian atas (padded transparan, bukan digeser)
    convert -size ${s}x${s} gradient:'#12263d-#05090f' \
        \( -size ${s}x$(( s * 55 / 100 )) gradient:'#8FC0FF55-#8FC0FF00' \
           -background none -gravity north -extent ${s}x${s} \) \
        -compose over -composite \
        \( -size ${s}x${s} xc:none -fill white -draw "roundrectangle 0,0,$((s - 1)),$((s - 1)),$r,$r" \) \
        -alpha off -compose CopyOpacity -composite "$out"
}

# Monogram X yang disederhanakan untuk ikon kecil (32 px):
# detail metalik logo tidak terbaca di ukuran ini, jadi dipakai bentuk tegas.
poly_mark() { # $1=ukuran-kanvas $2=keluaran
    local s=$1 out=$2
    local p=""
    local pts="8,7 11,7 16,14 21,7 24,7 17.5,16 24,25 21,25 16,18 11,25 8,25 14.5,16"
    for xy in $pts; do
        local x=${xy%,*} y=${xy#*,}
        p="$p $(( ${x%.*} * s / 32 )),$(( ${y%.*} * s / 32 ))"
    done
    convert -size ${s}x${s} xc:none -fill '#E8F1FF' -stroke none \
        -draw "polygon$p" "$out"
}

make_icon() { # $1=ukuran $2=keluaran $3=persen $4=metalik|mono
    local s=$1 out=$2 pct=$3 mode=$4
    local inner=$(( s * pct / 100 ))
    local r=$(( s * 23 / 100 ))

    icon_base "$s" "$TMP/base.png"

    if [ "$mode" = "mono" ]; then
        poly_mark "$inner" "$TMP/fg.png"
    else
        convert "$TMP/src.png" -resize ${inner}x${inner} -background none -gravity center \
            -extent ${inner}x${inner} "$TMP/fg.png"
    fi

    # Bayangan netral tipis agar logo tidak menempel di tepi kanvas.
    convert "$TMP/fg.png" -background none -blur 0x$( [ "$s" -ge 180 ] && echo 6 || echo 1.2 ) \
        -fill 'rgba(0,0,0,0.55)' -colorize 100 "$TMP/shadow.png"

    convert "$TMP/base.png" \
        \( "$TMP/shadow.png" -gravity center -geometry +0+$(( s / 90 + 1 )) \) -compose over -composite \
        \( "$TMP/fg.png" -gravity center \) -compose over -composite \
        \( -size ${s}x${s} xc:none -fill none -stroke 'rgba(180,215,255,0.16)' \
           -strokewidth 1 -draw "roundrectangle 1,1,$((s - 2)),$((s - 2)),$r,$r" \) -compose over -composite \
        -depth 8 -strip -define png:compression-level=9 "$out"

    if [ "$s" -lt 180 ]; then
        convert "$out" -unsharp 0x0.7+0.9+0 -depth 8 -strip -define png:compression-level=9 "$out"
    fi
}

make_icon 512 "$OUT_ICONS/icon-512.png" 66 metalik
make_icon 512 "$OUT_ICONS/favicon-512.png" 66 metalik
make_icon 512 "$OUT_ICONS/icon-maskable-512.png" 56 metalik
make_icon 192 "$OUT_ICONS/icon-192.png" 66 metalik
make_icon 180 "$OUT_ICONS/apple-touch-icon.png" 66 metalik
make_icon 32 "$OUT_ICONS/favicon-32.png" 78 mono

# ---------- 2) Banner hero 1920x1080 (murni tekstur) ----------
if [ -f "$BG" ]; then
    convert "$BG" -resize 1920x1080^ -gravity center -extent 1920x1080 "$TMP/banner.png"
else
    convert -size 1920x1080 gradient:'#0b1728-#04080f' "$TMP/banner.png"
fi

# Tepi bawah dibuat menyatu dengan halaman (skrim kiri diatur lewat CSS hero).
convert "$TMP/banner.png" \
    \( -size 1920x300 gradient:'rgba(4,8,16,0)-rgba(6,10,20,0.92)' \) \
    -gravity south -compose over -composite \
    -modulate 122,118,100 \
    -attenuate 0.05 +noise Gaussian \
    -strip -quality 88 "$OUT_IMG/hero-banner.jpg"

convert "$OUT_IMG/hero-banner.jpg" -strip -quality 82 "$OUT_IMG/hero-banner.webp"

# ---------- 3) Gambar pratinjau tautan (OG) 1200x630 ----------
FONT="DejaVu-Sans-Bold"
if ! convert -list font 2>/dev/null | grep -q "Font: ${FONT}$"; then
    FONT="DejaVu-Sans"
fi

convert "$OUT_IMG/hero-banner.jpg" -resize 1200x630^ -gravity center -extent 1200x630 \
    "$TMP/og-bg.png"

# skrim kiri agar teks selalu terbaca
convert "$TMP/og-bg.png" \
    \( -size 1200x630 gradient:'rgba(4,8,16,0.80)-rgba(4,8,16,0)' \) \
    -compose over -composite "$TMP/og-bg2.png"

convert "$TMP/src.png" -resize 190x190 -background none -gravity center -extent 210x210 \
    "$TMP/og-mark.png"

convert "$TMP/og-bg2.png" \
    \( "$TMP/og-mark.png" -gravity west -geometry +96+0 \) -compose over -composite \
    -gravity northwest \
    -font "$FONT" -pointsize 70 -fill '#F4F8FF' -kerning 14 \
    -annotate +332+292 'XIERO' \
    -font "DejaVu-Sans" -pointsize 22 -fill '#8FA7C2' -kerning 3 \
    -annotate +334+372 'Jasa Developer · Website · Server' \
    -strip -quality 88 "$OUT_IMG/og-cover.jpg"

# ---------- 4) Lembar pratinjau ----------
convert -size 1600x1100 xc:'#05090f' -gravity northwest \
    \( "$OUT_IMG/hero-banner.jpg" -resize 1600x900^ -gravity center -extent 1600x900 +gravity \) -geometry +0+0 -composite \
    \( "$OUT_ICONS/icon-512.png" -resize 150x150 \) -geometry +110+910 -composite \
    \( "$OUT_ICONS/favicon-32.png" -resize 150x150 \) -geometry +310+910 -composite \
    \( "$OUT_ICONS/apple-touch-icon.png" -resize 150x150 \) -geometry +510+910 -composite \
    \( "$OUT_ICONS/icon-maskable-512.png" -resize 150x150 \) -geometry +710+910 -composite \
    \( "$OUT_IMG/og-cover.jpg" -resize 420x \) -geometry +1000+905 -composite \
    -depth 8 -strip -quality 90 deploy/brand/preview.jpg

echo "Selesai:"
identify "$OUT_ICONS/logo-xiero-512.png" "$OUT_ICONS/favicon-32.png" "$OUT_ICONS/icon-192.png" \
    "$OUT_ICONS/icon-512.png" "$OUT_ICONS/apple-touch-icon.png" "$OUT_IMG/hero-banner.jpg" \
    "$OUT_IMG/hero-banner.webp" "$OUT_IMG/og-cover.jpg" deploy/brand/preview.jpg
