# TernakKu — Modul 1: Estimasi Bobot Sapi

Estimasi bobot hidup sapi dari ukuran tubuh (lingkar dada, panjang badan, tinggi gumba)
tanpa timbangan — modul inti platform **TernakKu** (marketplace ternak berbasis data science,
subdomain `ternakku.kaltaraprov.web.id`).

Repo ini berisi kode ML yang **reproducible**: bisa dijalankan di **Google Colab** (untuk
eksperimen & presentasi) maupun di **VM** sebagai service FastAPI (untuk produksi).

## Cara cepat (Google Colab) — untuk presentasi
1. Buka `notebooks/TernakKu_Modul1_Colab.ipynb` di Google Colab.
2. **Runtime > Run all**. Notebook akan clone repo ini, pasang paket, bersihkan data,
   latih model, dan menampilkan metrik + contoh prediksi.

Hasilnya identik tiap dijalankan karena **versi paket dikunci** (`ml/requirements.txt`),
**seed tetap** (`42`), dan **data ikut di repo** (`data/raw/`).

## Cara lokal
```bash
pip install -r ml/requirements.txt
python ml/prep_data.py --indir data/raw --outdir data/out     # bersihkan dataset
python ml/train_modul1.py --csv data/out/pengukuran_public.csv --outdir data/out  # latih + metrik
```
Output: `data/out/pengukuran_public.csv`, `data/out/model_modul1.joblib`, `data/out/metrics.json`.

## Deploy ke VM (produksi)
Stack lengkap (Nginx + Laravel + FastAPI + PostgreSQL + Redis) lewat Docker Compose,
subdomain `ternakku.kaltaraprov.web.id`. Panduan lengkap: **`deploy/DEPLOY_VM.md`**.
VM melatih modelnya sendiri lewat endpoint `/train`. Service ML endpoint
(kontrak DESIGN §9):
Endpoint (kontrak DESIGN §9):
| Method | Path | Fungsi |
|---|---|---|
| GET  | `/health` | status + apakah model sudah ada |
| POST | `/prep` | bersihkan dataset publik |
| POST | `/train` | latih ulang model di VM (`?best=linear\|loglog\|rf\|xgb`) |
| POST | `/predict/bobot` | estimasi bobot + interval p10/p90 + `model_ver` |

Dokumentasi interaktif otomatis di `/docs` (Swagger UI) — berguna untuk demo.

## Isi repo
```
docker-compose.yml      # stack lengkap (nginx, app, ml, db, redis)
docker-compose.modul1.yml  # stack ringan (nginx -> ml) untuk Modul 1 saja
nginx/conf.d/           # reverse proxy + TLS untuk ternakku.kaltaraprov.web.id
ml/                     # service FastAPI (Modul 1)
  ├── main.py           #   /health /prep /train /predict/bobot
  ├── prep_data.py      #   bersihkan dataset publik -> skema seragam
  ├── train_modul1.py   #   latih + bandingkan metode (linear, log-log, RF, XGBoost)
  ├── requirements.txt  #   versi DIKUNCI (reproducible)
  └── Dockerfile
web/                    # Laravel 11 (digenerate di VM; lihat deploy/)
deploy/
  ├── DEPLOY_VM.md       # runbook deploy lengkap
  └── laravel-overlay/   # file integrasi Laravel <-> ML (disalin ke web/)
data/raw/               # dataset publik (Hereford, Horqin) — di-commit
notebooks/              # notebook Colab (presentasi & reproducibility)
docs/                   # runbook VM versi awal
```

## Sumber data (wajib disitasi untuk artikel)
| Dataset | Baris | Lisensi | Sitasi |
|---|---|---|---|
| Hereford cows | 1.514 | Open access (GitHub) | Ruchay A. et al., IOP Conf. Ser. EES 624:012056 (2021) |
| Cattle side & back view (Horqin) | 72 | CC BY 4.0 | Bai, Lili. Mendeley Data, DOI 10.17632/h2s22wr5py.3 (2025) |

> Catatan: kedua dataset adalah sapi iklim sedang. Untuk relevansi & klaim
> rekalibrasi ternak lokal Kaltara, tambahkan data sapi Bali/Aceh menyusul.
