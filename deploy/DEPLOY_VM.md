# Deploy TernakKu ke VM (subdomain .web.id)

Panduan ringkas dari VM kosong → platform jalan, memakai repo ini langsung (git clone),
bukan menyalin file manual. Stack: Nginx + Laravel 11 (app) + FastAPI (ml) + PostgreSQL 16 + Redis 7.

| Variabel | Nilai |
|---|---|
| DOMAIN | `ternakku.kaltaraprov.web.id` |
| IP_PUBLIK | `103.156.110.104` |
| EMAIL_TLS | `spbekaltaradkisp@gmail.com` |

> Catatan: domain `.web.id` (bukan `.go.id`) → pakai Let's Encrypt/Certbot biasa,
> tidak ada wildcard dari DKISP, dan tidak terikat syarat formal SPBE.

---

## 1. Siapkan VM & Docker
```bash
sudo apt update && sudo apt -y upgrade
sudo apt -y install git curl ufw
curl -fsSL https://get.docker.com | sudo sh
sudo usermod -aG docker $USER && newgrp docker
docker --version && docker compose version
```

## 2. Ambil kode & siapkan .env
```bash
cd ~ && git clone https://github.com/skivanoclaire/ternakku.git
cd ~/ternakku
cp .env.example .env
nano .env                      # ganti POSTGRES_PASSWORD jadi kuat
```

## 3. Arahkan DNS (di pengelola kaltaraprov.web.id)
Buat A record:  `ternakku.kaltaraprov.web.id  →  103.156.110.104`
Verifikasi sebelum lanjut TLS:
```bash
dig +short ternakku.kaltaraprov.web.id        # harus mengembalikan 103.156.110.104
```

## 4. Firewall
```bash
sudo ufw allow OpenSSH
sudo ufw allow 80,443/tcp
sudo ufw enable && sudo ufw status
```

## 5. Bangun service ML & latih model (VM latih sendiri)
```bash
docker compose build ml
docker compose up -d db redis ml
# bersihkan data publik lalu latih (data sudah ada di repo: data/raw/)
docker compose exec ml sh -c 'curl -s -X POST http://localhost:8000/prep'
docker compose exec ml sh -c 'curl -s -X POST http://localhost:8000/train'
docker compose exec ml sh -c 'curl -s http://localhost:8000/health'   # {"model_ada": true}
```

## 6. Generate Laravel & terapkan overlay
```bash
cd ~/ternakku
# 6a. generate Laravel 11 ke web/ (folder harus kosong)
docker run --rm -v "$PWD/web":/app -w /app composer:2 \
  create-project laravel/laravel . "^11.0"

# 6b. aktifkan routing API (Laravel 11)
docker run --rm -v "$PWD/web":/app -w /app composer:2 php artisan install:api

# 6c. salin file overlay ke posisinya
cp deploy/laravel-overlay/Dockerfile web/Dockerfile
mkdir -p web/app/Services web/app/Http/Controllers/Api
cp deploy/laravel-overlay/app/Services/MlClient.php          web/app/Services/
cp deploy/laravel-overlay/app/Http/Controllers/Api/EstimasiController.php web/app/Http/Controllers/Api/
cp deploy/laravel-overlay/database/migrations/*.php          web/database/migrations/

# 6d. daftarkan route + config (gabungkan manual / append)
cat deploy/laravel-overlay/routes/api-ternakku.php >> web/routes/api.php
#  lalu EDIT web/config/services.php: tambah blok 'ml' => ['url' => env('ML_URL','http://ml:8000')],

# 6e. set koneksi DB Laravel ke service docker
cd web
sed -i 's/^DB_CONNECTION=.*/DB_CONNECTION=pgsql/' .env
sed -i 's/^DB_HOST=.*/DB_HOST=db/'               .env
sed -i 's/^DB_PORT=.*/DB_PORT=5432/'             .env
sed -i 's/^DB_DATABASE=.*/DB_DATABASE=ternakku/' .env
sed -i 's/^DB_USERNAME=.*/DB_USERNAME=ternakku/' .env
sed -i 's/^# *DB_PASSWORD=.*/DB_PASSWORD=ganti_password_kuat_ini/' .env   # samakan dgn root .env
echo "ML_URL=http://ml:8000"  >> .env
echo "REDIS_HOST=redis"        >> .env
echo "APP_URL=https://ternakku.kaltaraprov.web.id" >> .env
cd ..

# 6f. build & migrasi
docker compose build app
docker compose up -d app
docker compose exec app php artisan migrate --force
```

## 7. TLS (Certbot, Let's Encrypt)
```bash
mkdir -p nginx/certbot/www nginx/certbot/conf
docker compose up -d nginx                  # melayani HTTP untuk ACME
docker run --rm \
  -v "$PWD/nginx/certbot/conf":/etc/letsencrypt \
  -v "$PWD/nginx/certbot/www":/var/www/certbot \
  certbot/certbot certonly --webroot -w /var/www/certbot \
  -d ternakku.kaltaraprov.web.id \
  --email spbekaltaradkisp@gmail.com --agree-tos --no-eff-email
docker compose restart nginx
```

## 8. Nyalakan semua & uji
```bash
docker compose up -d
docker compose ps                            # semua "running"

# uji API estimasi bobot lewat domain publik
curl -X POST https://ternakku.kaltaraprov.web.id/api/estimasi/bobot \
  -H "Content-Type: application/json" \
  -d '{"lingkar_dada_cm":195,"panjang_badan_cm":165,"tinggi_gumba_cm":128}'
```
Buka `https://ternakku.kaltaraprov.web.id` → halaman Laravel via HTTPS.

## 9. Keamanan & backup
```bash
# backup PostgreSQL harian
( crontab -l 2>/dev/null; echo "0 2 * * * cd ~/ternakku && docker compose exec -T db pg_dump -U ternakku ternakku | gzip > ~/ternakku/data/backup_\$(date +\%F).sql.gz" ) | crontab -
```
Checklist: TLS+HSTS aktif · `ml`/`db`/`redis` internal-only (tanpa `ports:`) · auth Laravel + rate limit · backup terjadwal.

---

## Latih ulang model nanti
Setelah ada data baru atau ganti metode:
```bash
docker compose exec ml sh -c 'curl -s -X POST "http://localhost:8000/train?best=rf"'
```
Atau di Colab (reproducible, untuk presentasi): jalankan `notebooks/TernakKu_Modul1_Colab.ipynb`.
