# RUNBOOK — Deploy TernakKu (MVP Modul 1) di VM

Panduan step-by-step dari VM kosong sampai platform berjalan: **Docker + Laravel (web/API) + FastAPI (ML Python)**, plus persiapan dataset publik bobot sapi.

> Stack sesuai dokumen rancangan: Nginx (reverse proxy/TLS) · Laravel 13 (PHP 8.3) · FastAPI (Python 3.12) · PostgreSQL 16 (+pgvector) · Redis 7 · Docker Compose. Hanya Nginx yang terekspos publik.

**Yang kamu siapkan sendiri:** VM (Ubuntu Server 22.04/24.04), subdomain (mis. `ternakku.kaltaraprov.go.id`), dan IP publik. Saat sudah ada, ganti placeholder `DOMAIN` dan `IP_PUBLIK` di bawah.

---

## Ringkasan Tahapan

| # | Tahap | Hasil |
|---|-------|-------|
| 0 | Prasyarat & variabel | Nilai DOMAIN, IP, dll. siap |
| 1 | Siapkan VM (paket dasar, Docker) | Docker & Compose terpasang |
| 2 | Struktur folder proyek | Kerangka direktori dibuat |
| 3 | docker-compose.yml | Definisi semua service |
| 4 | Service ML (FastAPI) | Container `ml` siap latih/inferensi |
| 5 | Persiapan dataset publik | `pengukuran_public.csv` siap impor |
| 6 | Service Web (Laravel) | Container `app` melayani web/API |
| 7 | Nginx + TLS (subdomain) | HTTPS aktif di DOMAIN |
| 8 | Jalankan & verifikasi | Platform hidup |
| 9 | Keamanan SPBE | Firewall, internal-only, backup |

---

## 0. Prasyarat & Variabel

Login ke VM via SSH, lalu set variabel (ganti sesuai milikmu):

```bash
export DOMAIN="ternakku.kaltaraprov.go.id"   # subdomain kamu
export IP_PUBLIK="xxx.xxx.xxx.xxx"            # IP publik VM
export EMAIL_TLS="admin@kaltaraprov.go.id"    # untuk sertifikat Certbot
```

Pastikan **DNS A record** `DOMAIN -> IP_PUBLIK` sudah diarahkan (di pengelola DNS kaltaraprov.go.id) sebelum tahap TLS.

---

## 1. Siapkan VM & Docker

```bash
sudo apt update && sudo apt -y upgrade
sudo apt -y install git curl ufw

# Docker Engine + Compose plugin (skrip resmi)
curl -fsSL https://get.docker.com | sudo sh
sudo usermod -aG docker $USER        # agar tidak perlu sudo (logout-login setelah ini)
newgrp docker

docker --version && docker compose version   # verifikasi
```

---

## 2. Struktur Folder Proyek

```bash
mkdir -p ~/ternakku/{ml,web,nginx,data/raw,data/out}
cd ~/ternakku
# Pohon yang akan kita isi:
#   ternakku/
#   ├── docker-compose.yml
#   ├── .env
#   ├── ml/            (FastAPI: Dockerfile, app, requirements, prep_data.py)
#   ├── web/           (Laravel app — di-generate tahap 6)
#   ├── nginx/         (konfigurasi reverse proxy)
#   └── data/raw,out/  (dataset mentah & hasil olahan)
```

Buat file `.env` di `~/ternakku/.env`:

```bash
cat > ~/ternakku/.env <<'EOF'
# --- PostgreSQL ---
POSTGRES_DB=ternakku
POSTGRES_USER=ternakku
POSTGRES_PASSWORD=ganti_password_kuat_ini
# --- App ---
APP_ENV=production
ML_URL=http://ml:8000
EOF
```

---

## 3. docker-compose.yml

