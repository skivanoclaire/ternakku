"""experiment.py — mesin eksperimen Modul 1 untuk researcher workbench.

Satu eksperimen = satu (metode + subset fitur + mode evaluasi) dilatih pada
data nyata (pengukuran_public.csv), dievaluasi out-of-fold, lalu:
  - metrik (MAPE/MAE/RMSE/R2/coverage/bias)
  - diagnostik (titik prediksi vs aktual, residual) untuk grafik
  - importance/koefisien
  - artefak model tersimpan (bisa dipromosikan jadi model aktif)

Dipanggil FastAPI (main.py) atas perintah Laravel.
"""
import os
import numpy as np
import pandas as pd
import joblib
from sklearn.linear_model import LinearRegression
from sklearn.ensemble import RandomForestRegressor
from sklearn.model_selection import KFold, GroupKFold

SEED = 42
ALL_FEATS = ["lingkar_dada_cm", "panjang_badan_cm", "tinggi_gumba_cm"]
TARGET = "bobot_timbang_kg"
GROUP = "src_dataset"

METHODS = {
    "schoorl": "Baseline klasik Schoorl (LD)",
    "loglog":  "Power-law / log-log (LD)",
    "linear":  "Regresi linear berganda",
    "rf":      "Random Forest",
    "xgb":     "XGBoost",
}


def _metrics(y, p):
    y, p = np.asarray(y, float), np.asarray(p, float)
    err = y - p
    rmse = float(np.sqrt(np.mean(err ** 2)))
    mae = float(np.mean(np.abs(err)))
    mape = float(np.mean(np.abs(err) / y) * 100)
    bias = float(np.mean(p - y))
    ss_res = float(np.sum(err ** 2))
    ss_tot = float(np.sum((y - y.mean()) ** 2))
    r2 = 1 - ss_res / ss_tot if ss_tot else float("nan")
    return {"mape": round(mape, 2), "mae": round(mae, 2), "rmse": round(rmse, 2),
            "r2": round(r2, 4), "bias": round(bias, 2)}


def _schoorl(ld):
    # rumus empiris klasik: BB(kg) = (lingkar dada cm + 22)^2 / 100
    return ((np.asarray(ld, float) + 22.0) ** 2) / 100.0


def _fit_predict(method, Xtr, ytr, Xte):
    if method == "linear":
        return LinearRegression().fit(Xtr, ytr).predict(Xte)
    if method == "loglog":
        ld_tr, ld_te = Xtr[:, 0], Xte[:, 0]
        b, a = np.polyfit(np.log(ld_tr), np.log(ytr), 1)
        return np.exp(a + b * np.log(ld_te))
    if method == "rf":
        return RandomForestRegressor(n_estimators=300, random_state=SEED, n_jobs=-1).fit(Xtr, ytr).predict(Xte)
    if method == "xgb":
        from xgboost import XGBRegressor
        return XGBRegressor(n_estimators=300, max_depth=4, learning_rate=0.05,
                            random_state=SEED, n_jobs=-1).fit(Xtr, ytr).predict(Xte)
    if method == "schoorl":
        return _schoorl(Xte[:, 0])
    raise ValueError(f"metode tak dikenal: {method}")


def _splits(X, y, g, eval_mode):
    if eval_mode == "lintas" and len(np.unique(g)) >= 2:
        gkf = GroupKFold(n_splits=len(np.unique(g)))
        return list(gkf.split(X, y, g))
    return list(KFold(n_splits=5, shuffle=True, random_state=SEED).split(X))


def _final_bundle(method, X, y, feats, model_ver):
    """Latih final di seluruh data + simpan resid_std untuk interval p10/p90."""
    if method == "schoorl":
        pred = _schoorl(X[:, 0])
        bundle = {"kind": "schoorl", "feats": feats}
    elif method == "loglog":
        b, a = np.polyfit(np.log(X[:, 0]), np.log(y), 1)
        pred = np.exp(a + b * np.log(X[:, 0]))
        bundle = {"kind": "loglog", "a": float(a), "b": float(b), "feats": feats}
    else:
        m = (LinearRegression() if method == "linear"
             else RandomForestRegressor(n_estimators=300, random_state=SEED, n_jobs=-1) if method == "rf"
             else __import__("xgboost").XGBRegressor(n_estimators=300, max_depth=4,
                  learning_rate=0.05, random_state=SEED, n_jobs=-1))
        m.fit(X, y)
        pred = m.predict(X)
        bundle = {"kind": "sklearn", "model": m, "feats": feats}
    bundle["resid_std"] = float(np.std(y - pred))
    bundle["model_ver"] = model_ver
    return bundle


