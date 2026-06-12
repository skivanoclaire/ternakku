@extends('layouts.admin', ['title' => 'Modul Belajar'])
@section('heading', 'Modul Belajar — Data Science TernakKu')

@php
    // daftar bab untuk navigasi lompat
    $bab = [
        ['masalah',   '1. Masalah yang Dipecahkan'],
        ['konsep',    '2. Konsep Dasar Machine Learning'],
        ['data',      '3. Data: Bahan Bakar Model'],
        ['fitur',     '4. Fitur & Hukum Allometrik'],
        ['metode',    '5. Metode/Model & Kelebihan-Kekurangannya'],
        ['evaluasi',  '6. Mengukur Akurasi (Metrik & Validasi)'],
        ['skenario',  '7. Skenario Data & Baseline'],
        ['kasus',     '8. Studi Kasus: Sapi Lokal vs Sapi Besar'],
        ['sistem',    '9. Arsitektur Sistem (Cara Platform Bekerja)'],
        ['improve',   '10. Cara Meningkatkan Model'],
        ['glosarium', '11. Glosarium'],
    ];
@endphp

@section('page')
<p class="text-brand-600/80">
    Modul ini menjelaskan <b>seluruh data science di balik TernakKu</b> — dari konsep paling dasar sampai
    implementasi yang sekarang berjalan — memakai angka & contoh nyata dari sistem kita. Cocok dibaca berurutan,
    atau lompat ke bab yang dibutuhkan.
</p>

{{-- Navigasi lompat --}}
<div class="bg-white rounded-2xl border border-brand-100 shadow-sm p-4 sticky top-4 z-20">
    <p class="text-xs font-semibold text-brand-500 mb-2">DAFTAR ISI</p>
    <div class="flex flex-wrap gap-2">
        @foreach ($bab as [$id, $judul])
            <a href="#{{ $id }}" class="text-xs px-3 py-1.5 rounded-lg bg-brand-50 border border-brand-100 text-brand-700 hover:bg-brand-100 transition">{{ $judul }}</a>
        @endforeach
    </div>
</div>

@php
    // gaya seragam blok
    $box = 'bg-white rounded-2xl border border-brand-100 shadow-sm p-6 space-y-3 scroll-mt-24';
    $h2  = 'text-xl font-bold text-brand-800';
    $rumus = 'block bg-brand-900 text-brand-50 rounded-xl px-4 py-3 font-mono text-sm my-2';
@endphp

{{-- 1 --}}
<section id="masalah" class="{{ $box }}">
    <h2 class="{{ $h2 }}">1. Masalah yang Dipecahkan</h2>
    <p>Peternak rakyat sering tak punya timbangan ternak. Akibatnya saat menjual, mereka tak tahu bobot pasti
    sapinya → tengkulak menentukan harga sepihak. Ini disebut <b>asimetri informasi</b>: satu pihak tahu lebih banyak.</p>
    <p>TernakKu menutup celah itu: dari <b>ukuran tubuh</b> yang mudah diukur dengan pita (lingkar dada, panjang badan,
    tinggi gumba), sistem <b>memperkirakan bobot</b> tanpa timbangan. Peternak jadi punya angka untuk menawar.</p>
    <p class="text-sm text-brand-600">Inti seluruh proyek = membuat tebakan bobot itu <b>seakurat mungkin</b> dan
    <b>jujur</b> (memberi rentang, bukan satu angka palsu-pasti).</p>
</section>

{{-- 2 --}}
<section id="konsep" class="{{ $box }}">
    <h2 class="{{ $h2 }}">2. Konsep Dasar Machine Learning</h2>
    <p>Bayangkan kita ingin menebak bobot dari lingkar dada. Machine Learning (ML) = <b>komputer belajar pola dari
    contoh</b>, bukan diberi rumus jadi. Istilah kuncinya:</p>
    <ul class="list-disc pl-5 space-y-1">
        <li><b>Fitur</b> (input): informasi yang dipakai menebak — lingkar dada, panjang badan, tinggi gumba.</li>
        <li><b>Target</b> (output): yang ingin ditebak — bobot (kg).</li>
        <li><b>Ground truth</b>: bobot asli hasil timbang — "kunci jawaban" untuk belajar & menguji.</li>
        <li><b>Melatih (train)</b>: model melihat banyak pasangan (ukuran → bobot asli) dan menyesuaikan dirinya.</li>
        <li><b>Menguji (test)</b>: model dites pada data yang <b>belum pernah dilihat</b> — agar tahu performa sebenarnya.</li>
    </ul>
    <x-help title="Analogi sederhana">
        <p>Seperti murid belajar dari <i>soal + kunci jawaban</i> (data latih), lalu diuji dengan <i>soal baru</i>
        (data uji). Kalau murid cuma menghafal soal lama (bukan paham pola), nilainya tinggi saat latihan tapi jeblok
        di ujian baru — itu disebut <b>overfitting</b>.</p>
    </x-help>