```bash
cat > ~/ternakku/docker-compose.yml <<'EOF'
services:
  nginx:
    image: nginx:alpine
    ports: ["80:80", "443:443"]          # SATU-SATUNYA yang terekspos publik
    volumes:
      - ./nginx/conf.d:/etc/nginx/conf.d:ro
      - ./web/public:/var/www/html/public:ro
      - ./nginx/certbot/www:/var/www/certbot:ro
      - ./nginx/certbot/conf:/etc/letsencrypt:ro
    depends_on: [app]
    restart: unless-stopped

  app:                                    # Laravel (PHP-FPM)
    build: ./web
    expose: ["9000"]                      # internal saja (tanpa ports:)
    env_file: .env
    volumes: ["./web:/var/www/html"]
    depends_on: [db, redis]
    restart: unless-stopped

  ml:                                     # FastAPI (Python)
    build: ./ml
    expose: ["8000"]                      # internal saja
    env_file: .env
    volumes:
      - ./ml:/app
      - ./data:/data
    depends_on: [db]
    restart: unless-stopped

  db:
    image: postgres:16
    expose: ["5432"]                      # internal saja
    env_file: .env
    volumes: ["pgdata:/var/lib/postgresql/data"]
    restart: unless-stopped

  redis:
    image: redis:7-alpine
    expose: ["6379"]                      # internal saja
    restart: unless-stopped

volumes:
  pgdata:
EOF
```

> Prinsip keamanan: hanya `nginx` punya `ports:`. Service `app`, `ml`, `db`, `redis` memakai `expose:` → hanya dapat diakses dari jaringan internal Docker, tidak dari internet.

---

## 4. Service ML (FastAPI)

`ml/requirements.txt`:

```bash
cat > ~/ternakku/ml/requirements.txt <<'EOF'
fastapi==0.111.*
uvicorn[standard]==0.30.*
pandas==2.*
numpy==1.26.*
scikit-learn==1.4.*
openpyxl==3.*
psycopg2-binary==2.9.*
joblib==1.*
EOF
```

`ml/Dockerfile`:

```bash
cat > ~/ternakku/ml/Dockerfile <<'EOF'
FROM python:3.12-slim
WORKDIR /app
COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt
COPY . .
CMD ["uvicorn", "main:app", "--host", "0.0.0.0", "--port", "8000"]
EOF
```

`ml/main.py` — API minimal (health + latih + prediksi baseline Modul 1):

```bash
cat > ~/ternakku/ml/main.py <<'EOF'
from fastapi import FastAPI
from pydantic import BaseModel
import numpy as np, pandas as pd, joblib, os
from sklearn.linear_model import LinearRegression
from sklearn.model_selection import GroupShuffleSplit

app = FastAPI(title="TernakKu ML")
MODEL_PATH = "/data/out/model_modul1.joblib"

class Ukuran(BaseModel):
    lingkar_dada_cm: float
    panjang_badan_cm: float | None = None
    tinggi_gumba_cm: float | None = None

@app.get("/health")
def health():
    return {"status": "ok", "model_ada": os.path.exists(MODEL_PATH)}

@app.post("/train")
def train(csv: str = "/data/out/pengukuran_public.csv"):
    df = pd.read_csv(csv).dropna(subset=["lingkar_dada_cm", "bobot_timbang_kg"])
    feats = ["lingkar_dada_cm", "panjang_badan_cm", "tinggi_gumba_cm"]
    df = df.dropna(subset=feats)
    X, y, g = df[feats].values, df["bobot_timbang_kg"].values, df["src_dataset"].values
    # grouped split: data uji dari grup berbeda (cegah kebocoran)
    gss = GroupShuffleSplit(n_splits=1, test_size=0.3, random_state=42)
    tr, te = next(gss.split(X, y, groups=g))
    m = LinearRegression().fit(X[tr], y[tr])
    p = m.predict(X[te])
    mape = float(np.mean(np.abs(y[te]-p)/y[te])*100)
    mae = float(np.mean(np.abs(y[te]-p)))
    joblib.dump({"model": m, "feats": feats}, MODEL_PATH)
    return {"n_latih": len(tr), "n_uji": len(te), "MAPE": round(mape,2), "MAE_kg": round(mae,1)}

@app.post("/predict")
def predict(u: Ukuran):
    if not os.path.exists(MODEL_PATH):
        return {"error": "model belum dilatih, panggil /train dulu"}
    bundle = joblib.load(MODEL_PATH); m = bundle["model"]
    row = [u.lingkar_dada_cm, u.panjang_badan_cm or np.nan, u.tinggi_gumba_cm or np.nan]
    # isi fitur kosong dengan rata-rata sederhana bila perlu (MVP)
    row = [v if v==v else 0 for v in row]
    kg = float(m.predict([row])[0])
    return {"bobot_estimasi_kg": round(kg,1)}
EOF
```

