@extends('layouts.admin', ['title' => 'Perhitungan Manual'])
@section('heading', 'Perhitungan Manual — Cara Menghitung Setiap Eksperimen dengan Tangan')

@php
    $bab = [
        ['sampel',   '1. Sampel Data & Notasi'],
        ['statistik','2. Statistik Dasar (rata-rata & simpangan baku)'],
        ['korelasi', '3. Korelasi Pearson'],
        ['metrik',   '4. Metrik Akurasi (MAE, MAPE, RMSE, R², Bias)'],
        ['schoorl',  '5. Baseline Schoorl'],
        ['linear',   '6. Regresi Linear (least squares)'],
        ['loglog',   '7. Power-law / Log-log'],
        ['fitur',    '8. Fitur Rekayasa Allometrik'],
        ['hybrid',   '9. Model Hibrida'],
        ['pohon',    '10. Pohon Keputusan (RF/XGBoost) + contoh split'],
        ['interval', '11. Interval p10–p90 & Coverage'],
        ['cv',       '12. Cross-Validation'],
        ['penutup',  '13. Penutup & Kuis'],
    ];
    $box = 'bg-white rounded-2xl border border-brand-100 shadow-sm p-6 space-y-3 scroll-mt-24';
    $h2  = 'text-xl font-bold text-brand-800';
    $rumus = 'block bg-brand-900 text-brand-50 rounded-xl px-4 py-3 font-mono text-sm my-2 overflow-x-auto';
    $kalk = 'bg-amber-50/60 border border-amber-200 rounded-2xl p-5 space-y-3';
    $inp = 'rounded-lg border-brand-200 bg-white px-3 py-1.5 text-sm w-28';
@endphp

@section('page')
<p class="text-brand-600/80">
    Halaman ini menjabarkan <b>cara menghitung manual</b> setiap angka di platform. Tiap bagian: <b>rumus persis</b> (sama dengan kode), <b>contoh langkah-demi-langkah</b> dengan
    angka nyata, dan sebagian dilengkapi <b>kalkulator interaktif</b> (ketik angka → hasil langsung).
</p>

<div class="bg-white rounded-2xl border border-brand-100 shadow-sm p-4 sticky top-4 z-20">
    <p class="text-xs font-semibold text-brand-500 mb-2">DAFTAR ISI</p>
    <div class="flex flex-wrap gap-2">
        @foreach ($bab as [$id, $judul])
            <a href="#{{ $id }}" class="text-xs px-3 py-1.5 rounded-lg bg-brand-50 border border-brand-100 text-brand-700 hover:bg-brand-100 transition">{{ $judul }}</a>
        @endforeach
    </div>
</div>

{{-- 1 --}}
<section id="sampel" class="{{ $box }}">
    <h2 class="{{ $h2 }}">1. Sampel Data & Notasi</h2>
    <p>Semua contoh memakai <b>4 ekor sapi nyata</b> dari dataset (Hereford). Sengaja sedikit agar bisa dihitung tangan.</p>
    <table class="w-full text-sm border border-brand-100 rounded-lg overflow-hidden">
        <thead class="bg-brand-50/60 text-left text-brand-600"><tr><th class="px-3 py-2">Sapi</th><th class="px-3 py-2">LD (cm)</th><th class="px-3 py-2">PB (cm)</th><th class="px-3 py-2">TG (cm)</th><th class="px-3 py-2">Bobot asli (kg)</th></tr></thead>
        <tbody class="divide-y divide-brand-50">
            <tr><td class="px-3 py-2">A</td><td class="px-3 py-2">189</td><td class="px-3 py-2">178</td><td class="px-3 py-2">133</td><td class="px-3 py-2">516</td></tr>
            <tr><td class="px-3 py-2">B</td><td class="px-3 py-2">173</td><td class="px-3 py-2">153</td><td class="px-3 py-2">121</td><td class="px-3 py-2">358</td></tr>
            <tr><td class="px-3 py-2">C</td><td class="px-3 py-2">185</td><td class="px-3 py-2">168</td><td class="px-3 py-2">123</td><td class="px-3 py-2">460</td></tr>
            <tr><td class="px-3 py-2">D</td><td class="px-3 py-2">171</td><td class="px-3 py-2">142</td><td class="px-3 py-2">119</td><td class="px-3 py-2">314</td></tr>
        </tbody>
    </table>
    <p class="text-sm text-brand-600"><b>Notasi:</b> <code>y</code> = bobot asli · <code>ŷ</code> = bobot tebakan model ·
    <code>n</code> = jumlah data · <code>ȳ</code> = rata-rata · <code>LD/PB/TG</code> = lingkar dada / panjang badan / tinggi gumba.</p>