</section>

{{-- 3 --}}
<section id="data" class="{{ $box }}">
    <h2 class="{{ $h2 }}">3. Data: Bahan Bakar Model</h2>
    <p>Model hanya sebaik datanya. TernakKu memakai <b>tiga sumber</b>, ditandai jelas:</p>
    <table class="w-full text-sm border border-brand-100 rounded-lg overflow-hidden">
        <thead class="bg-brand-50/60 text-left text-brand-600"><tr><th class="px-3 py-2">Sumber</th><th class="px-3 py-2">Asal</th><th class="px-3 py-2">Peran</th></tr></thead>
        <tbody class="divide-y divide-brand-50">
            <tr><td class="px-3 py-2"><b>synthetic</b></td><td class="px-3 py-2">dibangkitkan rumus + noise</td><td class="px-3 py-2">bootstrap & validasi</td></tr>
            <tr><td class="px-3 py-2"><b>public</b></td><td class="px-3 py-2">dataset jurnal/repositori</td><td class="px-3 py-2">data nyata: latih & uji</td></tr>
            <tr><td class="px-3 py-2"><b>farmer</b></td><td class="px-3 py-2">input peternak (tanpa bobot)</td><td class="px-3 py-2">konsumen estimasi</td></tr>
        </tbody>
    </table>
    <p class="font-semibold text-brand-800 mt-3">Pelajaran terpenting: kualitas data &gt; kerumitan model.</p>
    <p>Contoh nyata dari data kita: dataset <b>Hereford</b> punya korelasi lingkar dada–bobot cuma <b>0,30</b>
    (sapi terlalu seragam) — sehingga model apa pun sulit akurat di sana. Dataset <b>Horqin</b> korelasinya <b>0,91</b>
    (sinyal kuat). Ada <b>plafon data</b>: jika sinyal di data lemah, mengganti algoritma tak banyak menolong — yang
    menolong adalah <b>data yang lebih baik & beragam</b>.</p>
</section>

{{-- 4 --}}
<section id="fitur" class="{{ $box }}">
    <h2 class="{{ $h2 }}">4. Fitur & Hukum Allometrik</h2>
    <p>Hubungan biologis: bobot sebanding dengan <b>volume</b> tubuh, dan volume ≈ luas penampang dada × panjang badan:</p>
    <code class="{{ $rumus }}">Bobot ∝ Volume ∝ (lingkar dada)² × panjang badan</code>
    <p>Komputer tidak otomatis tahu hukum ini. Maka kita bantu dengan <b>fitur rekayasa</b> — menghitungnya lebih dulu:</p>
    <ul class="list-disc pl-5 space-y-1">
        <li><b>LD²</b> — lingkar dada dikuadratkan (luas penampang dada).</li>
        <li><b>LD²·PB</b> — proksi <b>volume</b>; fitur tunggal paling kuat, sering melonjakkan akurasi regresi linear.</li>
        <li><b>PB/LD, TG/LD</b> — rasio bentuk tubuh (gemuk vs ramping).</li>
    </ul>
    <p class="text-sm text-brand-600">Di menu <a href="{{ route('admin.latih') }}" class="text-brand-700 underline">Latih Model</a>,
    fitur-fitur ini bisa dicentang dan dampaknya langsung terlihat di leaderboard.</p>
</section>