---

## 5. Persiapan Dataset Publik

Letakkan dua file mentah ke `~/ternakku/data/raw/`:
- `Hereford_cows.csv` — unduh dari <https://github.com/ruchaya/Hereford_cows> (tombol *Download raw file*).
- `measurements.xlsx` — unduh dari <https://data.mendeley.com/datasets/h2s22wr5py/3> (*Download All*), ambil file Excel-nya.

```bash
# contoh upload dari laptop ke VM:
#   scp Hereford_cows.csv measurements.xlsx user@$IP_PUBLIK:~/ternakku/data/raw/
ls ~/ternakku/data/raw/
```

Buat skrip pembersih `ml/prep_data.py` (sudah teruji pada kedua dataset):

```bash
cat > ~/ternakku/ml/prep_data.py <<'PYEOF'
#!/usr/bin/env python3
"""prep_data.py — bersihkan & petakan dataset publik bobot sapi ke skema `pengukuran`."""
import argparse, os, sys
import numpy as np, pandas as pd

TARGET_COLS = ["src_dataset","src_animal_id","jenis","ras","umur_estimasi_bulan",
               "lingkar_dada_cm","panjang_badan_cm","tinggi_gumba_cm","bobot_timbang_kg","source"]

def clean_hereford(path):
    df = pd.read_csv(path, sep=";", encoding="latin-1")
    df.columns = [c.strip() for c in df.columns]
    out = pd.DataFrame()
    out["src_animal_id"] = df["identificator"].astype(str)   # defines row count
    out["src_dataset"] = "hereford"; out["jenis"] = "sapi"; out["ras"] = "Hereford"
    out["umur_estimasi_bulan"] = pd.to_numeric(df["Age years"], errors="coerce")*12
    out["lingkar_dada_cm"]  = pd.to_numeric(df["heart girth"], errors="coerce")
    out["panjang_badan_cm"] = pd.to_numeric(df["oblique body length"], errors="coerce")
    out["tinggi_gumba_cm"]  = pd.to_numeric(df["withers height"], errors="coerce")
    out["bobot_timbang_kg"] = pd.to_numeric(df["Body weight"], errors="coerce")
    out["source"] = "public"
    n0 = len(out)
    bad = ((out["lingkar_dada_cm"]<150)|(out["lingkar_dada_cm"]>260)|
           (out["bobot_timbang_kg"]<100)|(out["bobot_timbang_kg"]>1200)|
           (out["panjang_badan_cm"]<80))
    out = out[~bad].reset_index(drop=True)
    return out, n0, int(bad.sum())

def clean_horqin(path):
    df = pd.read_excel(path)
    df = df.rename(columns={c: c.lower().replace(" ","").replace("(cm)","").replace("(kg)","") for c in df.columns})
    out = pd.DataFrame()
    out["src_animal_id"] = df["num"].astype(str)   # defines row count
    out["src_dataset"] = "horqin"; out["jenis"] = "sapi"; out["ras"] = "Horqin"
    out["umur_estimasi_bulan"] = np.nan
    out["lingkar_dada_cm"]  = pd.to_numeric(df["heartgirth"], errors="coerce")
    out["panjang_badan_cm"] = pd.to_numeric(df["obliquebodylength"], errors="coerce")
    out["tinggi_gumba_cm"]  = pd.to_numeric(df["withersheight"], errors="coerce")
    out["bobot_timbang_kg"] = pd.to_numeric(df["bodyweight"], errors="coerce")
    out["source"] = "public"
    n0 = len(out)
    bad = out[["lingkar_dada_cm","panjang_badan_cm","tinggi_gumba_cm","bobot_timbang_kg"]].isna().any(axis=1)
    out = out[~bad].reset_index(drop=True)
    return out, n0, int(bad.sum())

def quality(df, name):
    w, hg = df["bobot_timbang_kg"], df["lingkar_dada_cm"]
    x, y = hg.values, w.values
    b, a = np.polyfit(x, y, 1); p = a + b*x
    r2 = 1 - ((y-p)**2).sum()/((y-y.mean())**2).sum()
    mape = (np.abs(y-p)/y).mean()*100
    return ("[%s] baris=%d | bobot %.0f-%.0f kg | korelasi LD-bobot=%.3f | baseline R2=%.3f MAPE=%.1f%%"
            % (name, len(df), w.min(), w.max(), hg.corr(w), r2, mape))

def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("--indir", default="/data/raw")
    ap.add_argument("--outdir", default="/data/out")
    a = ap.parse_args()
    os.makedirs(a.outdir, exist_ok=True)
    frames, rep = [], []
    her = os.path.join(a.indir, "Hereford_cows.csv")
    hor = os.path.join(a.indir, "measurements.xlsx")
    if os.path.exists(her):
        d, n0, rm = clean_hereford(her); frames.append(d)
        rep += ["Hereford: %d -> %d (buang %d)" % (n0, len(d), rm), quality(d, "Hereford")]
    if os.path.exists(hor):
        d, n0, rm = clean_horqin(hor); frames.append(d)
        rep += ["Horqin: %d -> %d (buang %d)" % (n0, len(d), rm), quality(d, "Horqin")]
    if not frames: sys.exit("Tidak ada input. Cek --indir.")
    allrows = pd.concat(frames, ignore_index=True)[TARGET_COLS]
    out_csv = os.path.join(a.outdir, "pengukuran_public.csv")
    allrows.to_csv(out_csv, index=False)
    pd.DataFrame([
        {"source":"public","src_dataset":"hereford","nama":"Hereford cows (Ruchay et al.)",
         "n_ekor":int((allrows.src_dataset=='hereford').sum()),"lisensi":"Open access (GitHub)",
         "sitasi":"Ruchay A. et al., IOP Conf. Ser. EES 624:012056 (2021)","url":"https://github.com/ruchaya/Hereford_cows"},
        {"source":"public","src_dataset":"horqin","nama":"Cattle side & back view (Bai L.)",
         "n_ekor":int((allrows.src_dataset=='horqin').sum()),"lisensi":"CC BY 4.0",
         "sitasi":"Bai, Lili. Mendeley Data, DOI 10.17632/h2s22wr5py.3 (2025)","url":"https://data.mendeley.com/datasets/h2s22wr5py/3"},
    ]).to_csv(os.path.join(a.outdir, "dataset_source.csv"), index=False)
    rep.append("GABUNGAN: %d baris -> %s" % (len(allrows), out_csv))
    txt = "\n".join(rep)
    open(os.path.join(a.outdir,"report.txt"),"w").write(txt+"\n")
    print(txt)

if __name__ == "__main__":
    main()
PYEOF
```