</section>

{{-- 2 --}}
<section id="statistik" class="{{ $box }}">
    <h2 class="{{ $h2 }}">2. Statistik Dasar</h2>
    <p><b>Rata-rata</b> bobot keempat sapi:</p>
    <code class="{{ $rumus }}">ȳ = (516 + 358 + 460 + 314) / 4 = 1648 / 4 = 412 kg</code>
    <p><b>Simpangan baku (std)</b> — seperti <code>np.std</code> (populasi): akar dari rata-rata kuadrat selisih ke rata-rata.</p>
    <code class="{{ $rumus }}">std = √[ Σ(y − ȳ)² / n ]
selisih: 104, −54, 48, −98   →   kuadrat: 10816, 2916, 2304, 9604
Σ = 25640   →   /4 = 6410   →   √6410 = 80,06 kg</code>
    <p class="text-sm text-brand-600">Angka 6410 (varians) dipakai lagi di bab pohon keputusan; std dipakai di interval prediksi.</p>
</section>

{{-- 3 --}}
<section id="korelasi" class="{{ $box }}">
    <h2 class="{{ $h2 }}">3. Korelasi Pearson (LD vs bobot)</h2>
    <p>Mengukur kekuatan hubungan linear (−1…+1). Rumus:</p>
    <code class="{{ $rumus }}">r = Σ(x−x̄)(y−ȳ) / √[ Σ(x−x̄)² · Σ(y−ȳ)² ]</code>
    <p>x = LD (rata 179,5), y = bobot (rata 412). Langkah:</p>
    <code class="{{ $rumus }}">Σ(x−x̄)(y−ȳ) = 9,5·104 + (−6,5)(−54) + 5,5·48 + (−8,5)(−98) = 2436
Σ(x−x̄)² = 90,25 + 42,25 + 30,25 + 72,25 = 235
Σ(y−ȳ)² = 25640
r = 2436 / √(235 · 25640) = 2436 / 2454,7 = 0,992</code>
    <x-help title="Kenapa di data asli korelasinya cuma 0,299 (Hereford)?">
        <p>4 sapi di atas kebetulan sangat "rapi" sehingga r≈0,99. Pada <b>seluruh</b> 1514 sapi Hereford, r = <b>0,299</b>
        (lemah, karena sapinya seragam) — sedangkan Horqin r = <b>0,914</b>. Inilah kenapa korelasi tinggi = lingkar dada
        penanda bobot yang baik; rendah = dataset "sulit". (Angka ini dihitung dengan rumus yang sama persis di kode.)</p>
    </x-help>
</section>

{{-- 4 --}}
<section id="metrik" class="{{ $box }}">
    <h2 class="{{ $h2 }}">4. Metrik Akurasi</h2>
    <p>Untuk menilai model, kita pakai tebakan <b>Schoorl</b> sebagai ŷ (lihat bab 5) lalu bandingkan ke bobot asli:</p>
    <table class="w-full text-sm border border-brand-100 rounded-lg overflow-hidden">
        <thead class="bg-brand-50/60 text-left text-brand-600"><tr><th class="px-3 py-2">Sapi</th><th class="px-3 py-2">y (asli)</th><th class="px-3 py-2">ŷ (Schoorl)</th><th class="px-3 py-2">error e=y−ŷ</th></tr></thead>
        <tbody class="divide-y divide-brand-50">
            <tr><td class="px-3 py-2">A</td><td class="px-3 py-2">516</td><td class="px-3 py-2">445,21</td><td class="px-3 py-2">+70,79</td></tr>
            <tr><td class="px-3 py-2">B</td><td class="px-3 py-2">358</td><td class="px-3 py-2">380,25</td><td class="px-3 py-2">−22,25</td></tr>
            <tr><td class="px-3 py-2">C</td><td class="px-3 py-2">460</td><td class="px-3 py-2">428,49</td><td class="px-3 py-2">+31,51</td></tr>
            <tr><td class="px-3 py-2">D</td><td class="px-3 py-2">314</td><td class="px-3 py-2">372,49</td><td class="px-3 py-2">−58,49</td></tr>
        </tbody>
    </table>
    <code class="{{ $rumus }}">MAE  = rata-rata |e|            = (70,79+22,25+31,51+58,49)/4 = 183,04/4 = 45,76 kg