{{-- 5 --}}
<section id="metode" class="{{ $box }}">
    <h2 class="{{ $h2 }}">5. Metode/Model & Kelebihan–Kekurangannya</h2>
    <table class="w-full text-sm border border-brand-100 rounded-lg overflow-hidden">
        <thead class="bg-brand-50/60 text-left text-brand-600"><tr><th class="px-3 py-2">Metode</th><th class="px-3 py-2">Cara kerja</th><th class="px-3 py-2">Catatan</th></tr></thead>
        <tbody class="divide-y divide-brand-50">
            <tr><td class="px-3 py-2"><b>Schoorl</b></td><td class="px-3 py-2">rumus tetap (LD+22)²/100</td><td class="px-3 py-2">baseline wajib; bias untuk sapi non-Eropa</td></tr>
            <tr><td class="px-3 py-2"><b>Log-log</b></td><td class="px-3 py-2">ln(BB)=a+b·ln(LD)</td><td class="px-3 py-2">jujur biologis; eksponen b≈2,5–3</td></tr>
            <tr><td class="px-3 py-2"><b>Regresi linear</b></td><td class="px-3 py-2">kombinasi linear fitur</td><td class="px-3 py-2">transparan; terbantu fitur rekayasa</td></tr>
            <tr><td class="px-3 py-2"><b>Random Forest</b></td><td class="px-3 py-2">ensembel banyak pohon keputusan</td><td class="px-3 py-2">tangkap pola non-linear; <b>tak bisa ekstrapolasi</b></td></tr>
            <tr><td class="px-3 py-2"><b>XGBoost</b></td><td class="px-3 py-2">boosting bertahap</td><td class="px-3 py-2">biasanya paling akurat; lemah ekstrapolasi</td></tr>
            <tr><td class="px-3 py-2"><b>Hibrida</b></td><td class="px-3 py-2">allometrik (basis) + ML (koreksi residual)</td><td class="px-3 py-2">gabungan kekuatan; kandidat kontribusi artikel</td></tr>
        </tbody>
    </table>
    <x-help title="Apa itu 'tidak bisa ekstrapolasi'? (penting)">
        <p>Model pohon (RF/XGBoost) hanya bisa menebak di dalam <b>rentang bobot data latih</b>. Untuk sapi jauh lebih
        kecil/besar dari data latih, ia "mendatar" ke satu angka — bukan menebak benar. Itu sebabnya hibrida (yang
        memakai rumus allometrik sebagai basis) lebih aman di luar rentang. Lihat bukti nyatanya di Bab 8.</p>
    </x-help>
    <p class="text-sm text-brand-600">Hasil nyata leaderboard kita (data publik): XGBoost ~7,7% · Hibrida ~7,9% ·
    Random Forest ~8,0% · Linear ~8,3% · Schoorl ~14,4% MAPE. Selisih antar-ML tipis karena plafon data (Bab 3).</p>
</section>

{{-- 6 --}}
<section id="evaluasi" class="{{ $box }}">
    <h2 class="{{ $h2 }}">6. Mengukur Akurasi (Metrik & Validasi)</h2>
    <p><b>Metrik</b> (dihitung pada data uji):</p>
    <ul class="list-disc pl-5 space-y-1">
        <li><b>MAPE</b> (%) — rata-rata error relatif; metrik utama, mudah dijelaskan. Makin kecil makin baik.</li>
        <li><b>MAE</b> (kg) — rata-rata meleset berapa kg.</li>
        <li><b>RMSE</b> (kg) — menghukum error besar lebih keras.</li>
        <li><b>R²</b> (0–1) — proporsi variasi bobot yang dijelaskan model; mendekati 1 = baik.</li>
        <li><b>Bias</b> — kecenderungan over/under-estimate.</li>
        <li><b>Coverage & interval (p10–p90)</b> — kejujuran rentang prediksi.</li>
    </ul>
    <p class="font-semibold text-brand-800">Validasi yang benar = kunci kredibilitas (yang dinilai dosen):</p>
    <ul class="list-disc pl-5 space-y-1">
        <li><b>Pisah latih/uji</b>: model dinilai pada data yang belum dilihat.</li>
        <li><b>Grouped cross-validation</b>: melatih di satu peternak/dataset, menguji di lain — mencegah
        <b>kebocoran</b> (model menghafal peternak, bukan belajar hubungan ukuran→bobot).</li>
        <li><b>Overfitting</b>: akurasi latih tinggi tapi uji jeblok → model menghafal, bukan paham.</li>
        <li>Selisih kecil (7,8% vs 7,9%) belum tentu beda nyata — perlu diuji beberapa kali.</li>
    </ul>
    <p class="text-sm text-brand-600">Semua metrik & grafik ini ada di halaman
    <a href="{{ route('admin.leaderboard') }}" class="text-brand-700 underline">Leaderboard</a> & detail eksperimen.</p>
