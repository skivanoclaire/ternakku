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


def _eval_block(y_eval, p_eval):
    metrics = _metrics(y_eval, p_eval)
    resid = np.asarray(y_eval, float) - np.asarray(p_eval, float)
    sd = float(np.std(resid)); z = 1.2816
    metrics["coverage"] = round(float(np.mean(np.abs(resid) <= z * sd) * 100), 1)
    metrics["interval_kg"] = round(2 * z * sd, 1)
    idx = np.arange(len(y_eval))
    if len(idx) > 400:
        idx = np.sort(np.random.RandomState(SEED).choice(idx, 400, replace=False))
    diagnostics = {
        "actual":   [round(float(np.asarray(y_eval)[i]), 1) for i in idx],
        "pred":     [round(float(np.asarray(p_eval)[i]), 1) for i in idx],
        "residual": [round(float(np.asarray(y_eval)[i] - np.asarray(p_eval)[i]), 1) for i in idx],
    }
    return metrics, diagnostics


def run(csv, method, feats, eval_mode, outdir, exp_id, scenario="B"):
    """Skenario sumber data:
      B = nyata->nyata (CV, angka utama); A = sintetis->nyata; C = gabungan->nyata.
    """
    feats = [f for f in feats if f in ALL_FEATS] or ["lingkar_dada_cm"]
    df = pd.read_csv(csv).dropna(subset=feats + [TARGET])
    if "source" not in df:
        df["source"] = "public"
    if GROUP not in df:
        df[GROUP] = "real"
    real = df[df["source"] != "synthetic"]
    synth = df[df["source"] == "synthetic"]

    if scenario == "B" or synth.empty:
        scenario = "B"
        sub = real.dropna(subset=[GROUP])
        X, y, g = sub[feats].values, sub[TARGET].values.astype(float), sub[GROUP].values
        oof = np.full(len(y), np.nan)
        for tr, te in _splits(X, y, g, eval_mode):
            oof[te] = _fit_predict(method, X[tr], y[tr], X[te])
        m = ~np.isnan(oof)
        y_eval, p_eval = y[m], oof[m]
        finalX, finalY = X, y
    else:
        real_train, real_test = _real_holdout(real)
        train_df = synth if scenario == "A" else pd.concat([synth, real_train], ignore_index=True)
        Xtr, ytr = train_df[feats].values, train_df[TARGET].values.astype(float)
        y_eval = real_test[TARGET].values.astype(float)
        p_eval = _fit_predict(method, Xtr, ytr, real_test[feats].values)
        finalX, finalY = Xtr, ytr

    metrics, diagnostics = _eval_block(y_eval, p_eval)

    os.makedirs(os.path.join(outdir, "experiments"), exist_ok=True)
    model_ver = f"exp{exp_id}-{method}"
    bundle = _final_bundle(method, finalX, finalY, feats, model_ver)
    art = os.path.join(outdir, "experiments", f"{model_ver}.joblib")
    joblib.dump(bundle, art)

    return {
        "model_ver": model_ver, "method": method,
        "method_label": METHODS.get(method, method), "features": feats,
        "eval_mode": eval_mode, "scenario": scenario, "n_rows": int(len(y_eval)),
        "metrics": metrics, "importance": _importance(method, finalX, finalY, feats),
        "diagnostics": diagnostics, "artifact": art,
    }


def generate_synthetic(n=800, seed=SEED):
    """Bangkitkan data sintetis: bobot ~ allometrik(LD) * efek ras * noise (di-seed).
    Parameter ditanam sehingga model bisa divalidasi (menemukan kembali pola)."""
    rng = np.random.RandomState(seed)
    ras_eff = {"Bali": 0.95, "Madura": 0.90, "Limousin": 1.10, "Brahman": 1.05}
    ras_list = list(ras_eff)
    rows = []
    for i in range(int(n)):
        ras = ras_list[rng.randint(len(ras_list))]
        ld = rng.uniform(150, 230)
        pb = ld * rng.uniform(0.82, 0.92)
        tg = ld * rng.uniform(0.62, 0.70)
        bobot = 0.00052 * (ld ** 2.6) * ras_eff[ras] * rng.normal(1.0, 0.06)
        rows.append({
            "src_dataset": "synthetic", "src_animal_id": f"syn{i}",
            "lingkar_dada_cm": round(ld, 1), "panjang_badan_cm": round(pb, 1),
            "tinggi_gumba_cm": round(tg, 1), "bobot_timbang_kg": round(bobot, 1),
            "source": "synthetic",
        })
    return rows


def _real_holdout(real):
    """Pisahkan data nyata jadi train/test (grouped per dataset bila >1, jika tidak 30% acak)."""
    groups = real[GROUP].dropna().unique() if GROUP in real else []
    if len(groups) >= 2:
        test_grp = sorted(groups)[-1]
        return real[real[GROUP] != test_grp], real[real[GROUP] == test_grp]
    shuf = real.sample(frac=1, random_state=SEED)
    ntest = max(1, int(0.3 * len(shuf)))
    return shuf.iloc[ntest:], shuf.iloc[:ntest]


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
