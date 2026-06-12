@extends('layouts.admin', ['title' => 'Modul Belajar'])
@section('heading', 'Modul Belajar — Data Science TernakKu')

@php
    $bab = [
        ['data-itu',  '1. Apa Itu Data?'],
        ['peta',      '2. Business Analytics, Data Science, AI & ML'],
        ['analitik',  '3. Empat Tingkat Analitik'],
        ['klasifikasi','4. Klasifikasi Metode Machine Learning'],
        ['masalah',   '5. Masalah yang Dipecahkan TernakKu'],
        ['konsep',    '6. Konsep Inti: Fitur, Target, Latih, Uji'],
        ['datakita',  '7. Data Kita & Plafon Data'],
        ['fitur',     '8. Fitur & Hukum Allometrik'],
        ['metode',    '9. Metode/Model & Kelebihan-Kekurangan'],
        ['metrik',    '10. Metrik Akurasi (Singkatan & Definisi)'],
        ['validasi',  '11. Validasi: Latih/Uji, CV, Overfitting'],
        ['skenario',  '12. Skenario Data & Baseline'],
        ['kasus',     '13. Studi Kasus: Sapi Lokal vs Besar'],
        ['sistem',    '14. Arsitektur Sistem'],
        ['improve',   '15. Cara Meningkatkan Model'],
        ['glosarium', '16. Glosarium Singkatan'],
    ];
    $box = 'bg-white rounded-2xl border border-brand-100 shadow-sm p-6 space-y-3 scroll-mt-24';
    $h2  = 'text-xl font-bold text-brand-800';
    $rumus = 'block bg-brand-900 text-brand-50 rounded-xl px-4 py-3 font-mono text-sm my-2';
@endphp

@section('page')
<p class="text-brand-600/80">
    Modul untuk <b>pemula yang baru belajar data science</b>. Mulai dari "apa itu data" sampai cara kerja model di
    TernakKu. Setiap singkatan dijelaskan kepanjangan + artinya. Baca berurutan, atau lompat lewat daftar isi.
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
<section id="data-itu" class="{{ $box }}">
    <h2 class="{{ $h2 }}">1. Apa Itu Data?</h2>
    <p><b>Data</b> = fakta mentah yang dicatat. Contoh: seekor sapi punya lingkar dada 140 cm dan bobot 205 kg —
    itu data. Bila banyak data diolah jadi sesuatu yang berguna ("rata-rata bobot sapi Bali 190 kg"), itu menjadi
    <b>informasi</b>; bila dipakai mengambil keputusan, menjadi <b>pengetahuan</b>.</p>
    <p>Data biasanya disusun seperti tabel: <b>baris</b> = satu objek (satu ekor sapi), <b>kolom</b> = satu sifat
    (lingkar dada, bobot). Jenis data:</p>
    <ul class="list-disc pl-5 space-y-1">
        <li><b>Numerik</b> — angka (lingkar dada 140 cm, bobot 205 kg). Bisa dihitung rata-ratanya.</li>
        <li><b>Kategorik</b> — kategori/label (jenis kelamin: jantan/betina; ras: Bali/Simbal).</li>
    </ul>
    <p class="text-sm text-brand-600">Di TernakKu, satu baris tabel <code>pengukuran</code> = satu pengukuran seekor sapi.</p>
</section>

{{-- 2 --}}
<section id="peta" class="{{ $box }}">
    <h2 class="{{ $h2 }}">2. Business Analytics, Data Science, AI & ML</h2>
    <p>Istilah-istilah ini sering tertukar. Definisi singkatnya:</p>
    <ul class="list-disc pl-5 space-y-1">
        <li><b>Business Analytics / Business Intelligence (BI)</b> — menggunakan data untuk <b>keputusan bisnis</b>
        (mis. laporan penjualan, dashboard harga). Fokus: menjawab pertanyaan bisnis.</li>
        <li><b>Data Science</b> — bidang luas memadukan <b>data + statistik + pemrograman + pengetahuan domain</b>
        untuk menggali pola dan membuat prediksi. Mencakup BI sekaligus ML.</li>
        <li><b>AI (Artificial Intelligence / Kecerdasan Buatan)</b> — payung besar: membuat mesin "cerdas".</li>
        <li><b>ML (Machine Learning / Pembelajaran Mesin)</b> — bagian dari AI: mesin <b>belajar pola dari data</b>
        tanpa diprogram rumusnya satu per satu.</li>
        <li><b>Deep Learning</b> — bagian dari ML memakai jaringan saraf tiruan (untuk gambar/teks; mis. estimasi dari foto).</li>
    </ul>
    <code class="{{ $rumus }}">AI  ⊃  ML  ⊃  Deep Learning      ·      Data Science memakai semuanya + statistik + domain</code>
    <p class="text-sm text-brand-600"><b>TernakKu</b> = produk data science: memakai ML (regresi) untuk memprediksi
    bobot, dan BI (dashboard, leaderboard) untuk memantau.</p>
