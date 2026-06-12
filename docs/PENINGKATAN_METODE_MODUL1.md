# Strategi Peningkatan Metode — Modul 1 (Estimasi Bobot Sapi)

Panduan data science untuk **meng-improve metode** estimasi bobot dari ukuran tubuh
(lingkar dada/LD, panjang badan/PB, tinggi gumba/TG). Dokumen ini ditujukan sebagai
acuan tim sekaligus kerangka kedalaman metodologi yang dinilai pembimbing/penguji,
dan langsung dapat dijalankan sebagai eksperimen di leaderboard platform.

> Prinsip pengarah: tujuan bukan sekadar **MAPE terendah**, melainkan **studi
> komparatif yang dapat dipertahankan secara ilmiah** — paham *mengapa* sebuah
> metode menang/kalah (allometri, ekstrapolasi, heteroskedastisitas), desain
> eksperimen yang benar, dan kontribusi orisinal.

---

## 0. Kondisi awal (titik tolak)

Leaderboard saat ini (data publik Hereford + Horqin, evaluasi cross-validation):

| Metode | MAPE | Catatan |
|---|---|---|
| XGBoost | ~7,7% | terbaik saat ini |
| Random Forest | ~8,0% | |
| Regresi linear berganda | ~8,3% | |
| Power-law / log-log (LD) | ~9,4% | 1 fitur |
| Baseline Schoorl | ~14,4% | rumus tetap, kalibrasi sapi Eropa |

Fakta kunci yang membentuk seluruh strategi: korelasi **LD–bobot** = **0,30 di
Hereford** (sapi seragam, umur 3 & 5 tahun saja) vs **0,91 di Horqin**. Ada
**plafon data** — bukan semata masalah model.

---

## 1. Urutan leverage (paling berdampak → paling kecil)

1. **Kualitas & keragaman data** (terbesar)
2. **Rekayasa fitur berbasis biologi**
3. **Transformasi target (ruang log)**
4. **Model hibrida (atasi ekstrapolasi)** — sekaligus novelty
5. **Kelas model statistik tambahan**
6. **Tuning hyperparameter yang benar**
7. **Rigor validasi** (membuat semua gain di atas *kredibel*)

Tuning model murni (no. 6) sering memberi perbaikan paling kecil — sebaliknya
fitur & data memberi lompatan terbesar. Ini sendiri sudah temuan layak ditulis.

---

## 2. Data dulu, baru model

Karena Hereford ber-korelasi 0,30, **tak ada metode yang bisa mengarang sinyal
yang tidak ada di sana**. Implikasi:

- Gain akurasi terbesar = **menambah data nyata beragam**: rentang bobot lebar,
  banyak ras/umur/jenis kelamin, **utamanya sapi lokal Kaltara (Bali/PO/Brahman)**.
  Inilah alasan arsitektur "researcher mengumpulkan data" itu tepat.
- **Buktikan dengan learning curve**: plot akurasi (MAPE) vs ukuran data latih.
  Bila kurva masih menurun, artinya menambah data masih berbuah — argumen kuat
  untuk pengumpulan data lapangan.
- **Bersihkan data**: deteksi outlier (z-score robust/MAD), satuan salah
  (inci↔cm), dan *errors-in-variables* (LD diukur dengan galat pita ukur).

**Pesan untuk artikel:** menyadari & mengukur plafon data adalah tanda kematangan
data science, bukan kelemahan.

---

## 3. Rekayasa fitur berbasis biologi (murah, dampak besar)

Model tidak otomatis menemukan hukum allometrik. Hubungan fisik:

```
Bobot  ∝  Volume  ∝  lingkar_dada²  ×  panjang_badan
```

Karena itu **tambahkan fitur turunan** (bukan hanya LD/PB/TG mentah):

| Fitur baru | Alasan |
|---|---|
| `LD²` | bobot ∝ pangkat ~2–3 dimensi linear |
| `LD² · PB` | proksi volume tubuh (inti rumus Schoorl/Crevat) |
| `LD³` | alternatif pangkat penuh |
| `PB/LD`, `TG/LD` | rasio bentuk tubuh (gemuk vs kurus) |
| ras (one-hot), umur, kelamin, BCS | efek non-morfometrik |

Efek tipikal: **regresi linear sederhana melonjak akurasinya** hanya dari fitur
`LD²·PB` — sering menyamai/mengalahkan ML tanpa fitur turunan. Ini menegaskan
"feature engineering > model complexity".