</section>

{{-- 7 --}}
<section id="skenario" class="{{ $box }}">
    <h2 class="{{ $h2 }}">7. Skenario Data & Baseline</h2>
    <p>Kita membandingkan tiga skenario sumber data (di form Latih):</p>
    <ul class="list-disc pl-5 space-y-1">
        <li><b>B — nyata → nyata</b>: latih & uji data nyata. <b>Angka akurasi utama</b> untuk artikel.</li>
        <li><b>A — sintetis → nyata</b>: menjawab "apakah data sintetis kami valid?".</li>
        <li><b>C — gabungan → nyata</b>: apakah menambah sintetis membantu atau mengganggu?</li>
    </ul>
    <p><b>Aturan emas</b>: akurasi final <b>hanya</b> dihitung pada data nyata (sintetis dibuat sendiri, jadi
    "menghafal" angka kita kalau diuji pada sintetis → menipu). Dan <b>baseline klasik Schoorl wajib</b> jadi pembanding:
    kalau model ML tak mengalahkannya, belum ada gunanya.</p>
</section>

{{-- 8 --}}
<section id="kasus" class="{{ $box }}">
    <h2 class="{{ $h2 }}">8. Studi Kasus: Sapi Lokal vs Sapi Besar</h2>
    <p>Model kita (dilatih pada Hereford+Horqin = sapi besar 300–800 kg) diuji ke data jurnal Indonesia:</p>
    <table class="w-full text-sm border border-brand-100 rounded-lg overflow-hidden">
        <thead class="bg-brand-50/60 text-left text-brand-600"><tr><th class="px-3 py-2">Sapi</th><th class="px-3 py-2">LD</th><th class="px-3 py-2">Bobot jurnal</th><th class="px-3 py-2">Model</th><th class="px-3 py-2">Error</th></tr></thead>
        <tbody class="divide-y divide-brand-50">
            <tr class="bg-red-50/40"><td class="px-3 py-2">Bali jantan</td><td class="px-3 py-2">140,8</td><td class="px-3 py-2">205,9 kg</td><td class="px-3 py-2">425,3 kg</td><td class="px-3 py-2 text-red-600">+107%</td></tr>
            <tr class="bg-red-50/40"><td class="px-3 py-2">Bali betina</td><td class="px-3 py-2">129,2</td><td class="px-3 py-2">180,3 kg</td><td class="px-3 py-2">425,3 kg</td><td class="px-3 py-2 text-red-600">+136%</td></tr>
            <tr><td class="px-3 py-2">Simbal jantan</td><td class="px-3 py-2">160,4</td><td class="px-3 py-2">365,1 kg</td><td class="px-3 py-2">344,2 kg</td><td class="px-3 py-2 text-green-600">−6%</td></tr>
            <tr><td class="px-3 py-2">Simbal betina</td><td class="px-3 py-2">157,3</td><td class="px-3 py-2">340,6 kg</td><td class="px-3 py-2">362,1 kg</td><td class="px-3 py-2 text-green-600">+6%</td></tr>
        </tbody>
    </table>
    <p><b>Pelajaran</b>: Simbal (silangan Simmental, besar — mirip data latih) akurat. Sapi Bali (lokal kecil — di luar
    rentang data latih) meleset 2×. Perhatikan kedua Bali menghasilkan angka identik 425,3 — bukti model pohon
    <b>tak bisa ekstrapolasi</b>. Solusinya: <b>rekalibrasi dengan data sapi lokal</b> — inti celah riset proyek ini.</p>
</section>