def _importance(method, X, y, feats):
    if method in ("schoorl",):
        return {"catatan": "rumus tetap, tanpa parameter terlatih"}
    if method == "loglog":
        b, a = np.polyfit(np.log(X[:, 0]), np.log(y), 1)
        return {"eksponen_LD (b)": round(float(b), 3), "konstanta (a)": round(float(a), 3)}
    if method == "linear":
        m = LinearRegression().fit(X, y)
        return {f: round(float(c), 3) for f, c in zip(feats, m.coef_)}
    if method in ("rf", "xgb"):
        if method == "rf":
            m = RandomForestRegressor(n_estimators=300, random_state=SEED, n_jobs=-1).fit(X, y)
        else:
            m = __import__("xgboost").XGBRegressor(n_estimators=300, max_depth=4,
                learning_rate=0.05, random_state=SEED, n_jobs=-1).fit(X, y)
        return {f: round(float(v), 4) for f, v in zip(feats, m.feature_importances_)}
    return {}


def run(csv, method, feats, eval_mode, outdir, exp_id):
    feats = [f for f in feats if f in ALL_FEATS] or ["lingkar_dada_cm"]
    df = pd.read_csv(csv).dropna(subset=feats + [TARGET, GROUP])
    X, y, g = df[feats].values, df[TARGET].values.astype(float), df[GROUP].values

    # prediksi out-of-fold untuk metrik & diagnostik yang jujur
    oof = np.full(len(y), np.nan)
    for tr, te in _splits(X, y, g, eval_mode):
        oof[te] = _fit_predict(method, X[tr], y[tr], X[te])
    mask = ~np.isnan(oof)
    metrics = _metrics(y[mask], oof[mask])

    # coverage interval p10-p90 (aproksimasi normal dari residual)
    resid = y[mask] - oof[mask]
    sd = float(np.std(resid))
    z = 1.2816
    coverage = float(np.mean(np.abs(resid) <= z * sd) * 100)
    metrics["coverage"] = round(coverage, 1)
    metrics["interval_kg"] = round(2 * z * sd, 1)

    # simpan artefak final (untuk promosi/serving)
    os.makedirs(os.path.join(outdir, "experiments"), exist_ok=True)
    model_ver = f"exp{exp_id}-{method}"
    bundle = _final_bundle(method, X, y, feats, model_ver)
    art = os.path.join(outdir, "experiments", f"{model_ver}.joblib")
    joblib.dump(bundle, art)

    # diagnostik: sampel titik (maks 400) supaya payload ringan
    idx = np.where(mask)[0]
    if len(idx) > 400:
        rng = np.random.RandomState(SEED)
        idx = np.sort(rng.choice(idx, 400, replace=False))
    diagnostics = {
        "actual": [round(float(v), 1) for v in y[idx]],
        "pred":   [round(float(v), 1) for v in oof[idx]],
        "residual": [round(float(y[i] - oof[i]), 1) for i in idx],
    }

    return {
        "model_ver": model_ver,
        "method": method,
        "method_label": METHODS.get(method, method),
        "features": feats,
        "eval_mode": eval_mode,
        "n_rows": int(mask.sum()),
        "metrics": metrics,
        "importance": _importance(method, X, y, feats),
        "diagnostics": diagnostics,
        "artifact": art,
    }


def eda(csv):
    df = pd.read_csv(csv)
    cols = [c for c in ALL_FEATS + [TARGET] if c in df.columns]
    stats = {}
    for c in cols:
        s = pd.to_numeric(df[c], errors="coerce")
        stats[c] = {"n": int(s.notna().sum()), "kosong": int(s.isna().sum()),
                    "min": round(float(s.min()), 1), "max": round(float(s.max()), 1),
                    "mean": round(float(s.mean()), 1), "std": round(float(s.std()), 1)}
    # korelasi LD->bobot keseluruhan & per dataset
    korelasi = {}
    if TARGET in df and "lingkar_dada_cm" in df:
        korelasi["semua"] = round(float(df["lingkar_dada_cm"].corr(df[TARGET])), 3)
        if GROUP in df:
            for ds, grp in df.groupby(GROUP):
                if len(grp) > 2:
                    korelasi[str(ds)] = round(float(grp["lingkar_dada_cm"].corr(grp[TARGET])), 3)
    # sampel scatter LD vs bobot
    sub = df.dropna(subset=["lingkar_dada_cm", TARGET])
    if len(sub) > 500:
        sub = sub.sample(500, random_state=SEED)
    scatter = [{"ld": round(float(a), 1), "bobot": round(float(b), 1), "ds": str(c)}
               for a, b, c in zip(sub["lingkar_dada_cm"], sub[TARGET],
                                  sub[GROUP] if GROUP in sub else ["?"] * len(sub))]
    komposisi = (df[GROUP].value_counts().to_dict() if GROUP in df else {})
    return {"n_baris": int(len(df)), "stats": stats, "korelasi": korelasi,
            "komposisi": {str(k): int(v) for k, v in komposisi.items()}, "scatter": scatter}
