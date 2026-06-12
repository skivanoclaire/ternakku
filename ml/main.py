"""main.py — FastAPI service TernakKu (Modul 1: estimasi bobot + researcher workbench).

Dipanggil Laravel via jaringan internal Docker (http://ml:8000). Tidak terekspos publik.

  GET  /health             -> status & apakah model aktif ada
  POST /prep               -> bersihkan dataset publik -> pengukuran_public.csv
  POST /train              -> latih cepat (pilih terbaik) -> model aktif
  POST /eda                -> statistik & korelasi dataset (untuk halaman EDA)
  POST /experiment/train   -> latih SATU metode+fitur -> metrik, diagnostik, artefak
  POST /experiment/promote -> jadikan artefak eksperimen sbg model aktif
  POST /predict/bobot      -> estimasi bobot + interval p10/p90 + model_ver (model aktif)

Jalankan lokal: uvicorn main:app --reload
"""
import os
import shutil
import numpy as np
import joblib
from fastapi import FastAPI
from pydantic import BaseModel

import prep_data
import train_modul1
import experiment

app = FastAPI(title="TernakKu ML — Modul 1 + Researcher")

DATA_RAW = os.getenv("DATA_RAW", "data/raw")
DATA_OUT = os.getenv("DATA_OUT", "data/out")
CSV = os.path.join(DATA_OUT, "pengukuran_public.csv")
TRAIN_CSV = os.path.join(DATA_OUT, "pengukuran_train.csv")   # diekspor Laravel dari DB
MODEL_PATH = os.path.join(DATA_OUT, "model_modul1.joblib")
FEAT_ORDER = ["lingkar_dada_cm", "panjang_badan_cm", "tinggi_gumba_cm"]


def _active_csv():
    """Pakai data latih dari DB (diekspor Laravel) bila ada; jika tidak, dataset publik."""
    return TRAIN_CSV if os.path.exists(TRAIN_CSV) else CSV


class Ukuran(BaseModel):
    lingkar_dada_cm: float
    panjang_badan_cm: float | None = None
    tinggi_gumba_cm: float | None = None


class TrainReq(BaseModel):
    method: str = "linear"
    features: list[str] = FEAT_ORDER   # kunci FEATURE_CATALOG (mentah + rekayasa)
    eval_mode: str = "acak"      # acak (5-fold) | lintas (grouped per dataset)
    scenario: str = "B"          # B nyata->nyata | A sintetis->nyata | C gabungan->nyata
    log_target: bool = False     # modelkan ln(bobot) (galat multiplikatif)
    exp_id: int = 0


class SynthReq(BaseModel):
    n: int = 800


class PromoteReq(BaseModel):
    model_ver: str


def _predict_value(bundle, u: Ukuran):
    """Prediksi sadar-fitur untuk semua jenis model (lihat experiment.predict_one)."""
    return experiment.predict_one(bundle, u.lingkar_dada_cm, u.panjang_badan_cm, u.tinggi_gumba_cm)


@app.get("/health")
def health():
    return {"status": "ok", "model_ada": os.path.exists(MODEL_PATH)}


@app.post("/prep")
def prep():
    import sys
    sys.argv = ["prep_data.py", "--indir", DATA_RAW, "--outdir", DATA_OUT]
    prep_data.main()
    return {"ok": True, "csv": CSV}


@app.post("/train")
def train(best: str | None = None):
    if not os.path.exists(CSV):
        return {"error": f"{CSV} belum ada — panggil /prep dulu"}
    return train_modul1.run(CSV, DATA_OUT, best=best)


@app.post("/eda")
def eda():
    csv = _active_csv()
    if not os.path.exists(csv):
        return {"error": "data latih belum ada — impor data atau panggil /prep"}
    return experiment.eda(csv)


@app.post("/experiment/train")
def experiment_train(req: TrainReq):
    csv = _active_csv()
    if not os.path.exists(csv):
        return {"error": "data latih belum ada — impor data atau panggil /prep"}
    if req.method not in experiment.METHODS:
        return {"error": f"metode tak dikenal: {req.method}"}
    return experiment.run(csv, req.method, req.features, req.eval_mode, DATA_OUT,
                          req.exp_id, req.scenario, req.log_target)


@app.post("/synth/generate")
def synth_generate(req: SynthReq):
    """Bangkitkan data sintetis (dikembalikan ke Laravel untuk disimpan ke DB)."""
    return {"rows": experiment.generate_synthetic(req.n)}


@app.post("/experiment/promote")
def experiment_promote(req: PromoteReq):
    art = os.path.join(DATA_OUT, "experiments", f"{req.model_ver}.joblib")
    if not os.path.exists(art):
        return {"error": f"artefak {req.model_ver} tidak ditemukan"}
    shutil.copyfile(art, MODEL_PATH)     # jadikan model aktif yang melayani /predict
    return {"ok": True, "model_aktif": req.model_ver}


@app.post("/predict/bobot")
def predict_bobot(u: Ukuran):
    if not os.path.exists(MODEL_PATH):
        return {"error": "model belum dilatih — promosikan/latih model dulu"}
    bundle = joblib.load(MODEL_PATH)
    kg = _predict_value(bundle, u)
    sd = bundle.get("resid_std", 0.0)
    return {
        "bobot_estimasi_kg": round(kg, 1),
        "p10": round(kg - 1.2816 * sd, 1),
        "p90": round(kg + 1.2816 * sd, 1),
        "model_ver": bundle.get("model_ver", "unknown"),
    }
