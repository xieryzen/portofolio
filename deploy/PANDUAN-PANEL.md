# Panduan deploy — Panel Pterodactyl + `portofolio.xiero.biz.id`

Panduan ini khusus untuk server **Xiero Portofolio** di panel `panel.hexnitystore.com`
(node `basic-1.hexnitystore.com`), dengan domain publik
**`https://portofolio.xiero.biz.id`**.

Aplikasi berjalan sebagai **PHP built-in server** (`bash start.sh`) + **SQLite** di dalam
container, jadi tidak butuh Nginx/PHP-FPM **di dalam** container. Nginx di VPS hanya dipakai
sebagai *reverse proxy* dari domain ke port alokasi server.

> ⚠️ **Jangan simpan password panel, SSH, atau VPS di file repo/README.** Semua kredensial
> disimpan di password manager. Ganti password VPS dan password panel yang pernah dikirim lewat
> chat (lihat bagian **Keamanan** di bawah).

---

## 0. Ringkasan alur

```
git tag vX.Y.Z ──► GitHub Actions "Rilis" ──► portofolio-vX.Y.Z.zip
                                                    │
                          upload via File Manager / SFTP panel
                                                    │
                                   /home/container  (extract, jangan hapus storage/)
                                                    │
                              bash start.sh  ──►  0.0.0.0:$SERVER_PORT
                                                    │
             Nginx VPS: portofolio.xiero.biz.id  ──►  127.0.0.1:$PORT
```

Paket rilis yang dipakai selalu dari halaman **Releases** repo
(`https://github.com/xieryzen/portofolio/releases`). Rilis terbaru saat panduan ini ditulis:
**v1.8.1**.

---

## 1. Cek prasyarat di panel

1. Login panel → **Servers** → pilih **Xiero Portofolio**.
2. Pastikan **Nest/Egg** = `PHP 8.3 - Portfolio CMS` (file `deploy/pterodactyl-egg.json`).
   Bila egg belum ada: **Admin Area → Nests → Import Egg** lalu pilih file itu.
3. Catat **Primary Allocation** (mis. `0.0.0.0:25565` atau `:8080`) — angka port inilah yang
   dipakai reverse proxy.
4. Pastikan limit minimal: **RAM 256 MB**, **disk 512 MB** (disarankan 1 GB agar cukup untuk
   gambar + file rilis + backup).
5. Pastikan server punya **Description** dan **Startup Command** = `bash start.sh`.

---

## 2. Deploy pertama kali (instalasi baru)

### 2a. Upload

**Metode A — File Manager (paling mudah).**

1. Unduh `portofolio-vX.Y.Z.zip` dari GitHub Releases.
2. Panel → server → tab **Files** → **Upload**, lalu **Unarchive** (`... → Unzip`) ke `/`.
3. Struktur akhir harus seperti ini (bukan `portofolio/app`, bukan `public/app`):

```
/home/container/
├── app/        bin/        lang/
├── public/     views/      deploy/
├── start.sh    router.php  Dockerfile
└── storage/    (uploads/ releases/ documents/ sessions/ logs/)
```

4. Jika ZIP diekstrak ke subfolder, pindahkan isinya: buka **Console** lalu

```bash
cd /home/container
mv portofolio-*/* portofolio-*/.[!.]* . 2>/dev/null; rmdir portofolio-* 2>/dev/null
ls -a   # pastikan app/ public/ start.sh ada di sini
```

**Metode B — Console (tanpa upload manual).**

Jalankan di tab **Console** server (ganti versinya bila perlu):

```bash
cd /home/container
V=v1.8.1
curl -fL -o /tmp/app.zip "https://github.com/xieryzen/portofolio/releases/download/$V/portofolio-$V.zip"
unzip -o /tmp/app.zip -d . && rm -f /tmp/app.zip
chmod -R 775 storage
ls -a   # app/ public/ start.sh storage/ harus ada di sini
```

Isi ZIP berada di root (tanpa folder pembungkus), jadi ekstrak langsung ke `/home/container`.
Bila `unzip`/`curl` tidak tersedia, pakai Metode A lewat File Manager.

**Metode C — API panel (tanpa SSH, tanpa upload manual).**

Skrip `deploy/panel-deploy.sh` melakukan semuanya dari komputermu: bikin **backup**,
unduh ZIP rilis terbaru, upload, ekstrak, lalu **restart** server.

1. Panel → klik foto profil → **API Credentials** → *Create API Key* (permission *read & write*).
   Simpan key di password manager, **jangan** di-commit.
2. Jalankan:

```bash
export PTERO_PANEL_URL="https://panel.hexnitystore.com"
export PTERO_SERVER_ID="<uuid server Xiero Portofolio>"
export PTERO_API_KEY="<api key>"

bash deploy/panel-deploy.sh --dry-run     # cek rencana dulu
bash deploy/panel-deploy.sh               # backup + upload + restart
```

