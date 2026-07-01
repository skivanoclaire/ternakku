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
    "hybrid":  "Hibrida allometrik + ML (koreksi residual)",
}

# Katalog fitur: mentah + rekayasa allometrik. Nilai dihitung dari kolom mentah.
#   ld2     = LD^2            (bobot ~ pangkat dimensi linear)
#   ld2_pb  = LD^2 * PB       (proksi VOLUME tubuh — inti rumus Schoorl/Crevat)
#   pb_ld   = PB / LD, tg_ld = TG / LD  (rasio bentuk: gemuk vs kurus)
FEATURE_CATALOG = {
    "lingkar_dada_cm":  lambda d: d["lingkar_dada_cm"],
    "panjang_badan_cm": lambda d: d["panjang_badan_cm"],
    "tinggi_gumba_cm":  lambda d: d["tinggi_gumba_cm"],
    "ld2":    lambda d: d["lingkar_dada_cm"] ** 2,
    "ld2_pb": lambda d: (d["lingkar_dada_cm"] ** 2) * d["panjang_badan_cm"],
    "pb_ld":  lambda d: d["panjang_badan_cm"] / d["lingkar_dada_cm"],
    "tg_ld":  lambda d: d["tinggi_gumba_cm"] / d["lingkar_dada_cm"],
}
# kolom mentah yang dibutuhkan tiap fitur (untuk dropna)
_FEAT_NEEDS = {
    "lingkar_dada_cm": ["lingkar_dada_cm"], "panjang_badan_cm": ["panjang_badan_cm"],
    "tinggi_gumba_cm": ["tinggi_gumba_cm"], "ld2": ["lingkar_dada_cm"],
    "ld2_pb": ["lingkar_dada_cm", "panjang_badan_cm"],
    "pb_ld": ["lingkar_dada_cm", "panjang_badan_cm"],
    "tg_ld": ["lingkar_dada_cm", "tinggi_gumba_cm"],
}


def _required_raw(feat_keys):
    need = {"lingkar_dada_cm"}   # selalu (untuk basis allometrik/Schoorl)
    for k in feat_keys:
        need.update(_FEAT_NEEDS.get(k, []))
    return list(need)


def _build_X(df, feat_keys):
    return np.column_stack([np.asarray(FEATURE_CATALOG[k](df), float) for k in feat_keys])


def _row_from_raw(feat_keys, ld, pb, tg):
    d = {"lingkar_dada_cm": float(ld),
         "panjang_badan_cm": float(pb) if pb else float(ld) * 0.87,   # default proporsional bila kosong
         "tinggi_gumba_cm": float(tg) if tg else float(ld) * 0.66}
    return [float(FEATURE_CATALOG[k](d)) for k in feat_keys]


def _rf():
    return RandomForestRegressor(n_estimators=300, random_state=SEED, n_jobs=-1)


def _xgb():
    from xgboost import XGBRegressor
    return XGBRegressor(n_estimators=300, max_depth=4, learning_rate=0.05, random_state=SEED, n_jobs=-1)


def _fin(v, d=2):
    """Bulatkan; kembalikan None bila None/tak-hingga/NaN (agar aman di-JSON-kan)."""
    if v is None:
        return None
    v = float(v)
    return round(v, d) if np.isfinite(v) else None


def _metrics(y, p):
    y, p = np.asarray(y, float), np.asarray(p, float)
    err = y - p
    rmse = float(np.sqrt(np.mean(err ** 2)))
    mae = float(np.mean(np.abs(err)))
    mape = float(np.mean(np.abs(err) / y) * 100)
    bias = float(np.mean(p - y))
    ss_res = float(np.sum(err ** 2))
    ss_tot = float(np.sum((y - y.mean()) ** 2))
    r2 = (1 - ss_res / ss_tot) if ss_tot else None   # R² tak terdefinisi bila cuma 1 titik / bobot sama
    return {"mape": _fin(mape), "mae": _fin(mae), "rmse": _fin(rmse),
            "r2": _fin(r2, 4), "bias": _fin(bias)}


def _schoorl(ld):
    # rumus empiris klasik: BB(kg) = (lingkar dada cm + 22)^2 / 100
    return ((np.asarray(ld, float) + 22.0) ** 2) / 100.0


def _base_model(method):
    return {"linear": LinearRegression, "rf": _rf, "xgb": _xgb}[method]()


