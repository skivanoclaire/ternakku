#!/usr/bin/env python3
"""
prep_data.py — Bersihkan & petakan dataset publik bobot sapi ke skema `pengukuran` TernakKu.

Input (taruh di folder yang sama atau beri --indir):
  - Hereford_cows.csv      (Ruchay et al., GitHub ruchaya/Hereford_cows; sep=';')
  - measurements.xlsx      (Mendeley Horqin, Bai L., DOI 10.17632/h2s22wr5py.3)

Output (default ./out):
  - pengukuran_public.csv  (gabungan, kolom seragam skema `pengukuran`, source=public)
  - dataset_source.csv     (katalog sumber: lisensi & sitasi)
  - report.txt             (ringkasan kualitas: baris, missing, korelasi, baseline)

Pemakaian:
  python3 prep_data.py --indir . --outdir ./out
"""
import argparse, os, sys
import numpy as np
import pandas as pd

# ---- Skema target (sesuai tabel `pengukuran` di Rancangan_Web_TernakKu) ----
TARGET_COLS = [
    "src_dataset",        # asal dataset (hereford / horqin)
    "src_animal_id",      # id asli di dataset sumber
    "jenis",              # sapi
    "ras",                # bangsa
    "umur_estimasi_bulan",# umur (bulan) bila ada
    "lingkar_dada_cm",    # heart girth
    "panjang_badan_cm",   # oblique body length
    "tinggi_gumba_cm",    # withers height
    "bobot_timbang_kg",   # body weight (ground truth)
    "source",             # synthetic / public / farmer  -> di sini selalu 'public'
]

def clean_hereford(path):
    df = pd.read_csv(path, sep=";", encoding="latin-1")
    df.columns = [c.strip() for c in df.columns]
    out = pd.DataFrame()
    out["src_animal_id"] = df["identificator"].astype(str)   # defines row count
    out["src_dataset"] = "hereford"
    out["jenis"] = "sapi"
    out["ras"] = "Hereford"
    out["umur_estimasi_bulan"] = pd.to_numeric(df["Age years"], errors="coerce") * 12
    out["lingkar_dada_cm"] = pd.to_numeric(df["heart girth"], errors="coerce")
    out["panjang_badan_cm"] = pd.to_numeric(df["oblique body length"], errors="coerce")
    out["tinggi_gumba_cm"] = pd.to_numeric(df["withers height"], errors="coerce")
    out["bobot_timbang_kg"] = pd.to_numeric(df["Body weight"], errors="coerce")
    out["source"] = "public"

    n0 = len(out)
    # Buang baris dengan nilai mustahil (heart girth salah input pada beberapa baris)
    bad = (
        out["lingkar_dada_cm"] < 150) | (out["lingkar_dada_cm"] > 260) | (
        out["bobot_timbang_kg"] < 100) | (out["bobot_timbang_kg"] > 1200) | (
        out["panjang_badan_cm"] < 80)
    removed = int(bad.sum())
    out = out[~bad].reset_index(drop=True)
    return out, n0, removed

def clean_horqin(path):
    df = pd.read_excel(path)
    # Normalisasi nama kolom (hilangkan spasi/satuan)
    ren = {}
    for c in df.columns:
        k = c.lower().replace(" ", "").replace("(cm)", "").replace("(kg)", "")
        ren[c] = k
    df = df.rename(columns=ren)
    out = pd.DataFrame()
    out["src_animal_id"] = df["num"].astype(str)   # defines row count
    out["src_dataset"] = "horqin"
    out["jenis"] = "sapi"
    out["ras"] = "Horqin"
    out["umur_estimasi_bulan"] = np.nan  # tidak tersedia
    out["lingkar_dada_cm"] = pd.to_numeric(df["heartgirth"], errors="coerce")
    out["panjang_badan_cm"] = pd.to_numeric(df["obliquebodylength"], errors="coerce")
    out["tinggi_gumba_cm"] = pd.to_numeric(df["withersheight"], errors="coerce")
    out["bobot_timbang_kg"] = pd.to_numeric(df["bodyweight"], errors="coerce")
    out["source"] = "public"
    n0 = len(out)
    bad = out[["lingkar_dada_cm", "panjang_badan_cm", "tinggi_gumba_cm", "bobot_timbang_kg"]].isna().any(axis=1)
    removed = int(bad.sum())
    out = out[~bad].reset_index(drop=True)
    return out, n0, removed