</section>

{{-- 3 --}}
<section id="analitik" class="{{ $box }}">
    <h2 class="{{ $h2 }}">3. Empat Tingkat Analitik</h2>
    <p>Cara umum mengklasifikasikan "kedalaman" analisis data, dari sederhana ke canggih:</p>
    <table class="w-full text-sm border border-brand-100 rounded-lg overflow-hidden">
        <thead class="bg-brand-50/60 text-left text-brand-600"><tr><th class="px-3 py-2">Tingkat</th><th class="px-3 py-2">Menjawab</th><th class="px-3 py-2">Contoh di TernakKu</th></tr></thead>
        <tbody class="divide-y divide-brand-50">
            <tr><td class="px-3 py-2"><b>Deskriptif</b></td><td class="px-3 py-2">Apa yang terjadi?</td><td class="px-3 py-2">dashboard: jumlah data, rata-rata bobot</td></tr>
            <tr><td class="px-3 py-2"><b>Diagnostik</b></td><td class="px-3 py-2">Kenapa terjadi?</td><td class="px-3 py-2">analisis korelasi LD↔bobot, error per ras</td></tr>
            <tr><td class="px-3 py-2"><b>Prediktif</b></td><td class="px-3 py-2">Apa yang akan terjadi?</td><td class="px-3 py-2"><b>estimasi bobot (Modul 1)</b> ← fokus kita</td></tr>
            <tr><td class="px-3 py-2"><b>Preskriptif</b></td><td class="px-3 py-2">Apa yang sebaiknya dilakukan?</td><td class="px-3 py-2">rekomendasi waktu jual optimal (Modul 6)</td></tr>
        </tbody>
    </table>
    <p class="text-sm text-brand-600">Modul 1 berada di tingkat <b>prediktif</b> — inti nilai platform.</p>
</section>

{{-- 4 --}}
<section id="klasifikasi" class="{{ $box }}">
    <h2 class="{{ $h2 }}">4. Klasifikasi Metode Machine Learning</h2>
    <p>ML dikelompokkan berdasarkan <b>ada/tidaknya "kunci jawaban" (label)</b>:</p>
    <ul class="list-disc pl-5 space-y-2">
        <li><b>Supervised Learning (terbimbing)</b> — data punya kunci jawaban. Mesin belajar dari pasangan
        (input → jawaban benar). Dibagi dua:
            <ul class="list-disc pl-5 mt-1">
                <li><b>Regresi (Regression)</b> — jawaban berupa <b>angka kontinu</b>. Contoh: bobot (kg).
                <span class="text-brand-700 font-semibold">← TernakKu Modul 1 ada di sini.</span></li>
                <li><b>Klasifikasi (Classification)</b> — jawaban berupa <b>kategori</b>. Contoh: layak qurban / tidak; sehat / sakit.</li>
            </ul>
        </li>
        <li><b>Unsupervised Learning (tanpa terbimbing)</b> — tanpa kunci jawaban; mesin mencari struktur sendiri.
        Contoh: <b>clustering</b> (mengelompokkan sapi serupa), deteksi anomali harga (Modul 4).</li>
        <li><b>Reinforcement Learning (penguatan)</b> — belajar lewat coba-coba + hadiah/hukuman (mis. robot, game).</li>
    </ul>
    <p class="bg-brand-50 border border-brand-100 rounded-lg px-4 py-2 text-sm">
        <b>Jenis problem kita: Supervised Learning → Regresi.</b> Inputnya ukuran tubuh (angka), jawabannya bobot (angka),
        dan kita punya kunci jawaban (bobot timbang asli) untuk belajar.</p>
</section>

{{-- 5 --}}
<section id="masalah" class="{{ $box }}">
    <h2 class="{{ $h2 }}">5. Masalah yang Dipecahkan TernakKu</h2>
    <p>Peternak rakyat jarang punya timbangan → tak tahu bobot pasti → tengkulak menentukan harga sepihak
    (<b>asimetri informasi</b>). TernakKu memperkirakan bobot dari <b>ukuran tubuh</b> (diukur pita) sehingga peternak
    punya angka untuk menawar. Tujuannya: tebakan yang <b>akurat</b> dan <b>jujur</b> (memberi rentang, bukan satu angka).</p>