MAPE = rata-rata (|e|/y)·100   = (0,1372+0,0622+0,0685+0,1863)/4·100 = 11,35 %
RMSE = √( rata-rata e² )        = √(9920,3/4) = √2480,1 = 49,80 kg
Bias = rata-rata (ŷ−y)          = −(70,79−22,25+31,51−58,49)/4 = −5,39 kg
R²   = 1 − Σe²/Σ(y−ȳ)²          = 1 − 9920,3/25640 = 1 − 0,387 = 0,613</code>
    <p class="text-sm text-brand-600">Artinya: rata-rata meleset 45,8 kg (≈11,4%); R² 0,613 = model menjelaskan ~61% variasi bobot; Bias −5,4 = sedikit cenderung menebak di bawah.</p>

    <div class="{{ $kalk }}" x-data="metrikCalc()">
        <p class="font-semibold text-brand-800">🧮 Kalkulator metrik</p>
        <p class="text-xs text-brand-600">Isi pasangan <code>aktual,prediksi</code> — satu per baris.</p>
        <textarea x-model="teks" rows="4" class="w-full rounded-lg border-brand-200 font-mono text-sm px-3 py-2">516,445.21
358,380.25
460,428.49
314,372.49</textarea>
        <template x-if="hasil">
            <div class="grid grid-cols-2 sm:grid-cols-5 gap-2 text-center">
                <div class="bg-white rounded-lg p-2 border border-brand-100"><p class="text-xs text-brand-500">MAE</p><p class="font-bold text-brand-800" x-text="hasil.mae"></p></div>
                <div class="bg-white rounded-lg p-2 border border-brand-100"><p class="text-xs text-brand-500">MAPE%</p><p class="font-bold text-brand-800" x-text="hasil.mape"></p></div>
                <div class="bg-white rounded-lg p-2 border border-brand-100"><p class="text-xs text-brand-500">RMSE</p><p class="font-bold text-brand-800" x-text="hasil.rmse"></p></div>
                <div class="bg-white rounded-lg p-2 border border-brand-100"><p class="text-xs text-brand-500">R²</p><p class="font-bold text-brand-800" x-text="hasil.r2"></p></div>
                <div class="bg-white rounded-lg p-2 border border-brand-100"><p class="text-xs text-brand-500">Bias</p><p class="font-bold text-brand-800" x-text="hasil.bias"></p></div>
            </div>
        </template>
    </div>
</section>

{{-- 5 --}}
<section id="schoorl" class="{{ $box }}">
    <h2 class="{{ $h2 }}">5. Baseline Schoorl</h2>
    <p>Rumus klasik tetap (tanpa pelatihan), hanya pakai lingkar dada:</p>
    <code class="{{ $rumus }}">Bobot = (LD + 22)² / 100
Sapi A: (189 + 22)² / 100 = 211² / 100 = 44521 / 100 = 445,21 kg</code>
    <div class="{{ $kalk }}" x-data="schoorlCalc()">
        <p class="font-semibold text-brand-800">🧮 Kalkulator Schoorl</p>
        <label class="text-sm">LD (cm): <input type="number" x-model.number="ld" class="{{ $inp }}"></label>
        <p class="text-sm">Bobot = (<span x-text="ld"></span>+22)² / 100 = <b class="text-brand-700" x-text="bobot"></b> kg</p>
    </div>
</section>

{{-- 6 --}}
<section id="linear" class="{{ $box }}">
    <h2 class="{{ $h2 }}">6. Regresi Linear (least squares)</h2>
    <p>Mencari garis <code>ŷ = a + b·LD</code> yang paling pas. Rumus kemiringan & potongan:</p>
    <code class="{{ $rumus }}">b = Σ(x−x̄)(y−ȳ) / Σ(x−x̄)²        a = ȳ − b·x̄</code>
    <p>Dari angka bab 3 (Σ atas = 2436, Σ(x−x̄)² = 235; x̄=179,5; ȳ=412):</p>
    <code class="{{ $rumus }}">b = 2436 / 235 = 10,37 kg per cm
