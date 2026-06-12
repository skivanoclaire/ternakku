@extends('layouts.admin', ['title' => 'Latih Model'])
@section('heading', 'Latih Model (Modul 1)')

@section('page')
<div class="max-w-2xl">
    <p class="text-sm text-brand-600/80 mb-6">
        Pilih metode, fitur, dan mode evaluasi, lalu jalankan. Backend (FastAPI) melatih &
        mengevaluasi pada data nyata; hasilnya tersimpan sebagai satu eksperimen dan muncul di leaderboard.
    </p>

    <form method="POST" action="{{ route('admin.latih.store') }}"
          x-data="{ loading:false }" @submit="loading=true"
          class="bg-white rounded-2xl border border-brand-100 shadow-sm p-6 space-y-6">
        @csrf

        <div>
            <label class="block text-sm font-semibold text-brand-800 mb-2">Metode</label>
            <select name="method" class="w-full rounded-xl border-brand-200 focus:ring-brand-500 focus:border-brand-500 bg-brand-50/50 px-4 py-2.5">
                @foreach ($methods as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </select>
            <p class="text-xs text-brand-500 mt-1">Baseline klasik (Schoorl) wajib jadi pembanding — model lain harus mengalahkannya.</p>
        </div>

        <div>
            <label class="block text-sm font-semibold text-brand-800 mb-2">Fitur</label>
            <div class="space-y-2">
                @foreach ($allFeatures as $key => $label)
                    <label class="flex items-center gap-2 text-sm text-brand-700">
                        <input type="checkbox" name="features[]" value="{{ $key }}"
                               {{ $key === 'lingkar_dada_cm' ? 'checked' : '' }}
                               class="rounded border-brand-300 text-brand-600 focus:ring-brand-500">
                        {{ $label }}
                    </label>
                @endforeach
            </div>
            <p class="text-xs text-brand-500 mt-1">Schoorl & log-log hanya memakai lingkar dada.</p>
        </div>

        <div>
            <label class="block text-sm font-semibold text-brand-800 mb-2">Skenario sumber data</label>
            <select name="scenario" class="w-full rounded-xl border-brand-200 focus:ring-brand-500 focus:border-brand-500 bg-brand-50/50 px-4 py-2.5">
                <option value="B">B — nyata → nyata (angka utama)</option>
                <option value="A">A — sintetis → nyata (validasi data sintetis)</option>
                <option value="C">C — gabungan → nyata</option>
            </select>
            <p class="text-xs text-brand-500 mt-1">A &amp; C butuh data sintetis (buat dulu di menu Data Latih). Mode evaluasi di bawah berlaku untuk skenario B.</p>
        </div>

        <div>
            <label class="block text-sm font-semibold text-brand-800 mb-2">Mode evaluasi</label>
            <div class="space-y-2 text-sm text-brand-700">
                <label class="flex items-center gap-2">
                    <input type="radio" name="eval_mode" value="acak" checked class="text-brand-600 focus:ring-brand-500">
                    5-fold acak (performa pada distribusi sama)
                </label>
                <label class="flex items-center gap-2">
                    <input type="radio" name="eval_mode" value="lintas" class="text-brand-600 focus:ring-brand-500">
                    Lintas-dataset (grouped, uji generalisasi antar-sumber)
                </label>
            </div>
        </div>

        <button type="submit" :disabled="loading"
                class="w-full py-3 rounded-xl bg-brand-600 text-white font-semibold hover:bg-brand-700 transition disabled:opacity-60">
            <span x-show="!loading">Latih &amp; evaluasi</span>
            <span x-show="loading" x-cloak>Melatih… (mohon tunggu)</span>
        </button>
    </form>
</div>
@endsection