</section>

{{-- 6 --}}
<section id="konsep" class="{{ $box }}">
    <h2 class="{{ $h2 }}">6. Konsep Inti: Fitur, Target, Latih, Uji</h2>
    <ul class="list-disc pl-5 space-y-1">
        <li><b>Fitur (feature / variabel input)</b>: yang dipakai menebak — LD, PB, TG.
            <br><span class="text-sm text-brand-500">LD = Lingkar Dada, PB = Panjang Badan, TG = Tinggi Gumba (tinggi punuk bahu).</span></li>
        <li><b>Target (label / variabel output)</b>: yang ditebak — bobot (kg).</li>
        <li><b>Ground truth (kebenaran dasar)</b>: bobot asli hasil timbang — kunci jawaban.</li>
        <li><b>Melatih (training)</b>: model melihat banyak pasangan (ukuran → bobot asli) dan menyesuaikan diri.</li>
        <li><b>Menguji (testing)</b>: model dinilai pada data yang <b>belum pernah dilihat</b>.</li>
    </ul>
    <x-help title="Analogi">
        <p>Seperti murid belajar dari soal + kunci jawaban (data latih), lalu diuji soal baru (data uji). Kalau cuma
        menghafal soal lama, nilainya bagus saat latihan tapi jeblok di ujian → itu <b>overfitting</b> (lihat bab 11).</p>
    </x-help>
</section>

{{-- 7 --}}
<section id="datakita" class="{{ $box }}">
    <h2 class="{{ $h2 }}">7. Data Kita & Plafon Data</h2>
    <p>Tiga sumber data (ditandai <code>source</code>): <b>synthetic</b> (dibangkitkan rumus), <b>public</b> (jurnal),
    <b>farmer</b> (peternak, tanpa bobot). Akurasi final hanya dihitung pada data nyata.</p>
    <p class="font-semibold text-brand-800">Pelajaran kunci: kualitas data &gt; kerumitan model.</p>
    <p>Contoh nyata: dataset <b>Hereford</b> korelasi LD–bobot cuma <b>0,30</b> (sapi seragam), <b>Horqin</b> <b>0,91</b>
    (sinyal kuat). Ada <b>plafon data</b>: kalau sinyal lemah, ganti algoritma tak menolong — yang menolong data lebih baik.</p>
</section>

{{-- 8 --}}
<section id="fitur" class="{{ $box }}">
    <h2 class="{{ $h2 }}">8. Fitur & Hukum Allometrik</h2>
    <p>Biologi: bobot sebanding dengan <b>volume</b> tubuh; volume ≈ luas dada × panjang:</p>
    <code class="{{ $rumus }}">Bobot ∝ Volume ∝ (lingkar dada)² × panjang badan</code>
    <p>Komputer tak otomatis tahu ini, jadi kita beri <b>fitur rekayasa</b>: <b>LD²</b>, <b>LD²·PB</b> (proksi volume —
    paling kuat), serta rasio <b>PB/LD</b>, <b>TG/LD</b>. Bisa dicoba di menu Latih Model.</p>
</section>

{{-- 9 --}}
<section id="metode" class="{{ $box }}">
    <h2 class="{{ $h2 }}">9. Metode/Model & Kelebihan–Kekurangan</h2>
    <table class="w-full text-sm border border-brand-100 rounded-lg overflow-hidden">
        <thead class="bg-brand-50/60 text-left text-brand-600"><tr><th class="px-3 py-2">Metode</th><th class="px-3 py-2">Cara kerja</th><th class="px-3 py-2">Catatan</th></tr></thead>
        <tbody class="divide-y divide-brand-50">
            <tr><td class="px-3 py-2"><b>Schoorl</b></td><td class="px-3 py-2">rumus tetap (LD+22)²/100</td><td class="px-3 py-2">baseline wajib; bias non-Eropa</td></tr>
            <tr><td class="px-3 py-2"><b>Log-log</b></td><td class="px-3 py-2">ln(BB)=a+b·ln(LD)</td><td class="px-3 py-2">jujur biologis (b≈2,5–3)</td></tr>
            <tr><td class="px-3 py-2"><b>Regresi linear</b></td><td class="px-3 py-2">kombinasi linear fitur</td><td class="px-3 py-2">transparan; terbantu fitur rekayasa</td></tr>
            <tr><td class="px-3 py-2"><b>Random Forest (RF)</b></td><td class="px-3 py-2">gabungan banyak "pohon keputusan"</td><td class="px-3 py-2">non-linear; <b>tak bisa ekstrapolasi</b></td></tr>
            <tr><td class="px-3 py-2"><b>XGBoost</b></td><td class="px-3 py-2">pohon bertahap (boosting)</td><td class="px-3 py-2">sering paling akurat; lemah ekstrapolasi</td></tr>
            <tr><td class="px-3 py-2"><b>Hibrida</b></td><td class="px-3 py-2">allometrik (basis) + ML (koreksi)</td><td class="px-3 py-2">gabungan kekuatan; kandidat novelty</td></tr>
        </tbody>
    </table>
    <p class="text-sm text-brand-600"><b>Ekstrapolasi</b> = menebak di luar rentang data latih. Model pohon tak bisa
    (lihat bab 13). Hasil leaderboard kita: XGBoost ~7,7% · Hibrida ~7,9% · RF ~8,0% · Linear ~8,3% · Schoorl ~14,4% MAPE.</p>