a = 412 − 10,37 · 179,5 = 412 − 1861 = −1449
ŷ = −1449 + 10,37 · LD
Cek LD=189:  −1449 + 10,37·189 = 510,5 kg   (asli 516)</code>
    <p class="text-sm text-brand-600">Pada regresi <b>berganda</b> (banyak fitur), idenya sama tapi koefisien dicari serempak (aljabar matriks) — di platform dikerjakan scikit-learn.</p>
</section>

{{-- 7 --}}
<section id="loglog" class="{{ $box }}">
    <h2 class="{{ $h2 }}">7. Power-law / Log-log</h2>
    <p>Memodelkan <code>ln(bobot) = a + b·ln(LD)</code> — least squares pada nilai yang sudah di-<b>ln</b> (sama dengan <code>np.polyfit(ln LD, ln y, 1)</code>). Langkah: (1) ln-kan LD & bobot, (2) regresi linear seperti bab 6 pada nilai ln, (3) balik dengan <code>exp</code>.</p>
    <code class="{{ $rumus }}">ln LD : 5,242  5,153  5,220  5,142   (rata 5,189)
ln y  : 6,246  5,881  6,131  5,749   (rata 6,002)
b = Σ(dx·dy)/Σ(dx²) = 0,03321 / 0,00728 = 4,56   (eksponen)
a = 6,002 − 4,56·5,189 = −17,68
Prediksi LD=189: exp(−17,68 + 4,56·ln189) = exp(6,24) = 514 kg</code>
    <x-help title="Kenapa eksponen di sini 4,56, bukan ~2,5–3?">
        <p>Eksponen biologis bobot∝LD pangkat ~2,5–3. Di sampel 4-sapi ini hasilnya 4,56 karena cuma 4 titik & cherry-picked.
        Pada data penuh, nilainya mendekati 2,5–3 — itulah "jujur secara biologis".</p>
    </x-help>
    <div class="{{ $kalk }}" x-data="loglogCalc()">
        <p class="font-semibold text-brand-800">🧮 Kalkulator log-log</p>
        <div class="flex flex-wrap gap-3 text-sm items-center">
            <label>LD: <input type="number" x-model.number="ld" class="{{ $inp }}"></label>
            <label>a: <input type="number" step="0.01" x-model.number="a" class="{{ $inp }}"></label>
            <label>b: <input type="number" step="0.01" x-model.number="b" class="{{ $inp }}"></label>
        </div>
        <p class="text-sm">Bobot = exp(a + b·ln LD) = <b class="text-brand-700" x-text="bobot"></b> kg</p>
    </div>
</section>

{{-- 8 --}}
<section id="fitur" class="{{ $box }}">
    <h2 class="{{ $h2 }}">8. Fitur Rekayasa Allometrik</h2>
    <p>Fitur turunan yang dihitung dari ukuran mentah (membantu model menangkap volume). Contoh Sapi A (LD=189, PB=178, TG=133):</p>
    <code class="{{ $rumus }}">ld2    = LD²       = 189²        = 35.721
ld2_pb = LD² · PB  = 35.721·178  = 6.358.338     ← proksi VOLUME
pb_ld  = PB / LD   = 178/189     = 0,942
tg_ld  = TG / LD   = 133/189     = 0,704</code>
    <div class="{{ $kalk }}" x-data="fiturCalc()">
        <p class="font-semibold text-brand-800">🧮 Kalkulator fitur</p>
        <div class="flex flex-wrap gap-3 text-sm items-center">
            <label>LD: <input type="number" x-model.number="ld" class="{{ $inp }}"></label>
            <label>PB: <input type="number" x-model.number="pb" class="{{ $inp }}"></label>
            <label>TG: <input type="number" x-model.number="tg" class="{{ $inp }}"></label>
        </div>
        <div class="text-sm grid sm:grid-cols-4 gap-2">
            <div>ld2 = <b x-text="f.ld2"></b></div>
            <div>ld2_pb = <b x-text="f.ld2pb"></b></div>
            <div>pb_ld = <b x-text="f.pbld"></b></div>
            <div>tg_ld = <b x-text="f.tgld"></b></div>
        </div>
    </div>
</section>