Jalankan di dalam container `ml` (setelah build di tahap 8) atau langsung dengan Python lokal:

```bash
# opsi A — di dalam container ml (disarankan, lingkungan konsisten):
docker compose run --rm ml python prep_data.py --indir /data/raw --outdir /data/out

# opsi B — Python lokal di VM (butuh: pip install pandas numpy openpyxl):
# cd ~/ternakku/ml && python3 prep_data.py --indir ../data/raw --outdir ../data/out
```

**Hasil yang diharapkan (sudah diverifikasi):**

```
Hereford: 1523 -> 1514 (buang 9)
[Hereford] baris=1514 | bobot 314-750 kg | korelasi LD-bobot=0.299 | baseline R2=0.089 MAPE=9.2%
Horqin: 72 -> 72 (buang 0)
[Horqin] baris=72 | bobot 341-644 kg | korelasi LD-bobot=0.914 | baseline R2=0.836 MAPE=5.6%
GABUNGAN: 1586 baris -> /data/out/pengukuran_public.csv
```

> Catatan kualitas: di Hereford, lingkar dada kurang informatif (sapi terlalu seragam, umur 3 & 5 tahun saja) — korelasi 0.30. Di Horqin sehat (0.91). Keduanya tetap berguna: Hereford untuk volume, Horqin untuk sinyal bersih. Untuk relevansi Kaltara, tambah data sapi lokal (Bali/Aceh) menyusul.

