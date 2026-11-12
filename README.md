# Portfolio CMS — PHP 8.3

Website portofolio pribadi dengan halaman login/register untuk **user** dan **owner**.
Halaman publik menampilkan portofolio (nama, deskripsi, project, dokumentasi foto), sedangkan
panel admin dipakai untuk mengelola semuanya — dibuat agar **ringan dan bisa langsung jalan di Pterodactyl**
(tanpa Composer, tanpa build step, database SQLite bawaan).

![PHP](https://img.shields.io/badge/PHP-8.3%2B-777bb4)
![Database](https://img.shields.io/badge/Database-SQLite%20%7C%20MySQL-0ea5e9)
![License](https://img.shields.io/badge/License-MIT-22c55e)

---

## ✨ Fitur

**Halaman publik / user**
- Hero profil: nama, headline, tagline, foto, status ketersediaan, statistik pengalaman.
- Grid project unggulan + arsip project, halaman detail project dengan tech stack & tautan.
- Galeri dokumentasi dengan lightbox (klik gambar untuk memperbesar).
- **Rilis & unduhan per project**: riwayat versi, catatan perubahan (changelog), file unduhan, ukuran, jumlah unduhan, dan penanda versi terbaru.
- Bagian "Tentang" berisi deskripsi panjang dan bar keahlian per kategori.
- Formulir kontak yang tersimpan ke panel admin (dengan honeypot + anti-spam).
- **Tema terang & gelap**: tombol di header/admin, tersimpan di browser, dan otomatis mengikuti
  preferensi sistem (`prefers-color-scheme`) bila belum dipilih.
- **Pencarian project** di beranda (judul, ringkasan, kategori, tech stack) lewat `/?q=kata`.
- **SEO**: `sitemap.xml` dinamis (beranda + semua project terbit) dan `robots.txt` yang menunjuk sitemap.
- Responsif penuh, animasi halus: konten muncul berurutan saat scroll, ripple saat
  elemen diklik, tombol non-aktif bergetar, dan cincin kecil pada klik di area kosong.
  Semua animasi menghormati `prefers-reduced-motion`.
- Tampilan dioptimalkan untuk HP kecil (≤420 px), HP standar, tablet, laptop, dan layar ≥1440 px:
  target sentuh 44 px, input 16 px (anti-zoom iOS), tabel bisa digeser, serta gaya khusus cetak.

**Akun**
- Register user (bisa dimatikan), login, logout, ganti kata sandi, halaman dashboard user.
- Tiga role: `owner` (semua akses), `admin` (kelola konten), `user` (hanya melihat).
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
| `RELEASE_MAX_MB` | `50` | Batas ukuran tiap file rilis/unduhan (maks 500). |
| `ACCENT_COLOR` | `#6d8bff` | Warna aksen awal. |
| `TRUST_PROXY` | `true` | Percayai header `X-Forwarded-Proto` (HTTPS). |

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
| `/sitemap.xml` | publik | Sitemap dinamis untuk mesin pencari |
| `/robots.txt` | publik | Aturan crawler + tautan sitemap |
| `/download/{id}` | publik* | Unduh file rilis (*hanya rilis & project berstatus publik; admin selalu bisa) |
| `/login`, `/register` | publik | Autentikasi |
| `/dashboard` | user | Portofolio versi anggota |
| `/profil` | user | Akun & ganti kata sandi |
| `/admin` | owner/admin | Ringkasan panel |
| `/admin/profile` | owner/admin | Nama, deskripsi, avatar, cover, kontak |
| `/admin/projects` | owner/admin | CRUD project, cover, dokumentasi |
| `/admin/projects/{id}/releases` | owner/admin | Tambah rilis project + unggah file |
| `/admin/releases/{id}` | owner/admin | Edit rilis, ganti file, publikasi, hapus |
| `/admin/skills` | owner/admin | Keahlian & level |
| `/admin/messages` | owner/admin | Pesan masuk |
| `/admin/users` | **owner** | Kelola akun & role |
| `/admin/settings` | **owner** | Identitas situs, warna, akun owner |

---

## 🧰 CLI

Jalankan dari root aplikasi (bukan folder `public/`):

```bash
php bin/console list                            # daftar perintah
php bin/console migrate                         # migrasi + seeding ulang
php bin/console owner:create <email> <nama> <sandi>
php bin/console password:reset <email> <sandi>  # reset sandi (lupa password)
php bin/console user:list
php bin/console key:generate
php bin/console stats
php bin/console db:reset --force                # hapus semua data
```

Di Pterodactyl, buka tab **Console** server lalu jalankan perintah di atas.

---

## 📁 Struktur folder

```
app/                 kode inti (Config, Database, Auth, Router, Validator, Uploader, Model, Controller)
bin/console          utilitas CLI
public/              document root: index.php, assets, .htaccess
views/               template (layout, halaman publik, admin, auth, error)
storage/             database SQLite, upload gambar, file rilis, sesi, log  ← WAJIB writable, tidak masuk Git
deploy/              egg Pterodactyl, contoh nginx, Dockerfile
router.php           router untuk PHP built-in server
start.sh             entry point (Pterodactyl/Docker)
.env.example         contoh konfigurasi
```

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
