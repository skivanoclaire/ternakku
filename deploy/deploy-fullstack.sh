#!/usr/bin/env bash
# Deploy stack penuh TernakKu (Laravel + ML + Nginx + Postgres + Redis) di VM.
# Idempotent: aman dijalankan ulang. Jalankan dari mana saja:
#   bash deploy/deploy-fullstack.sh
set -euo pipefail

# --- ke root repo ---
cd "$(cd "$(dirname "$0")/.." && pwd)"
ROOT="$PWD"
OV="deploy/laravel-overlay"

# --- pilih docker / sudo docker ---
if docker ps >/dev/null 2>&1; then DK="docker"; else DK="sudo docker"; fi
DC="$DK compose --env-file /dev/null"
echo "▶ memakai: $DK"

# --- stack.env (kredensial container; tidak di-commit) ---
if [ ! -f stack.env ]; then
  if [ -f stack.env.example ]; then
    cp stack.env.example stack.env
  else
    # fallback bila contoh tidak ada (mis. fresh clone) — tulis default, GANTI password!
    cat > stack.env <<'EOF'
POSTGRES_DB=ternakku
POSTGRES_USER=ternakku
POSTGRES_PASSWORD=TernakkuDB2026_ganti
APP_ENV=production
ML_URL=http://ml:8000
REDIS_HOST=redis
EOF
  fi
  echo "▶ stack.env dibuat — GANTI POSTGRES_PASSWORD bila perlu"
fi
DBPASS="$(sed -n 's/^POSTGRES_PASSWORD=//p' stack.env)"

# --- hentikan stack ringan (Modul 1) agar port 80/443 bebas ---
$DC -f docker-compose.modul1.yml down 2>/dev/null || true

# --- 1. generate Laravel bila belum lengkap (vendor/ ada = sukses) ---
if [ ! -f web/vendor/autoload.php ]; then
  echo "▶ (re)generate Laravel 11 ke web/ ..."
  rm -rf web && mkdir -p web
  # policy.advisories.block=false: lewati blokir advisory composer pd framework 11.x
  $DK run --rm -v "$ROOT/web":/app -w /app composer:2 sh -c \
     'composer config --global policy.advisories.block false && composer create-project laravel/laravel . "^11.0" --no-interaction'
  # composer menulis sebagai root -> kembalikan kepemilikan ke pemanggil skrip
  $DK run --rm -v "$ROOT/web":/app -w /app composer:2 \
     chown -R "$(id -u)":"$(id -g)" /app
fi

# --- 2. terapkan overlay (kode + view) — rekursif, anti-omission ---
echo "▶ menyalin overlay ..."
cp $OV/Dockerfile             web/Dockerfile
cp -r $OV/app/.               web/app/
cp -r $OV/database/.          web/database/
cp -r $OV/resources/.         web/resources/
cp $OV/routes/web.php         web/routes/web.php

# patch file inti (file utuh, bukan tambal sebagian)
cp $OV/patches/User.php                          web/app/Models/User.php
cp $OV/patches/bootstrap-app.php                 web/bootstrap/app.php
cp $OV/patches/AppServiceProvider.php            web/app/Providers/AppServiceProvider.php
cp $OV/patches/services.php                      web/config/services.php
cp $OV/patches/DatabaseSeeder.php                web/database/seeders/DatabaseSeeder.php

# --- 3. konfigurasi web/.env ---
echo "▶ konfigurasi web/.env ..."
[ -f web/.env ] || cp web/.env.example web/.env
set_env() { local k="$1" v="$2"; if grep -q "^$k=" web/.env; then sed -i "s|^$k=.*|$k=$v|" web/.env; else echo "$k=$v" >> web/.env; fi; }
set_env APP_NAME TernakKu
set_env APP_ENV production
set_env APP_DEBUG false
set_env APP_URL https://ternakku.kaltaraprov.web.id
set_env DB_CONNECTION pgsql
set_env DB_HOST db
set_env DB_PORT 5432
set_env DB_DATABASE ternakku
set_env DB_USERNAME ternakku
set_env DB_PASSWORD "$DBPASS"
set_env SESSION_DRIVER file
set_env CACHE_STORE file
set_env QUEUE_CONNECTION sync
set_env ML_URL http://ml:8000
set_env REDIS_HOST redis
set_env ADMIN_EMAIL admin@ternakku.test
set_env ADMIN_PASSWORD admin12345

# --- 4. build & jalankan ---
echo "▶ build & up ..."
$DC -f docker-compose.yml build app ml
$DC -f docker-compose.yml up -d db redis ml
sleep 8
$DC -f docker-compose.yml up -d app nginx
sleep 5

# --- 5. izin storage + artisan ---
echo "▶ migrasi & seed ..."
$DC -f docker-compose.yml exec -T app chmod -R 777 storage bootstrap/cache || true
$DC -f docker-compose.yml exec -T app php artisan key:generate --force
$DC -f docker-compose.yml exec -T app php artisan config:clear
$DC -f docker-compose.yml exec -T app php artisan migrate --force
$DC -f docker-compose.yml exec -T app php artisan db:seed --force

# --- 6. pastikan model ML terlatih (data persist di ./data) ---
echo "▶ cek/latih model ML ..."
$DC -f docker-compose.yml exec -T ml sh -c 'curl -s http://localhost:8000/health' || true
echo
$DC -f docker-compose.yml exec -T ml sh -c '[ -f /app/data/out/model_modul1.joblib ] || (curl -s -X POST http://localhost:8000/prep && curl -s -X POST http://localhost:8000/train)' || true

echo
echo "✅ selesai. Cek:"
echo "   https://ternakku.kaltaraprov.web.id        (beranda)"
echo "   https://ternakku.kaltaraprov.web.id/login  (admin: admin@ternakku.test / admin12345)"
$DC -f docker-compose.yml ps