---

## 4. Transformasi target & fungsi galat

**Modelkan `ln(bobot)`**, lalu balik dengan `exp()`:

- Galat biologis **multiplikatif & heteroskedastik** — sapi besar wajar meleset
  lebih banyak dalam kg, tetapi serupa dalam %. Ruang log menstabilkan ini.
- Konsisten dengan power-law: `ln(BB) = a + b·ln(LD)`, eksponen `b ≈ 2,5–3`
  (jujur secara biologis).
- Pilih metrik utama **MAPE** (relatif, mudah dijelaskan ke peternak) dan
  laporkan RMSE/MAE untuk konteks. Diagnostik residual: bila plot *residual vs
  fitted* berbentuk corong → bukti perlunya transformasi log.

---

## 5. Masalah ekstrapolasi → model hibrida (NOVELTY)

**Model berbasis pohon (RF, XGBoost) tidak dapat mengekstrapolasi** di luar
rentang bobot data latih — prediksi "mendatar" untuk sapi sangat besar/kecil.
Solusi yang sekaligus menjadi **kontribusi orisinal untuk artikel**:

> **Hibrida Allometrik + ML (koreksi residual):**
> 1. Basis allometrik: `BB̂_base = a · LD^b` (di-fit dulu).
> 2. Hitung residual: `r = BB − BB̂_base`.
> 3. ML (RF/XGBoost/GBM) memprediksi residual `r̂` dari fitur.
> 4. Prediksi akhir: `BB̂ = BB̂_base + r̂`.

Basis menangani ekstrapolasi & biologi; ML menangkap pola lokal yang tersisa.
Pendekatan ini jarang dipakai pada estimasi bobot ternak → **celah riset jelas**,
cocok target Sinta 2. Bandingkan: allometrik murni vs ML murni vs hibrida.

---

## 6. Interval prediksi yang benar (uncertainty)

Saat ini p10–p90 memakai aproksimasi normal dari simpangan residual. Tingkatkan:

- **Quantile regression** (mis. LightGBM dengan `objective=quantile`, `alpha=0.1`
  dan `0.9`) → interval p10/p90 langsung, tanpa asumsi normal.
- **Kalibrasi interval (reliability)**: ukur *coverage* — apakah interval "90%"
  benar menangkap ~90% kasus uji. Interval yang **sempit + coverage tepat** =
  model percaya diri & benar.
- Penguji menghargai pembahasan *uncertainty*, bukan hanya titik prediksi.

---

## 7. Kelas model tambahan yang "berbobot" statistik

Bukan menumpuk model, tetapi menambah yang **menjawab pertanyaan**:

- **Mixed/hierarchical model** dengan ras sebagai *random effect* (partial
  pooling) → menstabilkan ras berdata sedikit; sangat dihargai secara statistik.
  (`statsmodels.MixedLM` atau pendekatan Bayesian.)
- **GAM** (Generalized Additive Model) — hubungan non-linear mulus & tetap
  interpretable.
- **SVR** dan **Gaussian Process Regression** — kuat untuk data kecil + memberi
  ketidakpastian (GP).
- **Stacking/ensemble** — gabungkan allometrik + ML + mixed model lewat
  meta-learner.

Pemetaan ke peran tim (satu metode per orang) tetap berlaku — semua diadu di
leaderboard yang sama.

---

## 8. Tuning hyperparameter yang benar

Saat ini hyperparameter tetap (mis. 300 pohon, kedalaman 4). Tingkatkan:

- **Pencarian sistematis**: grid/random search atau **Bayesian (Optuna)**.
- **Nested cross-validation**: loop dalam untuk tuning, loop luar untuk estimasi
  performa — mencegah **bias optimistik** (bocornya test ke pemilihan parameter).
- Laporkan ruang pencarian & parameter terpilih (reproducibility).

---

## 9. Rigor validasi (inti penilaian "data science")

Akurasi tinggi tak bernilai bila validasinya lemah. Yang membuat hasil kredibel:

- **Grouped cross-validation per peternak/wilayah/ras** — cegah kebocoran (model
  menghafal peternak, bukan belajar ukuran→bobot). Platform sudah punya mode
  "lintas"; jadikan standar.
- **Repeated CV / bootstrap → interval kepercayaan metrik.** "MAPE 7,7% vs 7,9%"
  belum tentu beda nyata; uji signifikansi (mis. paired t-test/Wilcoxon atas fold)
  sebelum mengklaim menang.
