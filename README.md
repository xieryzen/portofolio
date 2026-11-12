# Portfolio CMS — PHP 8.3

Website portofolio pribadi dengan halaman login/register untuk **user** dan **owner**.
Halaman publik menampilkan portofolio (nama, deskripsi, project, dokumentasi foto), sedangkan
panel admin dipakai untuk mengelola semuanya — dibuat agar **ringan dan bisa langsung jalan di Pterodactyl**
(tanpa Composer, tanpa build step, database SQLite bawaan).

![PHP](https://img.shields.io/badge/PHP-8.3%2B-777bb4)
![Database](https://img.shields.io/badge/Database-SQLite%20%7C%20MySQL-0ea5e9)
![License](https://img.shields.io/badge/License-MIT-22c55e)
[![CI](https://github.com/xieryzen/portofolio/actions/workflows/ci.yml/badge.svg)](https://github.com/xieryzen/portofolio/actions/workflows/ci.yml)

---

## ✨ Fitur

**Halaman publik / user**
- Hero profil: nama, headline, tagline, foto, status ketersediaan, statistik pengalaman.
- Grid project unggulan + arsip project, halaman detail project dengan tech stack & tautan.
- Galeri dokumentasi dengan lightbox (klik gambar untuk memperbesar).
- **Rilis & unduhan per project**: riwayat versi, catatan perubahan (changelog), file unduhan, ukuran, jumlah unduhan, dan penanda versi terbaru.
- Bagian "Tentang" berisi deskripsi panjang dan bar keahlian per kategori.
- Formulir kontak yang tersimpan ke panel admin (dengan honeypot + anti-spam), plus notifikasi
  opsional ke Telegram, webhook JSON, dan email SMTP.
- **Tema terang & gelap**: tombol di header/admin, tersimpan di browser, dan otomatis mengikuti
  preferensi sistem (`prefers-color-scheme`) bila belum dipilih.
- **Pencarian project** di beranda (judul, ringkasan, kategori, tech stack) lewat `/?q=kata`.
- **Dua bahasa (ID/EN)** untuk seluruh antarmuka publik, lengkap dengan tombol ID/EN, cookie
  preferensi bahasa, dan tag `hreflang`.
- **Tombol Unduh CV** otomatis muncul di hero, navbar, dan menu HP setelah PDF diunggah dari panel.
- **Halaman studi kasus** per project: tantangan, solusi, hasil, dan kartu metrik.
- **Blog/artikel**: daftar artikel dengan pencarian & kategori, halaman detail dengan cover,
  estimasi waktu baca, tag, artikel terkait, feed **RSS** (`/feed.xml`), dan entri sitemap otomatis.
- **SEO**: `sitemap.xml` dinamis (beranda + semua project terbit) dan `robots.txt` yang menunjuk sitemap.
- **PWA**: bisa dipasang ke home screen (Android/iOS), aset di-cache service worker, dan ada
  halaman `/offline` saat koneksi putus. Area admin & unduhan tidak pernah di-cache.
- Responsif penuh, animasi halus: konten muncul berurutan saat scroll, ripple saat
  elemen diklik, tombol non-aktif bergetar, dan cincin kecil pada klik di area kosong.
  Semua animasi menghormati `prefers-reduced-motion`.
- Tampilan dioptimalkan untuk HP kecil (≤420 px), HP standar, tablet, laptop, dan layar ≥1440 px:
  target sentuh 44 px, input 16 px (anti-zoom iOS), tabel bisa digeser, serta gaya khusus cetak.

**Akun**
- Register user (bisa dimatikan), login, logout, ganti kata sandi, halaman dashboard user.
- Tiga role: `owner` (semua akses), `admin` (kelola konten), `user` (hanya melihat).
- **Verifikasi dua langkah (2FA/TOTP)** opsional per akun: kode 6 digit dari Google Authenticator,
  Authy, 1Password, atau Bitwarden, plus 8 kode pemulihan sekali pakai.
- **Log aktivitas** untuk owner: login sukses/gagal, logout, 2FA dinyalakan/dimatikan,
  kode pemulihan dipakai, perubahan kata sandi, pengaturan, dan restore cadangan.
- Proteksi CSRF di semua form, rate limit login, session hardening, escaping output.

**Panel admin**
- Ringkasan statistik + status kelengkapan profil + pesan terbaru.
- Profil: nama, headline, tagline, deskripsi, lokasi, kontak, sosial media, statistik, avatar & cover.
- Project: CRUD, draft/publik, unggulan, urutan, cover, dokumentasi multi-upload + caption.
- Rilis project: tambah versi + file unduhan (ZIP, PDF, APK, dokumen), sembunyikan dari publik, ganti/hapus file, hapus rilis, pantau jumlah unduhan.
- Keahlian: tambah/ubah/hapus dengan kategori dan level.
- Pengguna: daftar akun, ubah role, aktif/nonaktif, hapus (khusus owner).
- Pesan: tandai dibaca, balas via email, hapus.
- Pengaturan: judul situs, meta description, warna aksen, footer, aktif/nonaktif kontak & registrasi.
- **Cadangan**: unduh database (SQLite) atau satu ZIP berisi database + upload + rilis, lalu pulihkan
  lewat upload ZIP. Salinan database sebelum restore disimpan otomatis di `storage/backups/`.
- **CV (PDF)**: unggah/ganti/hapus CV dari halaman Profil.
- **Studi kasus**: tiga blok teks + metrik per project dari form project.
- **Statistik kunjungan**: grafik 14 hari, halaman populer, dan sumber kunjungan tanpa data pribadi.
- **Artikel**: CRUD blog dengan draft/publik, cover (otomatis WebP), kategori, tag, dan penanda pilihan.
- **Keamanan akun**: aktifkan/nonaktifkan 2FA, lihat sisa kode pemulihan, buat ulang kode pemulihan.
- **Log aktivitas**: 40 entri terbaru per halaman dengan waktu, akun, aksi, keterangan, dan alamat IP.

---

## 🚀 Menjalankan lokal

```bash
git clone https://github.com/xieryzen/portofolio.git
cd portofolio
cp .env.example .env

php -S 0.0.0.0:8080 -t public router.php
# atau
bash start.sh
```

Buka `http://localhost:8080`.

**Login pertama:** `owner@example.com` / `Owner12345!` (dibuat otomatis dari `.env`).

> ⚠️ Akun ini **milik instalasi kamu sendiri** — wajib diganti sebelum dipakai publik.
> Lihat bagian **🔑 Akun owner** di bawah untuk cara menggantinya.

Akun owner otomatis dibuat saat aplikasi pertama kali dijalankan. Database, tabel, dan
**3 contoh project + dokumentasi + satu rilis demo (`v1.0.0`)** dibuat otomatis — tidak perlu
import SQL. Contoh project dan rilis bisa dihapus kapan saja dari panel admin.

### Persyaratan
- PHP **8.3** atau lebih baru
- Ekstensi: `pdo_sqlite` **atau** `pdo_mysql`, `mbstring`, `fileinfo`
- Folder `storage/` harus writable (sesi, database, upload, log)

---

## 🔑 Akun owner — ganti sebelum dipakai

Repo ini adalah **template**: siapa pun yang meng-install-nya menjalankan instance sendiri.
Database, file upload, dan file rilis tersimpan di server masing-masing — **tidak ada
koneksi ke server pembuat repo**. Jadi setiap pemasang wajib memakai akun owner sendiri,
bukan kredensial contoh di bawah.

### Kredensial awal (default)

| Peran | Email | Kata sandi |
| ----- | ----- | ---------- |
| Owner | `owner@example.com` | `Owner12345!` |

Nilai tersebut dibaca dari `.env` (atau variabel egg Pterodactyl) saat instalasi pertama:

| Variabel | Default | Keterangan |
| --- | --- | --- |
| `OWNER_NAME` | `Pemilik Portfolio` | Nama tampilan owner |
| `OWNER_EMAIL` | `owner@example.com` | Email login owner |
| `OWNER_PASSWORD` | `Owner12345!` | Kata sandi owner |

Nilai di atas adalah isi `.env.example`. Kalau variabelnya dibiarkan kosong, kode memakai
`Owner` / `owner@example.com` / `Owner12345!` sebagai cadangan.

Akun owner dibuat **hanya sekali**, yaitu ketika tabel `users` masih kosong. Setelah itu,
mengubah `OWNER_EMAIL` / `OWNER_PASSWORD` di `.env` **tidak** mengubah akun yang sudah ada —
pakai salah satu cara berikut.

### Cara mengganti akun owner

1. **Lewat panel (disarankan)** — login sebagai owner, buka **Admin → Pengaturan**, lalu isi
   bagian **Akun owner**: nama, email, dan kata sandi baru (kosongkan kolom kata sandi bila
   tidak ingin menggantinya). Kata sandi saat ini diminta sebagai konfirmasi; kata sandi baru
   minimal 8 karakter.
2. **Lewat CLI** — kalau lupa kata sandi atau tidak bisa login:

   ```bash
   php bin/console password:reset email@anda.com SandiBaru123!          # reset sandi
   php bin/console owner:create email@anda.com "Nama Anda" SandiBaru123! # owner tambahan
   php bin/console user:list                                            # lihat semua akun
   ```

   Jalankan dari **root aplikasi** (bukan folder `public/`). Di Pterodactyl buka tab
   **Console** server, lalu ketik perintahnya.
3. **Sebelum instalasi pertama** — isi `OWNER_EMAIL`, `OWNER_PASSWORD`, dan `OWNER_NAME` di
   `.env` (atau di variabel egg) agar akun owner langsung dibuat dengan kredensial pilihanmu.

> ⚠️ Jangan biarkan `Owner12345!` aktif pada instance yang bisa diakses publik.
> Setelah ganti akun, cek juga **Admin → Pengguna** untuk memastikan tidak ada akun asing.

---

## 🐧 Deploy di Pterodactyl

Aplikasi ini memakai **PHP built-in server** + **SQLite**, jadi tidak butuh Nginx/Apache
di dalam container. Semua permintaan diarahkan lewat `router.php`.

### 1. Import egg
1. Buka **Admin Area → Nests → Import Egg**.
2. Pilih file `deploy/pterodactyl-egg.json` yang ada di repo ini.
3. Simpan (egg menggunakan image `php:8.3-cli`, startup command `bash start.sh`).

### 2. Buat server
- Pilih egg **"PHP 8.3 — Portfolio CMS"**.
- Alokasi port: bebas (mis. `8080`), port inilah yang dibuka panel.
- Isi variabel egg: `OWNER_EMAIL`, `OWNER_PASSWORD`, `OWNER_NAME` (bisa diubah kapan pun).
- Pastikan server punya resource minimal **RAM 256 MB** dan **disk 512 MB**.

### 3. Upload file
Lewat **File Manager**: upload ZIP repo → extract ke `/home/container`
(atau pakai menu **SFTP**). Struktur akhir harus seperti:

```
/home/container/
├── app/  bin/  public/  router.php  start.sh  views/  storage/ ...
```

Jika egg memakai install script Git, file akan di-clone otomatis saat instalasi.

### 4. Jalankan
- Klik **Start**. Log akan menampilkan:

  ```
  [portfolio] Menjalankan PHP 8.3.x di http://0.0.0.0:8080
  ```

- Buka alamat server: `http://IP:PORT` (atau domain/subdomain yang diarahkan ke port tersebut).
- Login dengan akun owner dari variabel egg / `.env`, lalu ganti kredensialnya —
  lihat bagian **🔑 Akun owner**.

### 5. Batas upload
Ada dua batas unggahan:

| Jenis | Variabel | Default |
| ----- | -------- | ------- |
| Gambar (cover, avatar, dokumentasi) | `MAX_UPLOAD_MB` | 6 MB |
| File rilis project | `RELEASE_MAX_MB` | 50 MB (maks 500) |

`start.sh` memakai nilai terbesar dari keduanya untuk `upload_max_filesize` dan menambah 4 MB
untuk `post_max_size`. Jika memakai Nginx/Apache, sesuaikan juga:

- Nginx: `client_max_body_size 60M;` (samakan dengan `RELEASE_MAX_MB` + buffer)
- Apache: `php_value upload_max_filesize 50M` dan `php_value post_max_size 54M`

### 6. Hal penting di Pterodactyl
| Hal | Keterangan |
| --- | --- |
| Port | Panel menyuntikkan `SERVER_PORT`, `start.sh` memakainya otomatis. |
| Database | Default **SQLite** (`storage/database.sqlite`) — tanpa konfigurasi. |
| MySQL (opsional) | Buat database di tab **Databases**, lalu isi `DB_CONNECTION=mysql`, `DB_HOST`, `DB_NAME`, `DB_USERNAME`, `DB_PASSWORD` di `.env`. |
| File `.env` | Dibuat otomatis oleh `start.sh` pada start pertama (dengan `APP_KEY` acak). Edit lewat File Manager. |
| Permission | `storage/` dibuat otomatis 775. Jika error tulis, jalankan: `chmod -R 775 storage` lewat console. |
| Backup | Salin `storage/` (berisi database, semua gambar, dan file rilis). |
| Restart | Panel → Restart. Tidak ada proses build, jadi cepat. |

### Reverse proxy / domain (opsional)
Jika memakai Cloudflare atau Nginx di depan panel, aktifkan `TRUST_PROXY=true` di `.env`
agar cookie sesi mengenali HTTPS.

---

## 🖥️ Deploy VPS (Nginx + PHP-FPM / Apache)

**Nginx** (contoh lengkap ada di `deploy/nginx.conf.example`):

```nginx
server {
    listen 80;
    server_name portofolio.example.com;
    root /var/www/portofolio/public;
    index index.php;

    client_max_body_size 8M;
    location / { try_files $uri $uri/ /index.php?$query_string; }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
    }

    location ~ /\.(?!well-known).* { deny all; }
}
```

**Apache**: arahkan `DocumentRoot` ke folder `public/` (file `.htaccess` sudah disertakan).

**Docker**:

```bash
docker build -t portfolio .
docker run -d -p 8080:8080 -v portfolio_storage:/app/storage --name portfolio portfolio
```

---

## 📦 Rilis aplikasi (GitHub Releases)

Ada dua jenis "rilis" di repo ini — jangan tertukar:

| | Rilis di dalam aplikasi | GitHub Releases |
| --- | --- | --- |
| Fungsi | file unduhan untuk pengunjung situs, per project (versi, catatan, file, penghitung unduhan) | paket **aplikasi** (ZIP) untuk instalasi/upgrade |
| Dikelola di | Admin → Edit project → **Rilis & unduhan** | tag git + GitHub Actions |
| Disimpan di | `storage/releases/` pada server masing-masing | aset rilis di GitHub |

### Membuat paket rilis baru

Workflow `.github/workflows/release.yml` berjalan otomatis setiap tag `v*` di-push:

```bash
git tag -a v1.0.1 -m "Portofolio v1.0.1"
git push origin v1.0.1
```

Workflow akan mengecek sintaks seluruh file PHP, membuat `portofolio-v1.0.1.zip` (tanpa
`.git`, `.github`, isi `storage/`, dan `.env`), lalu mempublikasikan GitHub Release berisi ZIP
tersebut. Rilis pertama: **v1.0.0**.

ZIP itu bisa dipakai untuk instalasi baru di Pterodactyl: ekstrak ke folder server, lalu
**Start**. Untuk memperbarui instalasi yang sudah ada, ekstrak ulang di atasnya — jangan hapus
folder `storage/` agar database, upload, dan file rilis tetap aman.

---

## 🔔 Notifikasi pesan kontak (opsional)

Setiap pesan baru bisa langsung diteruskan ke kanal berikut. Isi salah satu (atau semuanya)
di `.env`, lalu **restart** server.

| Kanal | Variabel yang perlu diisi |
| ----- | ------------------------- |
| Telegram | `TELEGRAM_BOT_TOKEN`, `TELEGRAM_CHAT_ID` |
| Webhook JSON | `NOTIFY_WEBHOOK_URL` |
| Email | `NOTIFY_EMAIL_TO` + (`SMTP_HOST`, `SMTP_PORT`, `SMTP_USER`, `SMTP_PASS`, `SMTP_ENCRYPTION`) |

Contoh Telegram:
1. Chat **@BotFather** → `/newbot` → salin token ke `TELEGRAM_BOT_TOKEN`.
2. Kirim pesan apa pun ke bot, buka `https://api.telegram.org/bot<TOKEN>/getUpdates`, salin
   `chat.id` ke `TELEGRAM_CHAT_ID`.
3. Buka **Admin → Pengaturan → Notifikasi pesan**, klik **Kirim tes notifikasi**.

Payload webhook:

```json
{
  "event": "contact.created",
  "site": "Nama Situs",
  "message": { "name": "...", "email": "...", "subject": "...", "body": "..." },
  "admin_url": "https://domain-kamu/admin/messages",
  "sent_at": "2026-01-01T10:00:00+07:00"
}
```

Catatan:
- Toggle **Kirim notifikasi saat ada pesan kontak baru** ada di Pengaturan; mematikan toggle
  tidak menghapus pesan dari panel.
- Bila pengiriman gagal (token salah, SMTP diblokir hosting), pesan **tetap tersimpan** dan
  kegagalan dicatat di `storage/logs/notify.log` serta tampil di halaman Pengaturan.
- Tanpa `SMTP_HOST`, email dikirim lewat `mail()` bila tersedia.

---

## 🖼️ Optimasi gambar (WebP)

Setiap gambar yang diunggah (avatar, cover, sampul project, dokumentasi) otomatis:

1. diperkecil bila lebih lebar dari `IMAGE_MAX_WIDTH` (default 1600 px), lalu
2. dikonversi ke **WebP** dengan kualitas `IMAGE_WEBP_QUALITY` (default 82).

Konversi memakai ekstensi **GD** (`imagewebp`). Bila GD tidak tersedia di hosting, file asli tetap
dipakai tanpa error — tidak ada gambar yang hilang. GIF tidak dikonversi agar animasinya tidak
hilang, dan WebP yang hasilnya justru lebih besar dari aslinya tidak dipakai.

Matikan lewat `.env` bila tidak diinginkan:

```env
IMAGE_WEBP=false
```

---

## 📄 Tombol Unduh CV

1. Buka **Admin → Profil**, kartu **CV (PDF)**.
2. Unggah berkas PDF (maksimal `CV_MAX_MB`, default 10 MB, isi diverifikasi `%PDF-`).
3. Tombol **Unduh CV** langsung tampil di hero, navbar desktop, dan menu HP.
   Hapus/ganti kapan saja dari kartu yang sama.

File disimpan di `storage/documents/` dan hanya bisa diunduh lewat route `/cv`
(tidak bisa diakses langsung dari web server).

---

## 📊 Statistik kunjungan

Statistik ringan tanpa layanan eksternal dan **tanpa data pribadi**:

- hanya halaman publik (`/` dan `/project/{slug}`) yang dihitung,
- bot/crawler (Googlebot, curl, monitor, dsb.) difilter lewat user-agent,
- yang disimpan hanya path, host referrer, dan penanda bot — bukan IP atau cookie,
- entri lebih tua dari 365 hari dibersihkan otomatis.

Halaman **Admin → Statistik** (khusus owner) menampilkan total hari ini/7 hari/30 hari/total,
grafik 14 hari terakhir, halaman populer, dan sumber kunjungan.

---

## 🌐 Dua bahasa (ID/EN)

Halaman publik tersedia dalam **Indonesia** dan **Inggris**:

- tombol **ID / EN** di navbar dan menu HP, atau tambahkan `?lang=en` pada URL,
- pilihan disimpan di cookie `lang` selama satu tahun,
- kunjungan pertama tanpa pilihan mengikuti header `Accept-Language`,
- tag `<html lang>` dan `hreflang` diisi otomatis untuk SEO.

Isi database (profil, judul project, deskripsi, studi kasus) tidak diterjemahkan otomatis —
kamus antarmuka ada di `lang/id.php` dan `lang/en.php` bila ingin menyesuaikan teks.
**Panel admin tetap berbahasa Indonesia.**

---

## 📝 Blog / artikel

Artikel dikelola dari **Admin → Artikel** (`/admin/posts`):

- Tulis judul, ringkasan, konten (teks biasa, paragraf dipisah baris kosong), kategori, dan tag.
- Simpan sebagai **Draft** dulu, lalu ubah ke **Publik** saat siap. Tanggal publikasi diisi otomatis
  saat pertama kali diterbitkan; draft tidak muncul di halaman publik, sitemap, maupun RSS.
- Cover opsional (JPG/PNG/WEBP/GIF) — otomatis diperkecil & dikonversi ke WebP seperti gambar lain.
- Estimasi waktu baca dihitung dari jumlah kata (~200 kata/menit), jumlah dilihat dihitung otomatis.

Halaman publik:

| URL | Isi |
| --- | ----------- |
| `/blog` | Daftar artikel + pencarian `?q=` dan filter kategori `?kategori=` |
| `/blog/{slug}` | Detail artikel + tag + artikel terkait |
| `/feed.xml` | RSS 2.0 (20 artikel terbaru) |

Artikel terbit otomatis masuk ke `sitemap.xml`, dan tiga artikel terbaru tampil di beranda
(bagian **Artikel terbaru**). Semua label antarmuka blog tersedia dalam ID/EN.

---

## 🔒 Verifikasi dua langkah (2FA)

2FA bersifat opsional dan diatur per akun (owner maupun admin) lewat **/admin/security**.

1. Buka **Admin → Keamanan**, klik **Mulai aktifkan 2FA**.
2. Buka aplikasi authenticator (Google Authenticator, Authy, 1Password, Bitwarden, dsb),
   pilih "Enter a setup key" / "Tambah akun secara manual", lalu masukkan **secret** yang tampil.
   Alternatif: salin tautan `otpauth://…` ke HP.
3. Masukkan kode 6 digit dari aplikasi → **Aktifkan 2FA**.
4. **Simpan 8 kode pemulihan** yang muncul (hanya ditampilkan sekali). Setiap kode bisa dipakai
   satu kali bila ponsel hilang.

Saat login, setelah kata sandi benar kamu akan diarahkan ke `/two-factor` untuk memasukkan kode
6 digit atau kode pemulihan. Sesi verifikasi kedaluwarsa setelah 10 menit dan dibatasi 6 percobaan.
Menonaktifkan 2FA atau membuat ulang kode pemulihan memerlukan kata sandi.

Kode TOTP bergantung pada waktu. Bila kode selalu ditolak, aktifkan sinkronisasi waktu otomatis
di perangkat.

**Darurat — ponsel & kode pemulihan hilang:** jalankan dari CLI server:

```bash
php bin/console 2fa:disable akun@email.com
```

Perintah ini menghapus secret dan kode pemulihan akun tersebut, sehingga login kembali memakai
kata sandi saja. Catat aksi ini di log server.

---

## ⚙️ Konfigurasi `.env`

| Variabel | Default | Keterangan |
| -------- | ------- | ---------- |
| `APP_NAME` | `Portfolio` | Nama aplikasi (dipakai internal). |
| `APP_ENV` | `production` | `production` / `local`. |
| `APP_DEBUG` | `false` | `true` menampilkan detail error (jangan di produksi). |
| `APP_KEY` | — | String acak; dibuat otomatis saat start pertama. |
| `APP_PORT` / `APP_HOST` | — | Kosongkan di Pterodactyl (`SERVER_PORT` dipakai). |
| `DB_CONNECTION` | `sqlite` | `sqlite` atau `mysql`. |
| `DB_DATABASE` | `storage/database.sqlite` | Path SQLite (relatif root aplikasi). |
| `DB_HOST` / `DB_PORT` / `DB_NAME` / `DB_USERNAME` / `DB_PASSWORD` | — | Untuk MySQL. |
| `OWNER_NAME` | `Pemilik Portfolio` | Nama owner awal. |
| `OWNER_EMAIL` | `owner@example.com` | Email login owner awal. |
| `OWNER_PASSWORD` | `Owner12345!` | Kata sandi owner awal. |
| `ALLOW_REGISTRATION` | `true` | Izinkan pendaftaran user. |
| `MAX_UPLOAD_MB` | `6` | Batas ukuran tiap gambar. |
| `IMAGE_WEBP` | `true` | Konversi otomatis gambar ke WebP (butuh GD). |
| `IMAGE_MAX_WIDTH` | `1600` | Lebar maksimal gambar setelah upload (px). |
| `IMAGE_WEBP_QUALITY` | `82` | Kualitas WebP (45–95). |
| `CV_MAX_MB` | `10` | Batas ukuran PDF CV. |
| `RELEASE_MAX_MB` | `50` | Batas ukuran tiap file rilis/unduhan (maks 500). |
| `BACKUP_MAX_MB` | `200` | Batas ukuran ZIP cadangan saat restore (maks 1024). |
| `ACCENT_COLOR` | `#6d8bff` | Warna aksen awal. |
| `TRUST_PROXY` | `true` | Percayai header `X-Forwarded-Proto` (HTTPS). |
| `TELEGRAM_BOT_TOKEN` / `TELEGRAM_CHAT_ID` | — | Notifikasi pesan lewat bot Telegram. |
| `NOTIFY_WEBHOOK_URL` | — | Webhook JSON saat ada pesan kontak baru. |
| `SMTP_HOST` / `SMTP_PORT` / `SMTP_USER` / `SMTP_PASS` / `SMTP_ENCRYPTION` | — | Kirim email notifikasi lewat SMTP. |

> Variabel environment dari panel/Docker **selalu menang** atas isi `.env`.

---

## 👥 Peran pengguna

| Peran | Hak akses |
| ----- | --------- |
| **owner** | Semua: konten, pengguna, pengaturan situs, akun owner. |
| **admin** | Kelola project, profil, keahlian, dan pesan. Tidak bisa mengubah pengguna/pengaturan. |
| **user** | Login dan melihat portofolio di `/dashboard`, mengubah kata sandi sendiri. |

Pengunjung tanpa akun tetap bisa melihat halaman utama (`/`). Pendaftaran hanya membuat role `user`;
role `admin` diberikan owner dari **Admin → Pengguna**.

### Peta URL

| URL | Akses | Keterangan |
| --- | ----- | ---------- |
| `/` | publik | Portofolio lengkap + formulir kontak |
| `/project/{slug}` | publik | Detail project + galeri dokumentasi |
| `/media/{file}` | publik | Gambar upload (validasi nama & tipe) |
| `/manifest.webmanifest` | publik | Manifest PWA (nama, ikon, warna tema) |
| `/sw.js` | publik | Service worker (cache app shell) |
| `/blog` | publik | Daftar artikel blog (cari & filter kategori) |
| `/blog/{slug}` | publik | Detail artikel |
| `/feed.xml` | publik | Feed RSS artikel |
| `/offline` | publik | Halaman cadangan saat offline |
| `/cv` | publik* | Unduh CV PDF (*bila sudah diunggah) |
| `/sitemap.xml` | publik | Sitemap dinamis untuk mesin pencari |
| `/robots.txt` | publik | Aturan crawler + tautan sitemap |
| `/download/{id}` | publik* | Unduh file rilis (*hanya rilis & project berstatus publik; admin selalu bisa) |
| `/login`, `/register` | publik | Autentikasi |
| `/two-factor` | publik* | Verifikasi kode 2FA setelah kata sandi benar |
| `/dashboard` | user | Portofolio versi anggota |
| `/profil` | user | Akun & ganti kata sandi |
| `/admin` | owner/admin | Ringkasan panel |
| `/admin/profile` | owner/admin | Nama, deskripsi, avatar, cover, kontak |
| `/admin/posts` | owner/admin | Kelola artikel blog (draft/publik) |
| `/admin/projects` | owner/admin | CRUD project, cover, dokumentasi |
| `/admin/projects/{id}/releases` | owner/admin | Tambah rilis project + unggah file |
| `/admin/releases/{id}` | owner/admin | Edit rilis, ganti file, publikasi, hapus |
| `/admin/skills` | owner/admin | Keahlian & level |
| `/admin/messages` | owner/admin | Pesan masuk |
| `/admin/users` | **owner** | Kelola akun & role |
| `/admin/backup` | **owner** | Cadangan & restore data |
| `/admin/backup/database` | **owner** | Unduh berkas database SQLite |
| `/admin/backup/archive` | **owner** | Unduh ZIP cadangan lengkap |
| `/admin/stats` | **owner** | Statistik kunjungan |
| `/admin/profile/cv` | owner/admin | Unggah / ganti CV (POST) |
| `/admin/security` | owner/admin | Aktifkan 2FA, kode pemulihan |
| `/admin/activity` | **owner** | Log aktivitas (login, 2FA, perubahan) |
| `/admin/settings` | **owner** | Identitas situs, warna, akun owner |

---

## 🧰 CLI

Jalankan dari root aplikasi (bukan folder `public/`):

```bash
php bin/console list                            # daftar perintah
php bin/console migrate                         # migrasi + seeding ulang
php bin/console owner:create <email> <nama> <sandi>
php bin/console password:reset <email> <sandi>  # reset sandi (lupa password)
php bin/console 2fa:disable <email>             # matikan 2FA (darurat, ponsel hilang)
php bin/console user:list
php bin/console key:generate
php bin/console stats
php bin/console db:reset --force                # hapus semua data
```

Di Pterodactyl, buka tab **Console** server lalu jalankan perintah di atas.

---

## 🧪 CI smoke test

Workflow `.github/workflows/ci.yml` berjalan otomatis pada setiap push ke `main` dan setiap
pull request:

1. `php -l` untuk semua file PHP,
2. migrasi + seed database SQLite baru, cek CLI (`user:list`, `stats`),
3. uji TOTP/base32 dan kode pemulihan,
4. menyalakan `php -S` memakai `router.php` lalu memeriksa `/`, `/?lang=en`, `/login`, `/offline`,
   `/blog`, `/blog?lang=en`, `/feed.xml`, `/sitemap.xml`, `/robots.txt`, `/manifest.webmanifest`,
   halaman project (ID & EN), dan memastikan `/admin` tetap terlindungi,
5. gagal bila ada `PHP Warning`/`Fatal error` di log server.

Jalankan lokal dengan perintah yang sama seperti di workflow, atau minimal:

```bash
cp .env.example .env
php bin/console migrate
php -S 127.0.0.1:8080 -t public router.php
```

---

## 📁 Struktur folder

```
app/                 kode inti (Config, Database, Auth, Router, Validator, Uploader, Image, Visits, Model, Controller)
lang/                kamus antarmuka publik (id.php, en.php)
bin/console          utilitas CLI
public/              document root: index.php, assets, .htaccess
views/               template (layout, halaman publik, blog, admin, auth, error)
storage/             database SQLite, upload gambar, file rilis, sesi, log  ← WAJIB writable, tidak masuk Git
deploy/              egg Pterodactyl, contoh nginx, Dockerfile
router.php           router untuk PHP built-in server
start.sh             entry point (Pterodactyl/Docker)
.env.example         contoh konfigurasi
```

CV disimpan di `storage/documents/` dan dilayani lewat `/cv`.

Gambar yang diunggah disimpan di `storage/uploads/` dan **tidak** dapat diakses langsung;
dilayani lewat route `/media/{file}` dengan validasi nama file, sehingga aman meski
web server tidak diberi konfigurasi tambahan. File rilis disimpan di `storage/releases/`
dan hanya bisa diunduh lewat route `/download/{id}` yang mengecek status publikasi.

---

## 🔐 Catatan keamanan

- Kata sandi disimpan dengan `password_hash()` (bcrypt).
- Semua query memakai prepared statement (PDO).
- CSRF token wajib di setiap POST; token dirotasi saat login.
- Upload divalidasi MIME (`finfo`) + `getimagesize`, nama file diacak, SVG ditolak.
- Session: cookie `HttpOnly` + `SameSite=Lax`, `use_strict_mode`, idle timeout 6 jam.
- Login dibatasi 6 percobaan gagal per email dalam 10 menit.
- 2FA memakai TOTP RFC 6238 (SHA-1, 30 detik, toleransi ±1 langkah). Kode pemulihan disimpan
  sebagai hash bcrypt dan dihapus setelah dipakai.
- Log aktivitas tidak pernah menyimpan kata sandi, kode 2FA, atau isi pesan.
- CV divalidasi MIME PDF + signature `%PDF-`, disimpan di luar document root.
- Statistik kunjungan tidak menyimpan IP, cookie, atau user-agent lengkap.
- Konten artikel disimpan sebagai teks dan di-escape saat ditampilkan (tidak ada HTML mentah).
- Gambar dikonversi ulang lewat GD (bukan mengeksekusi file), nama file selalu diacak.
- Semua output user di-escape dengan `e()`.
- Jangan set `APP_DEBUG=true` di produksi dan jangan bagikan `.env`.

---

## 🩺 Troubleshooting

| Masalah | Solusi |
| ------- | ------ |
| Halaman putih / 500 | Set `APP_DEBUG=true` sementara, lihat `storage/logs/app.log`. |
| "Database belum siap" | Pastikan `storage/` writable; untuk MySQL cek kredensial `DB_*`. |
| Upload gagal | Cek `MAX_UPLOAD_MB`, `upload_max_filesize`, dan permission `storage/uploads`. |
| Upload rilis gagal | Cek `RELEASE_MAX_MB` (maks 500), `upload_max_filesize`/`client_max_body_size`, dan permission `storage/releases`. |
| Session tidak bertahan | Pastikan cookie tidak diblokir; jika HTTPS di balik proxy set `TRUST_PROXY=true`. |
| Lupa kata sandi owner | `php bin/console password:reset email@anda.com SandiBaru123!` |
| Ingin reset total | Hapus `storage/database.sqlite` + `storage/.schema_version`, restart. |

---

## 📄 Lisensi

MIT — bebas dipakai dan dimodifikasi untuk portofolio pribadi maupun komersial.
