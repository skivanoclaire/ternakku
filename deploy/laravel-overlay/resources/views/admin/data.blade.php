@extends('layouts.admin', ['title' => 'Data Latih'])
@section('heading', 'Kelola Data Latih')

@section('page')
<p class="text-sm text-brand-600/80">
    Sumber ground truth untuk pelatihan diimpor di sini oleh researcher (dataset sapi nyata Indonesia/publik,
    berisi ukuran tubuh <b>dan</b> bobot timbang). Peternak tidak menyumbang bobot.
</p>

<div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
    @foreach ([
        ['Total pengukuran', $stats['total'], '📏'],
        ['Data publik (latih)', $stats['public'], '📚'],
        ['Data peternak', $stats['farmer'], '👨‍🌾'],
        ['Punya bobot (ground truth)', $stats['ground_truth'], '⚖️'],
    ] as $c)
        <div class="bg-white rounded-2xl p-5 border border-brand-100 shadow-sm">
            <div class="text-2xl">{{ $c[2] }}</div>
            <p class="text-3xl font-extrabold text-brand-800 mt-2">{{ number_format($c[1]) }}</p>
            <p class="text-sm text-brand-500">{{ $c[0] }}</p>
        </div>
    @endforeach
</div>

@if (!empty($stats['per_dataset']))
<div class="bg-white rounded-2xl border border-brand-100 shadow-sm p-5">
    <h2 class="font-bold text-brand-800 mb-3">Komposisi data latih per sumber</h2>
    <div class="flex flex-wrap gap-2">
        @foreach ($stats['per_dataset'] as $ds => $n)
            <span class="px-3 py-1.5 rounded-lg bg-brand-50 border border-brand-100 text-sm text-brand-700"><b>{{ $ds ?: '—' }}</b>: {{ number_format($n) }} baris</span>
        @endforeach
    </div>
</div>
@endif

<div class="bg-white rounded-2xl border border-brand-100 shadow-sm p-5 max-w-2xl">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="font-bold text-brand-800">Data Sintetis (bootstrap & validasi)</h2>
            <p class="text-xs text-brand-500">Bangkitkan data sintetis berlabel untuk skenario A (sintetis→nyata) & C (gabungan).</p>
        </div>
        <form method="POST" action="{{ route('admin.data.sintetis') }}">
            @csrf
            <input type="hidden" name="n" value="800">
            <button class="px-4 py-2 rounded-lg bg-brand-600 text-white text-sm font-semibold hover:bg-brand-700 transition">Buat 800 baris</button>
        </form>
    </div>
</div>

<div class="bg-white rounded-2xl border border-brand-100 shadow-sm p-6 max-w-2xl">
    <h2 class="font-bold text-brand-800 mb-1">Impor Dataset (CSV)</h2>
    <p class="text-xs text-brand-500 mb-4">
        Unggah CSV berisi ukuran tubuh + bobot. Sebutkan nama kolom sesuai header CSV-mu.
        Baris tanpa lingkar dada / bobot valid akan dilewati.
    </p>
    <form method="POST" action="{{ route('admin.data.import') }}" enctype="multipart/form-data" class="space-y-4">
        @csrf
        <div>
            <label class="block text-sm font-medium mb-1">File CSV</label>
            <input type="file" name="csv" accept=".csv,.txt" required
                   class="w-full text-sm rounded-xl border border-brand-200 bg-brand-50/50 px-3 py-2.5">
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Nama dataset (jadi penanda sumber)</label>
            <input name="dataset_name" value="{{ old('dataset_name') }}" required placeholder="mis. sapi_bali_2024"
                   class="w-full rounded-xl border-brand-200 focus:ring-brand-500 focus:border-brand-500 bg-brand-50/50 px-4 py-2.5">
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-sm font-medium mb-1">Kolom lingkar dada *</label>
                <input name="col_ld" value="{{ old('col_ld','lingkar_dada_cm') }}" required class="w-full rounded-xl border-brand-200 bg-brand-50/50 px-4 py-2.5">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Kolom bobot timbang *</label>
                <input name="col_bobot" value="{{ old('col_bobot','bobot_timbang_kg') }}" required class="w-full rounded-xl border-brand-200 bg-brand-50/50 px-4 py-2.5">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Kolom panjang badan</label>
                <input name="col_pb" value="{{ old('col_pb','panjang_badan_cm') }}" class="w-full rounded-xl border-brand-200 bg-brand-50/50 px-4 py-2.5">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Kolom tinggi gumba</label>
                <input name="col_gumba" value="{{ old('col_gumba','tinggi_gumba_cm') }}" class="w-full rounded-xl border-brand-200 bg-brand-50/50 px-4 py-2.5">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Kolom ras (opsional)</label>
                <input name="col_ras" value="{{ old('col_ras','ras') }}" class="w-full rounded-xl border-brand-200 bg-brand-50/50 px-4 py-2.5">
            </div>
        </div>
        <button class="w-full py-3 rounded-xl bg-brand-600 text-white font-semibold hover:bg-brand-700 transition">Impor</button>
    </form>
</div>
@endsection
