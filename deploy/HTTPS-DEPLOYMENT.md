# Deployment HTTPS — JARVIES (AWS EC2 + Nginx + Certbot)

Domain: **dev-help.eclectic.co.id** (JARVIES) → EcoSystem di **dev-me.eclectic.co.id**

Panduan ini melengkapi konfigurasi aplikasi yang sudah diterapkan di kode
(lihat bagian "Perubahan kode" di bawah).

---

## 1. Prasyarat di EC2

```bash
sudo apt update
sudo apt install -y nginx php8.3-fpm php8.3-cli php8.3-mysql php8.3-mbstring \
    php8.3-xml php8.3-curl php8.3-zip unzip git
# Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

> Sesuaikan versi PHP (`php8.3-*`) dengan yang dipakai aplikasi.

## 2. DNS

Arahkan A record domain ke Elastic IP / IP publik EC2:

```
dev-help.eclectic.co.id  ->  A  ->  <IP_PUBLIK_EC2>
```

Pastikan **Security Group EC2** membuka port **80** dan **443** (TCP) dari `0.0.0.0/0`.
Port 80 wajib terbuka agar Certbot bisa menyelesaikan ACME challenge.

## 3. Deploy kode aplikasi

```bash
sudo mkdir -p /var/www/jarvies
sudo chown -R $USER:www-data /var/www/jarvies
git clone <repo-url> /var/www/jarvies
cd /var/www/jarvies

composer install --no-dev --optimize-autoloader

# Salin env produksi (file .env.production sudah disiapkan di repo)
cp .env.production .env
# APP_KEY sudah ada; jika perlu generate baru: php artisan key:generate

# Permission Laravel
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache

# Cache config untuk produksi
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## 4. Pasang vhost Nginx

```bash
sudo cp deploy/nginx/dev-help.eclectic.co.id.conf \
        /etc/nginx/sites-available/dev-help.eclectic.co.id
sudo ln -s /etc/nginx/sites-available/dev-help.eclectic.co.id \
           /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx
```

Verifikasi dulu via HTTP (`http://dev-help.eclectic.co.id`) bahwa app tampil.

## 5. Pasang sertifikat HTTPS (Certbot)

```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d dev-help.eclectic.co.id
```

Certbot otomatis:
- menambahkan blok `listen 443 ssl` + path sertifikat,
- menambahkan redirect **80 → 443**.

Auto-renew sudah aktif via systemd timer. Cek:

```bash
sudo certbot renew --dry-run
```

> Tambahkan `fastcgi_param HTTPS on;` di blok `location ~ \.php$` (lihat contoh
> "SETELAH CERTBOT" pada file vhost) agar PHP-FPM mengabarkan ke Laravel bahwa
> koneksi sudah HTTPS. Lalu `sudo nginx -t && sudo systemctl reload nginx`.

## 6. Verifikasi

```bash
curl -I https://dev-help.eclectic.co.id        # harus 200/302, bukan error TLS
curl -I http://dev-help.eclectic.co.id         # harus 301 -> https
```

Di browser: pastikan ikon gembok muncul, tidak ada warning "mixed content"
di Console (semua aset & link sudah https karena `URL::forceScheme`).

---

## 7. WAJIB: update Redirect URI provider OAuth

Login Google/Azure akan **gagal** jika redirect URI tidak persis sama.
Daftarkan URL HTTPS berikut di dashboard masing-masing provider:

| Provider | Tempat daftar | Redirect URI |
|---|---|---|
| Google | Google Cloud Console → Credentials → OAuth client → Authorized redirect URIs | `https://dev-help.eclectic.co.id/oauth/email/callback/google` |
| Azure  | Azure Portal → App registrations → Authentication → Redirect URIs (Web) | `https://dev-help.eclectic.co.id/oauth/email/callback/azure` |

---

## Perubahan kode yang sudah diterapkan (lapisan aplikasi)

1. **`app/Providers/AppServiceProvider.php`** — `URL::forceScheme('https')`
   dipanggil otomatis bila `APP_URL` diawali `https://`. Membuat semua URL
   (`route()`, `asset()`, link email, redirect OAuth) jadi https. Aman untuk
   dev lokal karena hanya aktif saat `APP_URL` https.

2. **`bootstrap/app.php`** — `trustProxies(at: '*')` (sudah ada sebelumnya):
   Laravel mempercayai header `X-Forwarded-Proto` dari Nginx.

3. **`.env.production`** — template env produksi:
   - `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://dev-help.eclectic.co.id`
   - `SESSION_SECURE_COOKIE=true` (cookie hanya lewat HTTPS)
   - URL EcoSystem & redirect OAuth memakai https.

Tidak ada perubahan logika bisnis — fungsi tiket, email, auth, dll tetap sama.
