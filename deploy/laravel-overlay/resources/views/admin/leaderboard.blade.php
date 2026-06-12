@extends('layouts.admin', ['title' => 'Leaderboard'])
@section('heading', 'Papan Banding Metode')

@section('page')
<x-help title="Apa itu leaderboard & cara membacanya" :open="true">
    <p>Tabel ini mengadu <b>semua eksperimen</b> pada data uji & cara hitung yang <b>sama</b>, sehingga angkanya benar-benar
    sebanding — tidak ada lagi "akurasi saya beda dengan kamu". Diurutkan dari <b>MAPE terkecil</b> (paling akurat di atas).</p>
    <ul class="list-disc pl-5 space-y-1">
        <li>Kolom <b>Skenario</b> (A/B/C) = sumber data latih; bandingkan B (nyata) sebagai penentu utama.</li>
        <li>Baris <b>Schoorl</b> (kuning) = baseline klasik wajib — model lain harus mengalahkannya agar bermakna.</li>
        <li>Badge <b>★ aktif</b> = model yang sedang melayani estimasi peternak.</li>
        <li><b>Penting</b>: selisih kecil (mis. 7,8% vs 7,9%) belum tentu beda nyata — jalankan beberapa kali sebelum mengklaim menang.</li>
    </ul>
    <p>Klik <b>detail</b> untuk metrik lengkap & grafik diagnosa; <b>Ekspor CSV</b> untuk bahan artikel.</p>
</x-help>

<div class="flex items-start justify-between gap-4">
    <p class="text-sm text-brand-600/80">
        Semua eksperimen diuji pada data & cara yang sama, jadi angkanya sebanding. Diurutkan dari MAPE terkecil.
        Baris <b>Schoorl</b> adalah baseline klasik — model lain wajib mengalahkannya.
    </p>
    <a href="{{ route('admin.ekspor') }}" class="shrink-0 px-4 py-2 rounded-lg border border-brand-200 text-brand-700 text-sm font-semibold hover:bg-brand-100 transition">⬇ Ekspor CSV</a>
</div>

<div class="bg-white rounded-2xl border border-brand-100 shadow-sm overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="text-left text-brand-500 bg-brand-50/60">
            @php $thc = 'px-4 py-3 font-medium'; $tip = 'cursor-help border-b border-dotted border-brand-400'; @endphp
            <tr>
                <th class="{{ $thc }}"><span class="{{ $tip }}" title="Peringkat — diurutkan dari MAPE terkecil (paling akurat di atas).">#</span></th>
                <th class="{{ $thc }}"><span class="{{ $tip }}" title="Algoritma yang dipakai (linear, log-log, RF, XGBoost, hibrida, baseline Schoorl).">Metode</span></th>
                <th class="{{ $thc }}"><span class="{{ $tip }}" title="Sumber data: B nyata→nyata (angka utama), A sintetis→nyata, C gabungan→nyata.">Skenario</span></th>
                <th class="{{ $thc }}"><span class="{{ $tip }}" title="Jumlah fitur yang dipakai (ukuran tubuh mentah + fitur rekayasa allometrik).">Fitur</span></th>
                <th class="{{ $thc }}"><span class="{{ $tip }}" title="Cara evaluasi: acak (5-fold) atau lintas-dataset (grouped, anti-kebocoran).">Eval</span></th>
                <th class="{{ $thc }}"><span class="{{ $tip }}" title="Mean Absolute Percentage Error — rata-rata kesalahan persen. Makin kecil makin akurat; metrik utama.">MAPE%</span></th>
                <th class="{{ $thc }}"><span class="{{ $tip }}" title="Mean Absolute Error — rata-rata meleset berapa kg. Konkret.">MAE</span></th>
                <th class="{{ $thc }}"><span class="{{ $tip }}" title="Root Mean Squared Error (kg) — seperti MAE tapi menghukum error besar lebih keras.">RMSE</span></th>
                <th class="{{ $thc }}"><span class="{{ $tip }}" title="Koefisien determinasi (0–1): proporsi variasi bobot yang dijelaskan model. Mendekati 1 = baik.">R²</span></th>
                <th class="{{ $thc }}"><span class="{{ $tip }}" title="Researcher yang menjalankan eksperimen.">Oleh</span></th>
                <th class="{{ $thc }}"><span class="{{ $tip }}" title="Waktu eksperimen dijalankan.">Tanggal</span></th>
                <th class="px-4 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-brand-50">
            @forelse ($experiments as $i => $e)
                <tr class="hover:bg-brand-50/50 {{ $e->method === 'schoorl' ? 'bg-amber-50/40' : '' }}">
                    <td class="px-4 py-3 font-semibold text-brand-700">{{ $i+1 }}</td>
                    <td class="px-4 py-3">
                        {{ $e->method_label ?? $e->method }}
                        @if ($e->is_active)<span class="ml-1 text-xs px-2 py-0.5 rounded-full bg-green-600 text-white">★ aktif</span>@endif
                        @if ($e->method === 'schoorl')<span class="ml-1 text-xs px-2 py-0.5 rounded-full bg-amber-200 text-amber-800">baseline</span>@endif
                    </td>
                    <td class="px-4 py-3"><span class="text-xs px-2 py-0.5 rounded-full bg-brand-100 text-brand-700">{{ $e->scenario ?? 'B' }}</span></td>
                    <td class="px-4 py-3 text-brand-500 text-xs">{{ count($e->features ?? []) }} fitur</td>
                    <td class="px-4 py-3 text-brand-500">{{ $e->eval_mode }}</td>
                    <td class="px-4 py-3 font-bold text-brand-800">{{ $e->mape }}</td>
                    <td class="px-4 py-3">{{ $e->mae }}</td>
                    <td class="px-4 py-3">{{ $e->rmse }}</td>
                    <td class="px-4 py-3">{{ $e->r2 }}</td>
                    <td class="px-4 py-3 text-brand-500">{{ $e->user?->name ?? '—' }}</td>
                    <td class="px-4 py-3 text-brand-500 whitespace-nowrap">{{ $e->created_at?->format('d/m H:i') }}</td>
                    <td class="px-4 py-3"><a href="{{ route('admin.eksperimen', $e) }}" class="text-brand-600 hover:underline">detail</a></td>
                </tr>
            @empty
                <tr><td colspan="12" class="px-6 py-10 text-center text-brand-400">Belum ada eksperimen. <a href="{{ route('admin.latih') }}" class="text-brand-600 hover:underline">Latih model pertama →</a></td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