{{-- 9 --}}
<section id="hybrid" class="{{ $box }}">
    <h2 class="{{ $h2 }}">9. Model Hibrida (allometrik + ML)</h2>
    <p>Gabungan: <b>basis</b> log-log menebak kasar, lalu <b>ML (Random Forest)</b> mengoreksi sisanya (residual).</p>
    <code class="{{ $rumus }}">1) base   = exp(a + b·ln LD)              (dari bab 7)
2) residual = y − base                    (selisih yang belum tertangkap basis)
3) RF dilatih menebak residual → residual̂
4) prediksi akhir = base + residual̂</code>
    <p>Contoh Sapi C (LD=185, asli 460), dengan basis log-log ≈ 432 kg:</p>
    <code class="{{ $rumus }}">residual = 460 − 432 = 28 kg   →   RF belajar "di wilayah ini tambah ~+28"
prediksi = 432 + 28 = 460 kg</code>
    <p class="text-sm text-brand-600">Basis menangani biologi & ekstrapolasi; ML menambal pola lokal. Bagian RF tidak dihitung tangan (lihat bab 10).</p>
</section>

{{-- 10 --}}
<section id="pohon" class="{{ $box }}">
    <h2 class="{{ $h2 }}">10. Pohon Keputusan (RF/XGBoost) + Contoh Split</h2>
    <p>Random Forest & XGBoost tersusun dari banyak <b>pohon keputusan</b>. Satu pohon memecah data pada <b>ambang</b>
    yang paling mengurangi <b>varians</b> bobot. Contoh 1 split pada 4 sapi (urut LD): 171, 173, 185, 189 → bobot 314, 358, 460, 516.</p>
    <code class="{{ $rumus }}">Varians sebelum split = 6410   (lihat bab 2)
Split pada LD ≤ 179  →  kiri {314,358}, kanan {460,516}
  kiri : rata 336, varians = (22²+22²)/2 = 484
  kanan: rata 488, varians = (28²+28²)/2 = 784
Varians sesudah (tertimbang) = ½·484 + ½·784 = 634
Pengurangan varians = 6410 − 634 = 5776   → split sangat bagus</code>
    <x-help title="Lalu kenapa tak bisa dihitung penuh manual?">
        <p>Pohon memilih ambang terbaik dengan mencoba banyak titik potong; <b>Random Forest</b> membuat ratusan pohon
        lalu merata-ratakan; <b>XGBoost</b> menambah pohon bertahap untuk memperbaiki sisa error. Konsepnya = pengurangan
        varians di atas, tapi jumlahnya terlalu banyak untuk tangan — itulah tugas komputer. Yang penting kamu paham
        <b>satu split</b>-nya.</p>
    </x-help>
</section>

{{-- 11 --}}
<section id="interval" class="{{ $box }}">
    <h2 class="{{ $h2 }}">11. Interval p10–p90 & Coverage</h2>
    <p>Estimasi diberi rentang, bukan satu angka. Memakai simpangan baku residual dan z = 1,2816 (≈80%).</p>
    <code class="{{ $rumus }}">sd       = std(residual)                  (residual bab 4: 70,79 −22,25 31,51 −58,49)
sd ≈ 49,5 kg
p10/p90  = ŷ ∓ z·sd ,  z = 1,2816  →  z·sd ≈ 63,4 kg
lebar interval = 2·z·sd ≈ 127 kg
coverage = % |residual| ≤ z·sd = 3 dari 4 = 75 %</code>
    <x-help title="Kenapa pakai z·sd, dan beda dengan confidence interval?">
        <p>z=1,2816 adalah titik distribusi normal untuk ~80% di tengah (p10–p90). Interval di sini = <b>prediction
        interval</b> (untuk satu ekor sapi), lebih lebar daripada confidence interval (yang untuk rata-rata), karena
        memasukkan keragaman antar-individu. Coverage idealnya mendekati 80% bila modelnya jujur.</p>
    </x-help>
</section>