Opsi lain: `--tag v1.8.2`, `--no-backup`, `--domain portofolio.xiero.biz.id`.
Skrip butuh `curl`, `python3`, dan `unzip` di komputermu.

> ZIP rilis tidak memuat `storage/` maupun `.env`, jadi database, gambar, file rilis, dan
> konfigurasi tidak tertimpa. Tetap buat backup (skrip melakukannya otomatis) sebelum update.

### 2b. Isi variabel (sekali saja)

Panel → **Startup** → isi variabel egg:

| Variabel | Contoh | Catatan |
| --- | --- | --- |
| `OWNER_NAME` | `Xiero` | Nama pemilik yang tampil di profil |
| `OWNER_EMAIL` | email kamu | Dipakai login owner |
| `OWNER_PASSWORD` | kata sandi kuat (min. 8) | Dibuat sebagai akun owner saat start pertama |
| `APP_ENV` | `production` | Matikan pesan debug |
| `MAX_UPLOAD_MB` | `6` | Batas gambar |
| `RELEASE_MAX_MB` | `50` | Batas file rilis project |

> Ganti `OWNER_PASSWORD` **sebelum** start pertama. `start.sh` membuat `.env` otomatis dengan
> `APP_KEY` acak; bila `OWNER_PASSWORD` masih default, ganti segera setelah login.

### 2c. Start

1. Klik **Start**, buka tab **Console**. Tunggu log:

```
[portfolio] Menjalankan PHP 8.3.x di http://0.0.0.0:<port>
```

2. Uji dari VPS (bukan dari browser dulu):

```bash
curl -sI http://127.0.0.1:<PORT> | head -3     # harap 200/302
curl -s  http://127.0.0.1:<PORT>/ | grep -o '<title>[^<]*'
```

3. Buka `http://<IP-VPS>:<PORT>` → harus muncul halaman portofolio, lalu login di `/login`.

---

## 3. Pasang domain `portofolio.xiero.biz.id`

### 3a. DNS

Di pengelola DNS `xiero.biz.id`, buat:

| Type | Name | Value | Proxy |
| --- | --- | --- | --- |
| `A` | `portofolio` | `38.47.92.214` | **DNS only** (awan abu-abu) saat pertama kali |

Proxy Cloudflare boleh dinyalakan **setelah** sertifikat SSL jadi (lihat 3c). Bila langsung
proxied, set SSL/TLS mode ke **Full (strict)** dan pastikan origin sudah punya sertifikat.

Cek propagasi:

```bash
dig +short portofolio.xiero.biz.id
```

### 3b. Reverse proxy Nginx di VPS

File contoh sudah disiapkan: `deploy/nginx-proxy-portofolio.conf.example`. Salin ke
`/etc/nginx/sites-available/portofolio.xiero.biz.id`, sesuaikan `proxy_pass` dengan port
alokasi panel, lalu aktifkan:

```bash
sudo ln -s /etc/nginx/sites-available/portofolio.xiero.biz.id /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx
```