{{-- 9 --}}
<section id="sistem" class="{{ $box }}">
    <h2 class="{{ $h2 }}">9. Arsitektur Sistem (Cara Platform Bekerja)</h2>
    <p>Web <b>tidak</b> menghitung ML sendiri. Pembagiannya:</p>
    <code class="{{ $rumus }}">Peternak/Researcher → Nginx (HTTPS) → Laravel (web+API) → FastAPI/Python (ML) → PostgreSQL</code>
    <ul class="list-disc pl-5 space-y-1">
        <li><b>Laravel</b>: halaman, login, peran, simpan data & eksperimen.</li>
        <li><b>FastAPI (Python)</b>: melatih model, menghitung metrik, memberi estimasi. Internal-only.</li>
        <li><b>PostgreSQL</b>: data ternak, pengukuran, eksperimen, audit.</li>
        <li><b>Redis</b>: cache/antrian; <b>Nginx</b>: satu-satunya pintu publik (TLS).</li>
    </ul>
    <p><b>Alur kerja researcher → peternak</b>: impor/generate data → latih model (Laravel memanggil FastAPI) →
    bandingkan di leaderboard → <b>promosikan</b> model terbaik jadi "aktif" → peternak input ukuran → dapat estimasi
    dari model aktif itu.</p>
    <p class="text-sm text-brand-600"><b>Keamanan</b>: RBAC (peran admin/peternak), ABAC (akun nonaktif ditolak,
    peternak hanya lihat ternaknya), dan audit trail (jejak siapa-melakukan-apa) — semua aktif.</p>
</section>

{{-- 10 --}}
<section id="improve" class="{{ $box }}">
    <h2 class="{{ $h2 }}">10. Cara Meningkatkan Model</h2>
    <p>Urutan paling berdampak:</p>
    <ol class="list-decimal pl-5 space-y-1">
        <li><b>Data lokal yang beragam</b> (Bali/PO/Krui per ekor) — gain terbesar; atasi kasus Bab 8.</li>
        <li><b>Fitur allometrik</b> (LD²·PB) + <b>target log</b> — murah, naikkan akurasi.</li>
        <li><b>Model hibrida</b> allometrik+ML — atasi ekstrapolasi (kandidat novelty artikel).</li>
        <li><b>Tuning hyperparameter</b> + <b>repeated CV</b> dengan interval kepercayaan (rigor).</li>
        <li><b>Quantile regression</b> untuk interval p10–p90 yang terkalibrasi.</li>
    </ol>
    <p class="text-sm text-brand-600">Detail lengkap ada di dokumen repo
    <code>docs/PENINGKATAN_METODE_MODUL1.md</code> — sekaligus kerangka untuk artikel/SLR.</p>
</section>

{{-- 11 --}}
<section id="glosarium" class="{{ $box }}">
    <h2 class="{{ $h2 }}">11. Glosarium</h2>
    <dl class="grid sm:grid-cols-2 gap-x-6 gap-y-2 text-sm">
        <div><dt class="font-semibold text-brand-800">Fitur</dt><dd class="text-brand-600">input model (ukuran tubuh).</dd></div>
        <div><dt class="font-semibold text-brand-800">Target</dt><dd class="text-brand-600">yang ditebak (bobot).</dd></div>
        <div><dt class="font-semibold text-brand-800">Ground truth</dt><dd class="text-brand-600">bobot asli hasil timbang.</dd></div>
        <div><dt class="font-semibold text-brand-800">Allometrik</dt><dd class="text-brand-600">hubungan ukuran↔bobot berbasis volume.</dd></div>
        <div><dt class="font-semibold text-brand-800">MAPE</dt><dd class="text-brand-600">rata-rata error persen.</dd></div>
        <div><dt class="font-semibold text-brand-800">R²</dt><dd class="text-brand-600">seberapa baik model menjelaskan data.</dd></div>
        <div><dt class="font-semibold text-brand-800">Overfitting</dt><dd class="text-brand-600">hafal data latih, gagal di data baru.</dd></div>
        <div><dt class="font-semibold text-brand-800">Ekstrapolasi</dt><dd class="text-brand-600">menebak di luar rentang data latih.</dd></div>
        <div><dt class="font-semibold text-brand-800">Cross-validation</dt><dd class="text-brand-600">uji berulang dgn membagi data.</dd></div>
        <div><dt class="font-semibold text-brand-800">Baseline</dt><dd class="text-brand-600">pembanding sederhana (Schoorl).</dd></div>
        <div><dt class="font-semibold text-brand-800">Model aktif</dt><dd class="text-brand-600">model yang melayani estimasi peternak.</dd></div>
        <div><dt class="font-semibold text-brand-800">RBAC/ABAC</dt><dd class="text-brand-600">kontrol akses by peran / by atribut.</dd></div>
    </dl>
</section>

<p class="text-center text-sm text-brand-500 py-4">Selesai — silakan praktik langsung di menu Data Latih → Latih Model → Leaderboard. <a href="#masalah" class="text-brand-700 underline">↑ kembali ke atas</a></p>
@endsection
