#!/usr/bin/env python3
"""train_modul1.py — Latih & bandingkan model estimasi bobot sapi (Modul 1).

Membaca `pengukuran_public.csv` (hasil prep_data.py), melatih beberapa metode,
mengevaluasi dengan DUA skenario, lalu menyimpan model terbaik + metrik.

Metode yang dibandingkan (sesuai DESIGN §7 Modul 1):
  - Regresi linear berganda (LD + PB + tinggi gumba)   -> baseline konvensional
  - Power-law / log-log (ln BB = a + b ln LD)           -> jujur secara biologis
  - Random Forest                                       -> ML non-linear
  - XGBoost                                             -> ML boosting (bila terpasang)

Skenario evaluasi:
  - "acak"   : 5-fold acak (performa pada distribusi yang sama)
  - "lintas" : train 1 dataset, uji dataset lain (uji generalisasi antar-ras)

Semua RNG di-seed (default 42) -> angka identik tiap dijalankan = reproducible.

Pakai:
  python3 train_modul1.py --csv data/out/pengukuran_public.csv --outdir data/out
"""
import argparse, json, os
import numpy as np
import pandas as pd
import joblib
from sklearn.linear_model import LinearRegression
from sklearn.ensemble import RandomForestRegressor
from sklearn.model_selection import KFold, GroupKFold

SEED = 42
FEATS = ["lingkar_dada_cm", "panjang_badan_cm", "tinggi_gumba_cm"]
TARGET = "bobot_timbang_kg"
GROUP = "src_dataset"


def _metrics(y, p):
    y, p = np.asarray(y, float), np.asarray(p, float)
    err = y - p
    rmse = float(np.sqrt(np.mean(err ** 2)))
    mae = float(np.mean(np.abs(err)))
    mape = float(np.mean(np.abs(err) / y) * 100)
    ss_res = float(np.sum(err ** 2))
    ss_tot = float(np.sum((y - y.mean()) ** 2))
    r2 = 1 - ss_res / ss_tot if ss_tot else float("nan")
    return {"R2": round(r2, 4), "RMSE": round(rmse, 2),
            "MAE": round(mae, 2), "MAPE": round(mape, 2)}


def _fit_predict(name, Xtr, ytr, Xte):
    """Latih satu metode di train, kembalikan prediksi di test."""
    if name == "linear":
        m = LinearRegression().fit(Xtr, ytr)
        return m.predict(Xte)
    if name == "loglog":
        # power law pakai lingkar dada (kolom 0) saja: ln BB = a + b ln LD
        ld_tr, ld_te = Xtr[:, 0], Xte[:, 0]
        b, a = np.polyfit(np.log(ld_tr), np.log(ytr), 1)
        return np.exp(a + b * np.log(ld_te))
    if name == "rf":
        m = RandomForestRegressor(n_estimators=300, random_state=SEED, n_jobs=-1).fit(Xtr, ytr)
        return m.predict(Xte)
    if name == "xgb":
        from xgboost import XGBRegressor
        m = XGBRegressor(n_estimators=300, max_depth=4, learning_rate=0.05,
                         random_state=SEED, n_jobs=-1).fit(Xtr, ytr)
        return m.predict(Xte)
    raise ValueError(name)


def _methods():
    names = ["linear", "loglog", "rf"]
    try:
        import xgboost  # noqa: F401
        names.append("xgb")
    except ImportError:
        print("  (xgboost tidak terpasang — dilewati)")
    return names


def evaluate(X, y, groups):
    """Kembalikan metrik rata-rata tiap metode untuk dua skenario."""
    results = {}
    methods = _methods()

    # Skenario 1: 5-fold acak
    kf = KFold(n_splits=5, shuffle=True, random_state=SEED)
    acak = {m: [] for m in methods}
    for tr, te in kf.split(X):
        for m in methods:
            acak[m].append(_metrics(y[te], _fit_predict(m, X[tr], y[tr], X[te])))

    # Skenario 2: lintas-dataset (train 1 grup, uji grup lain)
    n_groups = len(np.unique(groups))
    lintas = {m: [] for m in methods}
    if n_groups >= 2:
        gkf = GroupKFold(n_splits=n_groups)
        for tr, te in gkf.split(X, y, groups):
            for m in methods:
                lintas[m].append(_metrics(y[te], _fit_predict(m, X[tr], y[tr], X[te])))

    def avg(folds):
        keys = folds[0].keys()
        return {k: round(float(np.mean([f[k] for f in folds])), 3) for k in keys}

    for m in methods:
        results[m] = {"acak": avg(acak[m])}
        if lintas[m]:
            results[m]["lintas"] = avg(lintas[m])
    return results, methods


def train_final(X, y, best="linear"):
    """Latih model final di SELURUH data + simpan std residual untuk interval p10/p90."""
    if best == "loglog":
        ld = X[:, 0]
        b, a = np.polyfit(np.log(ld), np.log(y), 1)
        pred = np.exp(a + b * np.log(ld))
        bundle = {"kind": "loglog", "a": float(a), "b": float(b), "feats": FEATS}
    else:
        m = (LinearRegression() if best == "linear"
             else RandomForestRegressor(n_estimators=300, random_state=SEED, n_jobs=-1))
        m.fit(X, y)
        pred = m.predict(X)
        bundle = {"kind": "sklearn", "model": m, "feats": FEATS}
    bundle["resid_std"] = float(np.std(y - pred))
    return bundle


def run(csv, outdir, best=None, seed=SEED):
    os.makedirs(outdir, exist_ok=True)
    df = pd.read_csv(csv).dropna(subset=FEATS + [TARGET, GROUP])
    X, y, g = df[FEATS].values, df[TARGET].values, df[GROUP].values
    print(f"Data: {len(df)} baris, grup={sorted(np.unique(g))}, fitur={FEATS}")

    results, methods = evaluate(X, y, g)
    print("\n=== Perbandingan metode (MAPE% — makin kecil makin baik) ===")
    for m in methods:
        acak = results[m]["acak"]
        line = f"  {m:8s} | acak: MAPE={acak['MAPE']:5.2f} R2={acak['R2']:.3f}"
        if "lintas" in results[m]:
            lt = results[m]["lintas"]
            line += f" | lintas-dataset: MAPE={lt['MAPE']:5.2f} R2={lt['R2']:.3f}"
        print(line)

    # Pilih model final: default = yang MAPE-acak terkecil, atau --best paksa
    if best is None:
        best = min(methods, key=lambda m: results[m]["acak"]["MAPE"])
    print(f"\nModel final dipilih: {best}")
    bundle = train_final(X, y, best)
    bundle["model_ver"] = f"modul1-{best}-seed{seed}"

    model_path = os.path.join(outdir, "model_modul1.joblib")
    joblib.dump(bundle, model_path)

    metrics = {"seed": seed, "n_baris": len(df), "fitur": FEATS,
               "model_final": best, "model_ver": bundle["model_ver"],
               "perbandingan": results}
    metrics_path = os.path.join(outdir, "metrics.json")
    with open(metrics_path, "w") as f:
        json.dump(metrics, f, indent=2, ensure_ascii=False)
    print(f"Tersimpan: {model_path}\n           {metrics_path}")
    return metrics


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("--csv", default="data/out/pengukuran_public.csv")
    ap.add_argument("--outdir", default="data/out")
    ap.add_argument("--best", default=None,
                    help="paksa model final: linear|loglog|rf|xgb (default: MAPE terbaik)")
    a = ap.parse_args()
    run(a.csv, a.outdir, a.best)


if __name__ == "__main__":
    main()