Isi inti konfigurasi:

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name portofolio.xiero.biz.id;

    client_max_body_size 60M;   # samakan dengan RELEASE_MAX_MB + buffer
    proxy_read_timeout 120s;

    location / {
        proxy_pass http://127.0.0.1:PORT_ALOKASI;
        proxy_http_version 1.1;
        proxy_set_header Host              $host;
        proxy_set_header X-Real-IP         $remote_addr;
        proxy_set_header X-Forwarded-For   $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_set_header Upgrade           $http_upgrade;
        proxy_set_header Connection        "upgrade";
    }
}
```

> **Penting:** port di panel di-bind ke IP node. Bila Wings berjalan di VPS yang sama,
> `127.0.0.1:PORT` bisa dipakai. Bila tidak, ganti dengan IP publik VPS: `38.47.92.214:PORT`.

### 3c. HTTPS

```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d portofolio.xiero.biz.id --redirect -m <email-kamu> --agree-tos
sudo systemctl status certbot.timer   # perpanjangan otomatis
```

Setelah HTTPS aktif, isi `.env` di server (Files → `.env`):

```env
APP_ENV=production
APP_URL=https://portofolio.xiero.biz.id
TRUST_PROXY=true          # wajib: ada Nginx/Cloudflare di depan aplikasi
```

lalu **Restart** server dari panel.

### 3d. Verifikasi domain

```bash
curl -sI https://portofolio.xiero.biz.id | head -5
curl -s  https://portofolio.xiero.biz.id/sitemap.xml | head -5
curl -sI https://portofolio.xiero.biz.id/admin | head -3    # harus 302 ke /login
```

---

## 4. Update ke rilis baru (tanpa kehilangan data)

Cara tercepat: `bash deploy/panel-deploy.sh` (Metode C) — sudah termasuk backup otomatis,
upload, ekstrak, dan restart. Langkah manualnya:

1. **Backup dulu**: panel → **Backups** → *Create backup*, atau unduh
   `storage/database.sqlite` + folder `storage/uploads/` lewat File Manager.
2. Unduh ZIP rilis baru, upload ke `/`, lalu **Unarchive** — timpa file lama
   (*overwrite*). **Jangan hapus folder `storage/`.**
3. Restart server. Migrasi database berjalan otomatis saat halaman pertama dibuka
   (schema naik sendiri, mis. v5 → v6 pada rilis v1.8.0).
4. Cek: `/` , `/blog` , `/admin` , `/admin/stats`. Bila ada masalah, ekstrak ulang ZIP versi
   sebelumnya lalu restore database dari langkah 1.

> File `.env` dan folder `storage/` **tidak** ada di dalam ZIP rilis, jadi aman dari timpa —
> tetap jangan dihapus manual.

---

## 5. Checklist setelah live

- [ ] Login owner, ganti kata sandi (`/profil`), aktifkan **2FA** (`/admin/security`).
- [ ] **Admin → Pengaturan**: judul situs, meta description, warna aksen, footer.
- [ ] **Admin → Profil**: nama, headline, foto, kontak, sosial media.
- [ ] Unggah **CV PDF** (muncul tombol Unduh CV di hero).
- [ ] Hapus contoh project/artikel yang tidak dipakai; buat konten asli.
- [ ] Uji kirim pesan kontak → muncul di `/admin/messages`.
- [ ] Uji komentar artikel → muncul sebagai "menunggu" di `/admin/comments`.
- [ ] Buka `/admin/stats`, cek grafik & **Ekspor CSV**.
- [ ] Cek `https://portofolio.xiero.biz.id/robots.txt` dan `/sitemap.xml`.
- [ ] Pastikan `storage/logs/app.log` tidak berisi error.
- [ ] Schedule backup mingguan di panel (retensi 2–3 terakhir saja agar disk tidak penuh).

---

## 6. Kontrol dari GitHub Actions (bila SSH lokal tidak bisa)

Workflow **VPS** (`.github/workflows/vps.yml`) menjalankan tugas di server dari infrastruktur
GitHub, yang punya akses internet penuh:

1. **Settings → Secrets and variables → Actions**, tambahkan `VPS_SSH_KEY` (kunci privat,
   disarankan) **atau** `VPS_SSH_PASSWORD`; opsional `VPS_HOST`, `VPS_USER`.
   Untuk deploy tanpa SSH: `PTERO_SERVER_ID` + `PTERO_API_KEY`.
2. Tab **Actions** → **VPS** → *Run workflow*, pilih tugas:
   `cek`, `info` (baca keadaan server), `deploy-ssh` (konfirmasi `DEPLOY`),
   `bersih-laporan`, `bersih-hapus` (konfirmasi `BERSIHKAN`), atau `deploy-panel`.
3. Log job menampilkan hasil; kredensial otomatis di-mask.

---

## 7. Keamanan

- Password VPS/panel/owner yang pernah dikirim lewat chat **anggap bocor**: ganti password SSH
  (`passwd`), ganti password akun panel, dan ganti kata sandi owner di aplikasi.
- Matikan login root via password: pakai SSH key, `PermitRootLogin prohibit-password`
  di `/etc/ssh/sshd_config`, lalu `systemctl restart ssh`.
- Pasang `fail2ban` untuk SSH dan panel.
- Batasi akses panel (Cloudflare Access / allowlist IP) bila hanya dipakai sendiri.
- Jangan menaruh `.env`, `storage/database.sqlite`, atau backup di folder yang bisa diakses web.

---

## 8. Troubleshooting

| Gejala | Penyebab umum | Solusi |
| --- | --- | --- |
| Container langsung berhenti | `start.sh` tidak ada / salah folder | Pastikan `start.sh` di `/home/container`, startup `bash start.sh` |
| `502 Bad Gateway` dari domain | Port proxy salah / server mati | Cek port alokasi & status server di panel |
| Halaman putih / 500 | Permission `storage/` | Console: `chmod -R 775 storage` lalu restart |
| Upload gambar gagal | Batas PHP terlalu kecil | Naikkan `MAX_UPLOAD_MB` / `RELEASE_MAX_MB`, restart |
| Data hilang setelah update | Folder `storage/` terhapus | Restore backup dari panel |
| Domain tidak terbuka | DNS belum propagasi / proxy | `dig +short`, coba mode DNS only |
| Sertifikat gagal | Port 80 tertutup / proxied | Matikan proxy Cloudflare sementara, ulangi certbot |