</section>

{{-- 10 --}}
<section id="metrik" class="{{ $box }}">
    <h2 class="{{ $h2 }}">10. Metrik Akurasi — Singkatan & Definisi</h2>
    <p>Angka untuk menilai seberapa bagus model. Dihitung pada data uji.</p>
    <table class="w-full text-sm border border-brand-100 rounded-lg overflow-hidden">
        <thead class="bg-brand-50/60 text-left text-brand-600"><tr><th class="px-3 py-2">Singkatan</th><th class="px-3 py-2">Kepanjangan</th><th class="px-3 py-2">Definisi sederhana</th></tr></thead>
        <tbody class="divide-y divide-brand-50">
            <tr><td class="px-3 py-2 font-semibold">MAPE</td><td class="px-3 py-2">Mean Absolute Percentage Error<br><span class="text-xs text-brand-500">(Rata-rata Galat Persentase Absolut)</span></td><td class="px-3 py-2">Rata-rata meleset berapa <b>persen</b>. MAPE 8% = tebakan rata-rata meleset ~8% dari bobot asli. <b>Metrik utama</b>, makin kecil makin baik.</td></tr>
            <tr><td class="px-3 py-2 font-semibold">MAE</td><td class="px-3 py-2">Mean Absolute Error<br><span class="text-xs text-brand-500">(Rata-rata Galat Absolut)</span></td><td class="px-3 py-2">Rata-rata meleset berapa <b>kg</b>. MAE 40 = rata-rata meleset 40 kg.</td></tr>
            <tr><td class="px-3 py-2 font-semibold">RMSE</td><td class="px-3 py-2">Root Mean Squared Error<br><span class="text-xs text-brand-500">(Akar Rata-rata Galat Kuadrat)</span></td><td class="px-3 py-2">Mirip MAE tapi <b>menghukum error besar lebih keras</b> — peka pada tebakan yang meleset jauh.</td></tr>
            <tr><td class="px-3 py-2 font-semibold">R²</td><td class="px-3 py-2">R-squared / Koefisien Determinasi</td><td class="px-3 py-2">Seberapa besar variasi bobot yang bisa <b>dijelaskan</b> model, skala 0–1. R²=0,9 = menjelaskan 90%. Negatif = lebih buruk dari menebak rata-rata.</td></tr>
            <tr><td class="px-3 py-2 font-semibold">Bias (ME)</td><td class="px-3 py-2">Mean Error (Galat Rerata)</td><td class="px-3 py-2">Kecenderungan: + = sering <b>kelebihan</b> menebak, − = <b>kekurangan</b>. Ideal ~0.</td></tr>
            <tr><td class="px-3 py-2 font-semibold">Coverage</td><td class="px-3 py-2">Cakupan Interval</td><td class="px-3 py-2">% bobot asli yang masuk rentang prediksi (p10–p90). Idealnya ~80%.</td></tr>
        </tbody>
    </table>
    <p class="text-sm text-brand-600"><b>p10–p90</b> = rentang prediksi: kemungkinan besar (~80%) bobot asli ada di
    antara nilai p10 (batas bawah) dan p90 (batas atas).</p>
</section>