def _fit_predict(method, Xtr, ytr, Xte, ld_tr, ld_te, log_target=False):
    """ld_tr/ld_te = array lingkar dada (untuk basis allometrik/Schoorl/loglog)."""
    if method == "schoorl":
        return _schoorl(ld_te)
    if method == "loglog":
        b, a = np.polyfit(np.log(ld_tr), np.log(ytr), 1)
        return np.exp(a + b * np.log(ld_te))
    if method == "hybrid":
        # basis allometrik (a*LD^b) menangani biologi & ekstrapolasi; ML mengoreksi residual
        b, a = np.polyfit(np.log(ld_tr), np.log(ytr), 1)
        base_tr = np.exp(a + b * np.log(ld_tr))
        base_te = np.exp(a + b * np.log(ld_te))
        ml = _rf().fit(Xtr, ytr - base_tr)
        return base_te + ml.predict(Xte)
    # metode ML/linear biasa, dengan opsi target log (galat multiplikatif)
    m = _base_model(method)
    if log_target:
        m.fit(Xtr, np.log(ytr))
        return np.exp(m.predict(Xte))
    return m.fit(Xtr, ytr).predict(Xte)


def _splits(X, y, g, eval_mode):
    if eval_mode == "lintas" and len(np.unique(g)) >= 2:
        gkf = GroupKFold(n_splits=len(np.unique(g)))
        return list(gkf.split(X, y, g))
    return list(KFold(n_splits=5, shuffle=True, random_state=SEED).split(X))


def _final_bundle(method, X, y, ld, feat_keys, log_target, model_ver):
    """Latih final di seluruh data + simpan info untuk serving & interval p10/p90."""
    bundle = {"method": method, "feat_keys": feat_keys, "log_target": bool(log_target),
              "model_ver": model_ver}
    if method == "schoorl":
        pred = _schoorl(ld)
    elif method == "loglog":
        b, a = np.polyfit(np.log(ld), np.log(y), 1)
        bundle.update({"a": float(a), "b": float(b)})
        pred = np.exp(a + b * np.log(ld))
    elif method == "hybrid":
        b, a = np.polyfit(np.log(ld), np.log(y), 1)
        base = np.exp(a + b * np.log(ld))
        ml = _rf().fit(X, y - base)
        bundle.update({"a": float(a), "b": float(b), "model": ml})
        pred = base + ml.predict(X)
    else:
        m = _base_model(method)
        if log_target:
            m.fit(X, np.log(y)); pred = np.exp(m.predict(X))
        else:
            m.fit(X, y); pred = m.predict(X)
        bundle["model"] = m
    bundle["resid_std"] = float(np.std(y - pred))
    return bundle


def predict_one(bundle, ld, pb, tg):
    """Prediksi satu ekor dari LD/PB/TG (dipakai /predict/bobot)."""
    method = bundle.get("method") or bundle.get("kind", "linear")
    ld = float(ld)
    if method == "schoorl":
        return float(_schoorl(ld))
    if method == "loglog":
        return float(np.exp(bundle["a"] + bundle["b"] * np.log(ld)))
    keys = bundle.get("feat_keys") or bundle.get("feats") or ALL_FEATS
    row = _row_from_raw(keys, ld, pb, tg)
    if method == "hybrid":
        base = float(np.exp(bundle["a"] + bundle["b"] * np.log(ld)))
        return base + float(bundle["model"].predict([row])[0])
    p = float(bundle["model"].predict([row])[0])
    return float(np.exp(p)) if bundle.get("log_target") else p


def _importance(method, X, y, ld, feat_keys):
    if method == "schoorl":
        return {"catatan": "rumus tetap, tanpa parameter terlatih"}
    if method == "loglog":
        b, a = np.polyfit(np.log(ld), np.log(y), 1)
        return {"eksponen_LD (b)": round(float(b), 3), "konstanta (a)": round(float(a), 3)}
    if method == "linear":
        m = LinearRegression().fit(X, y)
        return {f: round(float(c), 4) for f, c in zip(feat_keys, m.coef_)}
    if method in ("rf", "xgb", "hybrid"):
        base = 0.0
        if method == "hybrid":
            b, a = np.polyfit(np.log(ld), np.log(y), 1)
            base = np.exp(a + b * np.log(ld))
        target = y - base
        m = (_xgb() if method == "xgb" else _rf()).fit(X, target)
        return {f: round(float(v), 4) for f, v in zip(feat_keys, m.feature_importances_)}
    return {}


def _eval_block(y_eval, p_eval):
    metrics = _metrics(y_eval, p_eval)
    resid = np.asarray(y_eval, float) - np.asarray(p_eval, float)
    sd = float(np.std(resid)); z = 1.2816
    metrics["coverage"] = _fin(float(np.mean(np.abs(resid) <= z * sd) * 100), 1)
    metrics["interval_kg"] = _fin(2 * z * sd, 1)
    ye, pe = np.asarray(y_eval, float), np.asarray(p_eval, float)
    idx = np.arange(len(ye))
    if len(idx) > 400:
        idx = np.sort(np.random.RandomState(SEED).choice(idx, 400, replace=False))
    diagnostics = {
        "actual":   [_fin(ye[i], 1) for i in idx],
        "pred":     [_fin(pe[i], 1) for i in idx],
        "residual": [_fin(ye[i] - pe[i], 1) for i in idx],
    }
    return metrics, diagnostics