- **Validasi eksternal**: latih Horqin → uji Hereford (dan sebaliknya) sebagai
  uji generalisasi antar-populasi.
- **Tiga skenario sumber data** (sudah ada di platform): B nyata→nyata (angka
  utama), A sintetis→nyata (validasi data sintetis), C gabungan. Membandingkan
  ketiganya = temuan menarik, bukan sekadar satu angka.
- **Kalibrasi interval** & **learning curve** (bagian 2 & 6).

---

## 10. Analisis error (kematangan)

- **Error tersegmentasi**: rinci MAPE per **rentang bobot / ras / umur**. Model
  sering bagus di tengah, buruk di ekstrem (ekstrapolasi).
- **Kasus terburuk**: telusuri residual terbesar — kerap menemukan **data salah**,
  bukan model salah → umpan balik ke pembersihan data.
- **Interpretabilitas**: koefisien (regresi), *feature importance* (pohon), SHAP
  → menyaring model yang "benar karena alasan salah".

---

## 11. Pemetaan ke platform & prioritas implementasi

Leaderboard sudah menjadi alat pembanding adil (test set & skrip metrik dikunci).
Tiap perbaikan dijalankan sebagai eksperimen lalu diadu. Prioritas tercepat-berbuah:

| Prioritas | Perbaikan | Dampak | Effort |
|---|---|---|---|
| 1 | Fitur allometrik (`LD²·PB`, rasio) + opsi target **log** | tinggi | rendah |
| 2 | **Model hibrida** allometrik+ML | tinggi (+novelty) | sedang |
| 3 | **Tuning** (Optuna) + **repeated CV + CI** | sedang (rigor) | sedang |
| 4 | **Quantile interval** + kalibrasi | sedang | sedang |
| 5 | **Mixed model** (ras random effect) | sedang | sedang |
| 6 | Learning curve + analisis error tersegmentasi | sedang (rigor) | rendah |

Urutan yang disarankan: **1 → 2 → 3 → 4** lebih dulu (akurasi + nilai artikel
paling besar), lalu 5 & 6 untuk kelengkapan.

---

## 12. Narasi untuk artikel/SLR

Rangkaian pekerjaan di atas otomatis menjadi badan naskah:

- **RQ1**: Bagaimana performa regresi konvensional vs machine learning untuk
  estimasi bobot sapi dari morfometri?
- **RQ2**: Apakah pendekatan **hibrida allometrik+ML** mengatasi keterbatasan
  ekstrapolasi model pohon sekaligus meningkatkan akurasi?
- **RQ3**: Seberapa valid **data sintetis** menyiapkan model untuk data nyata
  (skenario A vs B vs C)?
- **RQ4**: Bagaimana **rekalibrasi lokal** (sapi Kaltara) terhadap rumus klasik
  yang bias untuk ternak tropis?

**Kontribusi orisinal yang dijual**: (a) hibrida allometrik+ML, (b) rekalibrasi
ternak lokal Kaltara, (c) studi validasi data sintetis. Baseline klasik
(Schoorl/Winter) **wajib** sebagai pembanding — bila ML tak mengalahkannya, belum
ada kontribusi.

---

## 13. Checklist ringkas

- [ ] Tambah fitur `LD²`, `LD²·PB`, rasio bentuk; sediakan opsi target log.
- [ ] Implementasi model hibrida (allometrik base + ML residual).
- [ ] Tuning hyperparameter (Optuna) dengan nested CV.
- [ ] Quantile regression untuk interval + ukur kalibrasi coverage.
- [ ] Tambah mixed model (ras random effect).
- [ ] Repeated/grouped CV → laporkan metrik dengan interval kepercayaan + uji signifikansi.
- [ ] Validasi eksternal antar-dataset; bandingkan skenario A/B/C.
- [ ] Learning curve; analisis error per segmen; telusuri kasus terburuk.
- [ ] Kumpulkan data sapi lokal Kaltara (gain terbesar).

---

### Intinya
Untuk pembimbing yang menekankan ranah data science: yang dinilai bukan satu angka
MAPE, melainkan **rekayasa fitur yang berdasar biologi, desain validasi yang anti
bocor & terukur ketidakpastiannya, kontribusi metodologis (hibrida), serta
kejujuran tentang plafon data**. Akurasi yang naik adalah hasil sampingan dari
metodologi yang benar — bukan tujuan tunggal.