{{-- 11 --}}
<section id="validasi" class="{{ $box }}">
    <h2 class="{{ $h2 }}">11. Validasi: Latih/Uji, CV, Overfitting</h2>
    <ul class="list-disc pl-5 space-y-1">
        <li><b>Pisah latih/uji</b>: sebagian data untuk belajar, sebagian disembunyikan untuk ujian — agar tahu performa sebenarnya.</li>
        <li><b>CV (Cross-Validation / Validasi Silang)</b>: data dibagi beberapa bagian (mis. 5), bergantian jadi
        penguji, lalu hasilnya dirata-rata — penilaian lebih stabil.</li>
        <li><b>Grouped CV</b>: penyekatan per peternak/dataset agar model tak "mengintip" — mencegah <b>kebocoran data (data leakage)</b>.</li>
        <li><b>Overfitting (terlalu pas)</b>: model menghafal data latih → bagus saat latihan, jeblok di data baru.</li>
        <li><b>Underfitting (terlalu sederhana)</b>: model terlalu kaku → buruk di latih maupun uji.</li>
    </ul>
</section>

{{-- 12 --}}
<section id="skenario" class="{{ $box }}">
    <h2 class="{{ $h2 }}">12. Skenario Data & Baseline</h2>
    <p>Tiga skenario sumber data: <b>B</b> nyata→nyata (angka utama), <b>A</b> sintetis→nyata (validasi data sintetis),
    <b>C</b> gabungan→nyata. <b>Aturan emas</b>: akurasi final hanya pada data nyata. <b>Baseline</b> (pembanding
    sederhana = Schoorl) wajib — kalau model ML tak mengalahkannya, belum berguna.</p>
</section>

{{-- 13 --}}
<section id="kasus" class="{{ $box }}">
    <h2 class="{{ $h2 }}">13. Studi Kasus: Sapi Lokal vs Sapi Besar</h2>
    <p>Model kita (dilatih sapi besar 300–800 kg) diuji ke data jurnal Indonesia:</p>
    <table class="w-full text-sm border border-brand-100 rounded-lg overflow-hidden">
        <thead class="bg-brand-50/60 text-left text-brand-600"><tr><th class="px-3 py-2">Sapi</th><th class="px-3 py-2">LD</th><th class="px-3 py-2">Jurnal</th><th class="px-3 py-2">Model</th><th class="px-3 py-2">Error</th></tr></thead>
        <tbody class="divide-y divide-brand-50">
            <tr class="bg-red-50/40"><td class="px-3 py-2">Bali jantan</td><td class="px-3 py-2">140,8</td><td class="px-3 py-2">205,9</td><td class="px-3 py-2">425,3</td><td class="px-3 py-2 text-red-600">+107%</td></tr>
            <tr class="bg-red-50/40"><td class="px-3 py-2">Bali betina</td><td class="px-3 py-2">129,2</td><td class="px-3 py-2">180,3</td><td class="px-3 py-2">425,3</td><td class="px-3 py-2 text-red-600">+136%</td></tr>
            <tr><td class="px-3 py-2">Simbal jantan</td><td class="px-3 py-2">160,4</td><td class="px-3 py-2">365,1</td><td class="px-3 py-2">344,2</td><td class="px-3 py-2 text-green-600">−6%</td></tr>
            <tr><td class="px-3 py-2">Simbal betina</td><td class="px-3 py-2">157,3</td><td class="px-3 py-2">340,6</td><td class="px-3 py-2">362,1</td><td class="px-3 py-2 text-green-600">+6%</td></tr>
        </tbody>
    </table>
    <p>Simbal (besar, mirip data latih) akurat; Bali (lokal kecil, di luar rentang) meleset 2×. Kedua Bali = angka identik
    425,3 → bukti model pohon <b>tak bisa ekstrapolasi</b>. Solusi: <b>rekalibrasi dengan data sapi lokal</b>.</p>
</section>

{{-- 14 --}}
<section id="sistem" class="{{ $box }}">
    <h2 class="{{ $h2 }}">14. Arsitektur Sistem</h2>
    <code class="{{ $rumus }}">Pengguna → Nginx (HTTPS) → Laravel (web+API) → FastAPI/Python (ML) → PostgreSQL</code>
    <ul class="list-disc pl-5 space-y-1">
        <li><b>Laravel</b> (PHP): halaman, login, simpan data & eksperimen.</li>
        <li><b>API (Application Programming Interface)</b>: cara dua program bicara — Laravel memanggil API ML.</li>
        <li><b>FastAPI (Python)</b>: melatih model & memberi estimasi (internal-only).</li>
        <li><b>PostgreSQL</b>: basis data. <b>Redis</b>: cache. <b>Nginx</b>: pintu publik + TLS.</li>
    </ul>
    <p>Alur: impor data → latih → bandingkan di leaderboard → <b>promosikan</b> model terbaik → peternak dapat estimasi.</p>
    <p class="text-sm text-brand-600"><b>RBAC</b> (Role-Based Access Control / kontrol akses berbasis peran) & <b>ABAC</b>
    (Attribute-Based Access Control / berbasis atribut) mengatur siapa boleh apa; <b>audit trail</b> mencatat aktivitas.</p>