def run(csv, method, feats, eval_mode, outdir, exp_id, scenario="B", log_target=False):
    """Latih+evaluasi satu eksperimen.
    feats = daftar kunci FEATURE_CATALOG (mentah + rekayasa). log_target = modelkan ln(bobot).
    Skenario: B nyata->nyata (CV); A sintetis->nyata; C gabungan->nyata.
    """
    feats = [f for f in feats if f in FEATURE_CATALOG] or ["lingkar_dada_cm"]
    if method in ("schoorl", "loglog"):
        log_target = False   # sudah menangani skala sendiri
    df = pd.read_csv(csv).dropna(subset=_required_raw(feats) + [TARGET])
    if "source" not in df:
        df["source"] = "public"
    if GROUP not in df:
        df[GROUP] = "real"
    real = df[df["source"] != "synthetic"]
    synth = df[df["source"] == "synthetic"]

    if scenario == "B" or synth.empty:
        scenario = "B"
        sub = real.dropna(subset=[GROUP])
        X = _build_X(sub, feats); y = sub[TARGET].values.astype(float)
        ld = sub["lingkar_dada_cm"].values.astype(float); g = sub[GROUP].values
        oof = np.full(len(y), np.nan)
        for tr, te in _splits(X, y, g, eval_mode):
            oof[te] = _fit_predict(method, X[tr], y[tr], X[te], ld[tr], ld[te], log_target)
        m = ~np.isnan(oof)
        y_eval, p_eval = y[m], oof[m]
        finalX, finalY, finalLD = X, y, ld
    else:
        real_train, real_test = _real_holdout(real)
        train_df = synth if scenario == "A" else pd.concat([synth, real_train], ignore_index=True)
        Xtr = _build_X(train_df, feats); ytr = train_df[TARGET].values.astype(float)
        ld_tr = train_df["lingkar_dada_cm"].values.astype(float)
        Xte = _build_X(real_test, feats); ld_te = real_test["lingkar_dada_cm"].values.astype(float)
        y_eval = real_test[TARGET].values.astype(float)
        p_eval = _fit_predict(method, Xtr, ytr, Xte, ld_tr, ld_te, log_target)
        finalX, finalY, finalLD = Xtr, ytr, ld_tr

    metrics, diagnostics = _eval_block(y_eval, p_eval)

    os.makedirs(os.path.join(outdir, "experiments"), exist_ok=True)
    model_ver = f"exp{exp_id}-{method}"
    bundle = _final_bundle(method, finalX, finalY, finalLD, feats, log_target, model_ver)
    joblib.dump(bundle, os.path.join(outdir, "experiments", f"{model_ver}.joblib"))

    return {
        "model_ver": model_ver, "method": method,
        "method_label": METHODS.get(method, method), "features": feats,
        "log_target": bool(log_target), "eval_mode": eval_mode, "scenario": scenario,
        "n_rows": int(len(y_eval)), "metrics": metrics,
        "importance": _importance(method, finalX, finalY, finalLD, feats),
        "diagnostics": diagnostics,
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


def load_bundle(model_ver, outdir):
    """Muat artefak model: 'active' = model aktif; selain itu dari experiments/<ver>.joblib."""
    if model_ver in ("active", "aktif", "", None):
        path = os.path.join(outdir, "model_modul1.joblib")
    else:
        path = os.path.join(outdir, "experiments", f"{model_ver}.joblib")
    return joblib.load(path) if os.path.exists(path) else None


def evaluate_external(bundle, rows):
    """Evaluasi model pada data uji EKSTERNAL (tidak dilatihkan).
    rows = list dict dengan lingkar_dada_cm, (panjang_badan_cm, tinggi_gumba_cm), bobot_timbang_kg.
    """
    y, p, detail = [], [], []
    for r in rows:
        ld = float(r["lingkar_dada_cm"])
        pb = r.get("panjang_badan_cm")
        tg = r.get("tinggi_gumba_cm")
        truth = float(r["bobot_timbang_kg"])
        pred = predict_one(bundle, ld, pb, tg)
        y.append(truth); p.append(pred)
        detail.append({
            "lingkar_dada_cm": _fin(ld, 1),
            "panjang_badan_cm": _fin(pb, 1) if pb not in (None, "") else None,
            "tinggi_gumba_cm": _fin(tg, 1) if tg not in (None, "") else None,
            "aktual": _fin(truth, 1),
            "prediksi": _fin(pred, 1),
            "error_pct": _fin((pred - truth) / truth * 100, 1) if truth else None,
        })
    if not y:
        return {"error": "tidak ada baris valid"}
    metrics, diagnostics = _eval_block(np.array(y, float), np.array(p, float))
    return {"n": len(y), "metrics": metrics, "diagnostics": diagnostics, "detail": detail}


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
