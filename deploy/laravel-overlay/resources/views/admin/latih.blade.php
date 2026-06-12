@extends('layouts.admin', ['title' => 'Latih Model'])
@section('heading', 'Latih Model (Modul 1)')

@section('page')
<x-help title="Apa yang terjadi di halaman ini?" :open="true">
    <p>Di sini Anda <b>melatih satu model</b> untuk menebak bobot sapi dari ukuran tubuhnya. Web tidak menghitung sendiri —
    saat tombol <b>Latih</b> ditekan, Laravel mengirim pilihan Anda ke service Python (FastAPI) yang melatih & mengevaluasi,
    lalu hasilnya disimpan sebagai satu <b>eksperimen</b> dan muncul di leaderboard untuk diadu dengan eksperimen lain.</p>
    <p>Empat pilihan di bawah menentukan model: <b>metode</b> (cara menghitung), <b>fitur</b> (informasi yang dipakai),
    <b>skenario</b> (sumber data latih), dan <b>mode evaluasi</b> (cara mengukur kejujuran akurasi).</p>
</x-help>

<form method="POST" action="{{ route('admin.latih.store') }}"
      x-data="{ loading:false, method:'hybrid' }" @submit="loading=true"
      class="bg-white rounded-2xl border border-brand-100 shadow-sm p-6 space-y-6 max-w-2xl">
    @csrf

    {{-- METODE --}}
    <div>
        <label class="block text-sm font-semibold text-brand-800 mb-2">1. Metode</label>
        <select name="method" x-model="method" class="w-full rounded-xl border-brand-200 focus:ring-brand-500 focus:border-brand-500 bg-brand-50/50 px-4 py-2.5">
            @foreach ($methods as $key => $info)
                <option value="{{ $key }}" @selected($key==='hybrid')>{{ $info[0] }}</option>
            @endforeach
        </select>
        @foreach ($methods as $key => $info)
            <p x-show="method==='{{ $key }}'" x-cloak class="text-xs text-brand-600 bg-brand-50 border border-brand-100 rounded-lg px-3 py-2 mt-2 leading-relaxed">{{ $info[1] }}</p>
        @endforeach
    </div>

    {{-- FITUR --}}
    <div>
        <label class="block text-sm font-semibold text-brand-800 mb-2">2. Fitur (informasi yang dipakai model)</label>
        <div class="space-y-2">
            @foreach ($allFeatures as $key => $info)
                <label class="flex items-start gap-2 text-sm text-brand-700 p-2 rounded-lg hover:bg-brand-50">
                    <input type="checkbox" name="features[]" value="{{ $key }}"
                           {{ $key === 'lingkar_dada_cm' ? 'checked' : '' }}
                           class="mt-0.5 rounded border-brand-300 text-brand-600 focus:ring-brand-500">
                    <span><b>{{ $info[0] }}</b> — <span class="text-brand-500">{{ $info[1] }}</span></span>
                </label>
            @endforeach
        </div>
        <x-help title="Mengapa ada fitur 'rekayasa' (LD², LD²·PB)?">
            <p>Model tidak otomatis paham bahwa <b>bobot ∝ volume ∝ LD² × PB</b>. Dengan memberi fitur turunan ini secara
            eksplisit, model linear pun bisa menangkap hukum allometrik — sering melonjakkan akurasi tanpa mengganti algoritma.
            Ini contoh nyata "rekayasa fitur lebih penting daripada kerumitan model".</p>
            <p>Catatan: <b>Schoorl</b> & <b>log-log</b> hanya memakai lingkar dada (fitur lain diabaikan).</p>
        </x-help>
    </div>

    {{-- TARGET LOG --}}
    <div>
        <label class="flex items-center gap-2 text-sm font-medium text-brand-700">
            <input type="checkbox" name="log_target" value="1" class="rounded border-brand-300 text-brand-600 focus:ring-brand-500">
            3. Modelkan <b>ln(bobot)</b> (target log)
        </label>
        <x-help title="Apa efek target log?">
            <p>Galat bobot bersifat <b>multiplikatif</b>: sapi besar wajar meleset lebih banyak dalam kg, tapi serupa dalam persen.
            Memodelkan ln(bobot) lalu di-exp-kan kembali menstabilkan ini dan biasanya menurunkan MAPE. Tidak berlaku untuk
            Schoorl/log-log (sudah menangani skala sendiri).</p>
        </x-help>
    </div>

    {{-- SKENARIO --}}
    <div>
        <label class="block text-sm font-semibold text-brand-800 mb-2">4. Skenario sumber data</label>
        <select name="scenario" class="w-full rounded-xl border-brand-200 focus:ring-brand-500 focus:border-brand-500 bg-brand-50/50 px-4 py-2.5">
            <option value="B">B — nyata → nyata (angka utama)</option>
            <option value="A">A — sintetis → nyata (validasi data sintetis)</option>
            <option value="C">C — gabungan → nyata</option>
        </select>
        <x-help title="Beda A / B / C?">
            <p><b>B</b>: latih & uji pada data nyata — ini <b>angka akurasi sebenarnya</b> untuk artikel.</p>
            <p><b>A</b>: latih pada data sintetis, uji pada nyata — menjawab "apakah data sintetis kami valid?".</p>
            <p><b>C</b>: latih gabungan sintetis+nyata, uji nyata — apakah sintetis membantu atau mengganggu?</p>
            <p>A & C butuh data sintetis (buat dulu di menu <b>Data Latih</b>).</p>
        </x-help>
    </div>

    {{-- EVAL MODE --}}
    <div>
        <label class="block text-sm font-semibold text-brand-800 mb-2">5. Mode evaluasi (untuk skenario B)</label>
        <div class="space-y-2 text-sm text-brand-700">
            <label class="flex items-center gap-2"><input type="radio" name="eval_mode" value="acak" checked class="text-brand-600 focus:ring-brand-500"> 5-fold acak (performa pada distribusi sama)</label>
            <label class="flex items-center gap-2"><input type="radio" name="eval_mode" value="lintas" class="text-brand-600 focus:ring-brand-500"> Lintas-dataset (grouped — uji generalisasi antar-sumber)</label>
        </div>
        <x-help title="Kenapa mode evaluasi penting?">
            <p><b>5-fold acak</b>: data dibagi 5, dilatih di 4 bagian, diuji di 1, bergantian. Mengukur performa pada data
            sejenis. <b>Lintas-dataset</b>: melatih di satu sumber, menguji di sumber lain (grouped) — mencegah <i>kebocoran</i>
            (model menghafal peternak/dataset, bukan belajar hubungan ukuran→bobot). Lintas lebih ketat & lebih jujur untuk klaim generalisasi.</p>
        </x-help>
    </div>

    <button type="submit" :disabled="loading"
            class="w-full py-3 rounded-xl bg-brand-600 text-white font-semibold hover:bg-brand-700 transition disabled:opacity-60">
        <span x-show="!loading">Latih &amp; evaluasi</span>
        <span x-show="loading" x-cloak>Melatih… (mohon tunggu)</span>
    </button>
</form>
@endsection