</section>

{{-- 15 --}}
<section id="improve" class="{{ $box }}">
    <h2 class="{{ $h2 }}">15. Cara Meningkatkan Model</h2>
    <ol class="list-decimal pl-5 space-y-1">
        <li><b>Data lokal beragam</b> (Bali/PO/Krui per ekor) — gain terbesar.</li>
        <li><b>Fitur allometrik</b> (LD²·PB) + <b>target log</b> — murah.</li>
        <li><b>Model hibrida</b> — atasi ekstrapolasi (novelty artikel).</li>
        <li><b>Tuning</b> + <b>repeated CV</b> dgn interval kepercayaan.</li>
        <li><b>Quantile regression</b> untuk interval terkalibrasi.</li>
    </ol>
    <p class="text-sm text-brand-600">Detail: <code>docs/PENINGKATAN_METODE_MODUL1.md</code> di repo.</p>
</section>

{{-- 16 --}}
<section id="glosarium" class="{{ $box }}">
    <h2 class="{{ $h2 }}">16. Glosarium Singkatan</h2>
    <dl class="grid sm:grid-cols-2 gap-x-6 gap-y-2 text-sm">
        <div><dt class="font-semibold text-brand-800">AI</dt><dd class="text-brand-600">Artificial Intelligence — kecerdasan buatan.</dd></div>
        <div><dt class="font-semibold text-brand-800">ML</dt><dd class="text-brand-600">Machine Learning — pembelajaran mesin.</dd></div>
        <div><dt class="font-semibold text-brand-800">BI</dt><dd class="text-brand-600">Business Intelligence — analitik bisnis.</dd></div>
        <div><dt class="font-semibold text-brand-800">MAPE</dt><dd class="text-brand-600">Mean Absolute Percentage Error — rata-rata galat persen.</dd></div>
        <div><dt class="font-semibold text-brand-800">MAE</dt><dd class="text-brand-600">Mean Absolute Error — rata-rata galat (kg).</dd></div>
        <div><dt class="font-semibold text-brand-800">RMSE</dt><dd class="text-brand-600">Root Mean Squared Error — akar rata-rata galat kuadrat.</dd></div>
        <div><dt class="font-semibold text-brand-800">R²</dt><dd class="text-brand-600">R-squared — koefisien determinasi (0–1).</dd></div>
        <div><dt class="font-semibold text-brand-800">CV</dt><dd class="text-brand-600">Cross-Validation — validasi silang.</dd></div>
        <div><dt class="font-semibold text-brand-800">RF</dt><dd class="text-brand-600">Random Forest — ensembel pohon keputusan.</dd></div>
        <div><dt class="font-semibold text-brand-800">XGBoost</dt><dd class="text-brand-600">eXtreme Gradient Boosting.</dd></div>
        <div><dt class="font-semibold text-brand-800">LD / PB / TG</dt><dd class="text-brand-600">Lingkar Dada / Panjang Badan / Tinggi Gumba.</dd></div>
        <div><dt class="font-semibold text-brand-800">BB</dt><dd class="text-brand-600">Bobot Badan.</dd></div>
        <div><dt class="font-semibold text-brand-800">API</dt><dd class="text-brand-600">Application Programming Interface.</dd></div>
        <div><dt class="font-semibold text-brand-800">RBAC / ABAC</dt><dd class="text-brand-600">kontrol akses berbasis peran / atribut.</dd></div>
        <div><dt class="font-semibold text-brand-800">TLS</dt><dd class="text-brand-600">Transport Layer Security — enkripsi HTTPS.</dd></div>
        <div><dt class="font-semibold text-brand-800">p10 / p90</dt><dd class="text-brand-600">batas bawah/atas rentang prediksi.</dd></div>
    </dl>
</section>

<p class="text-center text-sm text-brand-500 py-4">Selesai — praktik di Data Latih → Latih Model → Leaderboard. <a href="#data-itu" class="text-brand-700 underline">↑ ke atas</a></p>
@endsection
