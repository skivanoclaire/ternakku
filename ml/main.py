"""main.py — FastAPI service TernakKu (Modul 1: estimasi bobot).

Dipanggil Laravel via jaringan internal Docker (http://ml:8000). Tidak terekspos publik.
Endpoint mengikuti kontrak API di DESIGN §9.

  GET  /health         -> status & apakah model sudah ada
  POST /prep           -> bersihkan dataset publik -> pengukuran_public.csv
  POST /train          -> latih ulang model di VM (Opsi: VM latih sendiri)
  POST /predict/bobot  -> estimasi bobot + interval p10/p90 + model_ver

Jalankan lokal: uvicorn main:app --reload
"""
import os
import numpy as np
import joblib
from fastapi import FastAPI
from pydantic import BaseModel

import prep_data
import train_modul1

app = FastAPI(title="TernakKu ML — Modul 1")

DATA_RAW = os.getenv("DATA_RAW", "data/raw")
DATA_OUT = os.getenv("DATA_OUT", "data/out")
CSV = os.path.join(DATA_OUT, "pengukuran_public.csv")
MODEL_PATH = os.path.join(DATA_OUT, "model_modul1.joblib")


class Ukuran(BaseModel):
    lingkar_dada_cm: float
    panjang_badan_cm: float | None = None
    tinggi_gumba_cm: float | None = None


def _predict(bundle, row):
    if bundle["kind"] == "loglog":
        return float(np.exp(bundle["a"] + bundle["b"] * np.log(row[0])))
    return float(bundle["model"].predict([row])[0])


@app.get("/health")
def health():
    return {"status": "ok", "model_ada": os.path.exists(MODEL_PATH)}


@app.post("/prep")
def prep():
    """Bersihkan dataset publik (mengisi data/out/pengukuran_public.csv)."""
    import sys
    sys.argv = ["prep_data.py", "--indir", DATA_RAW, "--outdir", DATA_OUT]
    prep_data.main()
    return {"ok": True, "csv": CSV}


@app.post("/train")
def train(best: str | None = None):
    """Latih ulang model di VM. `best` opsional: linear|loglog|rf|xgb."""
    if not os.path.exists(CSV):
        return {"error": f"{CSV} belum ada — panggil /prep dulu"}
    metrics = train_modul1.run(CSV, DATA_OUT, best=best)
    return metrics


@app.post("/predict/bobot")
def predict_bobot(u: Ukuran):
    if not os.path.exists(MODEL_PATH):
        return {"error": "model belum dilatih — panggil /train dulu"}
    bundle = joblib.load(MODEL_PATH)
    # isi fitur kosong dengan 0 (model linear/rf tetap berfungsi; loglog hanya pakai LD)
    row = [u.lingkar_dada_cm,
           u.panjang_badan_cm if u.panjang_badan_cm is not None else 0.0,
           u.tinggi_gumba_cm if u.tinggi_gumba_cm is not None else 0.0]
    kg = _predict(bundle, row)
    sd = bundle.get("resid_std", 0.0)
    # interval ~p10/p90 dengan aproksimasi normal (z=1.2816)
    return {
        "bobot_estimasi_kg": round(kg, 1),
        "p10": round(kg - 1.2816 * sd, 1),
        "p90": round(kg + 1.2816 * sd, 1),
        "model_ver": bundle.get("model_ver", "unknown"),
    }