{{-- 12 --}}
<section id="cv" class="{{ $box }}">
    <h2 class="{{ $h2 }}">12. Cross-Validation (validasi silang)</h2>
    <p>Agar penilaian adil, data diuji bergiliran:</p>
    <ul class="list-disc pl-5 space-y-1 text-sm">
        <li><b>5-fold acak</b> (<code>KFold n_splits=5</code>): data dibagi 5 bagian. Tiap putaran, 4 bagian untuk latih,
        1 bagian untuk uji → 5 nilai metrik → <b>dirata-rata</b>. Contoh MAPE fold = 8,1; 7,9; 8,4; 7,7; 8,0 → rata 8,02%.</li>
        <li><b>Lintas-dataset</b> (<code>GroupKFold</code>): latih di satu sumber (mis. Hereford), uji di sumber lain
        (Horqin) — menguji generalisasi & mencegah kebocoran.</li>
    </ul>
    <code class="{{ $rumus }}">MAPE_CV = (8,1 + 7,9 + 8,4 + 7,7 + 8,0) / 5 = 40,1 / 5 = 8,02 %</code>
</section>

{{-- 13 --}}
<section id="penutup" class="{{ $box }}">
    <h2 class="{{ $h2 }}">13. Penutup & Kuis</h2>
    <p>Setiap angka di menu Leaderboard, Eksperimen, dan Uji Model berasal dari rumus di atas — semuanya bisa kamu
    reproduksi dengan kalkulator. Untuk berlatih:</p>
    <x-quiz q="Bobot asli 400 kg, model menebak 360 kg. Berapa MAPE-nya?"
            :opsi="['40%', '10%', '11,1%', '90%']"
            :benar="1"
            jawaban="MAPE = |400−360|/400 ×100 = 40/400×100 = 10%." />
    <x-quiz q="Schoorl untuk LD = 200 cm?"
            :opsi="['400 kg', '492,84 kg', '222 kg', '4,84 kg']"
            :benar="1"
            jawaban="(200+22)²/100 = 222²/100 = 49284/100 = 492,84 kg." />
</section>

<p class="text-center text-sm text-brand-500 py-4">Selesai — buka <a href="{{ route('admin.uji') }}" class="text-brand-700 underline">Uji Model</a> atau <a href="{{ route('admin.leaderboard') }}" class="text-brand-700 underline">Leaderboard</a> untuk melihat angka-angka ini pada model nyata. <a href="#sampel" class="text-brand-700 underline">↑ atas</a></p>

<script>
    function metrikCalc() {
        return {
            teks: '516,445.21\n358,380.25\n460,428.49\n314,372.49',
            get hasil() {
                const p = this.teks.trim().split('\n')
                    .map(l => l.split(',').map(s => parseFloat(s.trim())))
                    .filter(r => r.length === 2 && !isNaN(r[0]) && !isNaN(r[1]) && r[0] !== 0);
                const n = p.length; if (!n) return null;
                const y = p.map(r => r[0]), yh = p.map(r => r[1]);
                const e = y.map((v, i) => v - yh[i]);
                const mae = e.reduce((s, v) => s + Math.abs(v), 0) / n;
                const mape = e.reduce((s, v, i) => s + Math.abs(v) / y[i], 0) / n * 100;
                const rmse = Math.sqrt(e.reduce((s, v) => s + v * v, 0) / n);
                const bias = yh.reduce((s, v, i) => s + (v - y[i]), 0) / n;
                const yb = y.reduce((a, b) => a + b, 0) / n;
                const sstot = y.reduce((s, v) => s + (v - yb) ** 2, 0);
                const ssres = e.reduce((s, v) => s + v * v, 0);
                const r2 = sstot ? 1 - ssres / sstot : NaN;
                return { mae: mae.toFixed(2), mape: mape.toFixed(2), rmse: rmse.toFixed(2), bias: bias.toFixed(2), r2: r2.toFixed(4) };
            }
        };
    }
    function schoorlCalc() {
        return { ld: 189, get bobot() { return (((+this.ld) + 22) ** 2 / 100).toFixed(2); } };
    }
    function loglogCalc() {
        return { ld: 189, a: -17.68, b: 4.56, get bobot() { return Math.exp((+this.a) + (+this.b) * Math.log(+this.ld)).toFixed(1); } };
    }
    function fiturCalc() {
        return {
            ld: 189, pb: 178, tg: 133,
            get f() {
                const ld = +this.ld, pb = +this.pb, tg = +this.tg;
                return { ld2: (ld * ld).toFixed(0), ld2pb: (ld * ld * pb).toFixed(0), pbld: (pb / ld).toFixed(3), tgld: (tg / ld).toFixed(3) };
            }
        };
    }
</script>
@endsection