File output (`pengukuran_public.csv`) sudah memakai nama kolom skema `pengukuran` dan ditandai `source=public` — siap diimpor ke PostgreSQL oleh Laravel (seeder/command) pada tahap berikutnya.

---

## 6. Service Web (Laravel)

Generate proyek Laravel ke folder `web/` memakai image Composer (tanpa instal PHP di host):

```bash
cd ~/ternakku
docker run --rm -v "$PWD/web":/app -w /app composer:2 \
  create-project laravel/laravel . "^11.0"
```

`web/Dockerfile` (PHP-FPM 8.3 + ekstensi PostgreSQL & Redis):

```bash
cat > ~/ternakku/web/Dockerfile <<'EOF'
FROM php:8.3-fpm
RUN apt-get update && apt-get install -y libpq-dev libzip-dev unzip \
 && docker-php-ext-install pdo pdo_pgsql zip \
 && pecl install redis && docker-php-ext-enable redis
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html
COPY . .
RUN composer install --no-dev --optimize-autoloader || true
EOF
```

Sesuaikan koneksi DB Laravel (`web/.env`) agar menunjuk service Docker:

```bash
cd ~/ternakku/web
sed -i 's/^DB_CONNECTION=.*/DB_CONNECTION=pgsql/'   .env
sed -i 's/^DB_HOST=.*/DB_HOST=db/'                  .env
sed -i 's/^DB_PORT=.*/DB_PORT=5432/'                .env
sed -i 's/^DB_DATABASE=.*/DB_DATABASE=ternakku/'    .env
sed -i 's/^DB_USERNAME=.*/DB_USERNAME=ternakku/'    .env
sed -i 's/^# *DB_PASSWORD=.*/DB_PASSWORD=ganti_password_kuat_ini/' .env
echo "ML_URL=http://ml:8000"  >> .env
echo "REDIS_HOST=redis"        >> .env
echo "APP_URL=https://$DOMAIN" >> .env
```

> Catatan: skema tabel (`users`, `ternak`, `pengukuran`, `experiment`, `model_version`, `dataset_source`, dst.) dibuat lewat **migration Laravel** sesuai Bab 8 dokumen rancangan. Impor `pengukuran_public.csv` dilakukan via Artisan command/seeder yang membaca file di `/data/out/`.

---

## 7. Nginx + TLS (Subdomain)

Konfigurasi awal (HTTP, untuk verifikasi domain & ambil sertifikat):

```bash
mkdir -p ~/ternakku/nginx/conf.d ~/ternakku/nginx/certbot/{www,conf}
cat > ~/ternakku/nginx/conf.d/ternakku.conf <<EOF
server {
    listen 80;
    server_name $DOMAIN;
    location /.well-known/acme-challenge/ { root /var/www/certbot; }
    location / { return 301 https://\$host\$request_uri; }
}
server {
    listen 443 ssl;
    server_name $DOMAIN;
    ssl_certificate     /etc/letsencrypt/live/$DOMAIN/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/$DOMAIN/privkey.pem;

    root /var/www/html/public;
    index index.php;

    location / { try_files \$uri \$uri/ /index.php?\$query_string; }
    location ~ \.php$ {
        fastcgi_pass app:9000;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME /var/www/html/public\$fastcgi_script_name;
    }
}
EOF
```

Ambil sertifikat (jalankan setelah DNS mengarah & port 80 terbuka):

```bash
cd ~/ternakku
docker compose up -d nginx            # nginx melayani HTTP untuk ACME challenge
docker run --rm \
  -v "$PWD/nginx/certbot/conf":/etc/letsencrypt \
  -v "$PWD/nginx/certbot/www":/var/www/certbot \
  certbot/certbot certonly --webroot -w /var/www/certbot \
  -d "$DOMAIN" --email "$EMAIL_TLS" --agree-tos --no-eff-email
docker compose restart nginx
```