def quality(df, name):
    lines = [f"[{name}] baris={len(df)}"]
    w = df["bobot_timbang_kg"]; hg = df["lingkar_dada_cm"]
    lines.append("  bobot kg: min %.0f max %.0f mean %.0f" % (w.min(), w.max(), w.mean()))
    r = df["lingkar_dada_cm"].corr(w)
    lines.append("  korelasi lingkar_dada vs bobot: %.3f" % r)
    x = hg.values; y = w.values
    b, a = np.polyfit(x, y, 1); p = a + b * x
    r2 = 1 - ((y - p) ** 2).sum() / ((y - y.mean()) ** 2).sum()
    mape = (np.abs(y - p) / y).mean() * 100
    lines.append("  baseline lingkar_dada->bobot: R2=%.3f MAPE=%.1f%%" % (r2, mape))
    return "\n".join(lines)

def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("--indir", default=".")
    ap.add_argument("--outdir", default="./out")
    args = ap.parse_args()
    os.makedirs(args.outdir, exist_ok=True)

    her_path = os.path.join(args.indir, "Hereford_cows.csv")
    hor_path = os.path.join(args.indir, "measurements.xlsx")
    frames, report = [], []

    if os.path.exists(her_path):
        her, n0, rm = clean_hereford(her_path)
        frames.append(her); report.append("Hereford: %d -> %d baris (buang %d rusak)" % (n0, len(her), rm))
        report.append(quality(her, "Hereford"))
    else:
        report.append("LEWAT: Hereford_cows.csv tidak ditemukan di " + args.indir)

    if os.path.exists(hor_path):
        hor, n0, rm = clean_horqin(hor_path)
        frames.append(hor); report.append("Horqin: %d -> %d baris (buang %d)" % (n0, len(hor), rm))
        report.append(quality(hor, "Horqin"))
    else:
        report.append("LEWAT: measurements.xlsx tidak ditemukan di " + args.indir)

    if not frames:
        sys.exit("Tidak ada input dataset yang ditemukan. Periksa --indir.")

    allrows = pd.concat(frames, ignore_index=True)[TARGET_COLS]
    out_csv = os.path.join(args.outdir, "pengukuran_public.csv")
    allrows.to_csv(out_csv, index=False)

    src = pd.DataFrame([
        {"source": "public", "src_dataset": "hereford",
         "nama": "Hereford cows (Ruchay et al.)", "ras": "Hereford", "n_ekor": int((allrows.src_dataset=='hereford').sum()),
         "lisensi": "Open access (GitHub)", "sitasi": "Ruchay A. et al., IOP Conf. Ser. EES 624:012056 (2021)",
         "url": "https://github.com/ruchaya/Hereford_cows"},
        {"source": "public", "src_dataset": "horqin",
         "nama": "Cattle side & back view (Bai L.)", "ras": "Horqin", "n_ekor": int((allrows.src_dataset=='horqin').sum()),
         "lisensi": "CC BY 4.0", "sitasi": "Bai, Lili. Mendeley Data, DOI 10.17632/h2s22wr5py.3 (2025)",
         "url": "https://data.mendeley.com/datasets/h2s22wr5py/3"},
    ])
    src.to_csv(os.path.join(args.outdir, "dataset_source.csv"), index=False)

    report.append("\nGABUNGAN: %d baris -> %s" % (len(allrows), out_csv))
    report_txt = "\n".join(report)
    with open(os.path.join(args.outdir, "report.txt"), "w") as f:
        f.write(report_txt + "\n")
    print(report_txt)

if __name__ == "__main__":
    main()