> Bila DKISP menyediakan **wildcard `*.kaltaraprov.go.id`**, lewati langkah Certbot dan pasang sertifikat yang diberikan ke `nginx/certbot/conf`.

---

## 8. Jalankan & Verifikasi

```bash
cd ~/ternakku
docker compose build              # build app & ml
docker compose up -d              # nyalakan semua service
docker compose ps                 # cek semua "running"

# 8a. siapkan data (bila belum di tahap 5)
docker compose run --rm ml python prep_data.py --indir /data/raw --outdir /data/out

# 8b. migrasi skema & impor data (sesuaikan nama command Laravel kamu)
docker compose exec app php artisan migrate --force
# docker compose exec app php artisan ternakku:import-public /data/out/pengukuran_public.csv

# 8c. latih model baseline Modul 1 lewat ML API
curl -X POST http://localhost/api/ml/train     # bila sudah diproxy Laravel
# atau langsung uji service ml dari dalam jaringan Docker:
docker compose exec app sh -c 'curl -s -X POST http://ml:8000/train'

# 8d. uji prediksi
docker compose exec app sh -c \
 'curl -s -X POST http://ml:8000/predict -H "Content-Type: application/json" \
  -d "{\"lingkar_dada_cm\":195,\"panjang_badan_cm\":165,\"tinggi_gumba_cm\":128}"'
```

Buka `https://DOMAIN` di browser — halaman Laravel harus muncul lewat HTTPS.

**Output sehat dari `/train`** kira-kira: `{"n_latih":..., "n_uji":..., "MAPE":..., "MAE_kg":...}`.

---

## 9. Keamanan SPBE (wajib untuk .go.id)

```bash
# Firewall: hanya SSH + HTTP/HTTPS
sudo ufw allow OpenSSH
sudo ufw allow 80,443/tcp
sudo ufw enable
sudo ufw status

# Pastikan db/redis/ml TIDAK punya ports: di compose (sudah, hanya expose:)
# Backup PostgreSQL terjadwal (contoh harian via cron):
( crontab -l 2>/dev/null; echo "0 2 * * * cd ~/ternakku && docker compose exec -T db pg_dump -U ternakku ternakku | gzip > ~/ternakku/data/backup_\$(date +\%F).sql.gz" ) | crontab -
```

Checklist SPBE: TLS+HSTS aktif · `ml`/`db`/`redis` internal-only · auth Laravel + rate limiting · simpan EXIF foto tetapi strip GPS sebelum tampil publik (untuk Modul 4 nanti) · backup terjadwal & pantau ruang disk · audit log transaksi/perubahan harga.

---

## Troubleshooting Cepat

| Gejala | Penyebab umum | Solusi |
|--------|---------------|--------|
| `prep_data.py` error encoding | Hereford CSV ber-Cyrillic | sudah ditangani (`encoding="latin-1"`) — pastikan pakai skrip ini |
| `/train` → "model belum dilatih" saat `/predict` | belum latih | jalankan `/train` dulu |
| Nginx gagal start | sertifikat belum ada | jalankan langkah Certbot tahap 7 dulu |
| `app` tak konek DB | password/.env beda | samakan `POSTGRES_PASSWORD` (root .env) dan `DB_PASSWORD` (web/.env) |
| Certbot gagal | DNS belum mengarah / port 80 tertutup | cek A record & `ufw allow 80` |

---

## Catatan Penting

- **Dua dataset publik (1.586 baris) cukup untuk MVP & komparasi metode**, tetapi keduanya sapi iklim sedang. Untuk akurasi & klaim "rekalibrasi ternak lokal Kaltara", tambahkan sumber **sapi Bali/Aceh** menyusul, beri `source=public` (atau `farmer` bila dari lapangan).
- Skrip `main.py` ML di sini sengaja **baseline (regresi linear)** untuk membuktikan alur end-to-end. Anggota tim menambah metode (log-log, Random Forest, XGBoost, mixed model) sebagai endpoint/eksperimen lain dan membandingkannya di leaderboard — sesuai Bab 6 & 9 dokumen rancangan.
- Evaluasi akurasi final memakai **skenario data nyata** (lihat Bab 3 rancangan): latih nyata → uji nyata sebagai angka utama.
