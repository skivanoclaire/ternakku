@extends('layouts.admin', ['title' => 'Model Aktif'])
@section('heading', 'Versi & Promosi Model')

@section('page')
<x-help title="Apa arti 'promosi' & 'model aktif'?" :open="true">
    <p>Setiap pelatihan menghasilkan satu versi model (<code>model_ver</code>) yang tersimpan. <b>Hanya satu yang "aktif"</b>
    pada satu waktu — model aktif inilah yang dipakai menghitung estimasi untuk peternak. <b>Promosikan</b> = jadikan aktif.</p>
    <p>Disarankan promosikan model yang: (1) <b>mengalahkan baseline Schoorl</b>, dan (2) dievaluasi pada <b>skenario B
    (data nyata)</b>. Bila versi baru ternyata buruk di lapangan, cukup promosikan kembali versi lama (rollback). Tiap
    prediksi menyimpan <code>model_ver</code> sehingga bisa diaudit & di-A/B-test.</p>
</x-help>

<p class="text-sm text-brand-600/80">
    Satu model aktif pada satu waktu — itulah yang melayani estimasi bobot ke peternak.
    Promosikan model terbaik (disarankan: yang mengalahkan baseline pada evaluasi data nyata).
</p>

<div class="bg-white rounded-2xl border border-brand-100 shadow-sm overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="text-left text-brand-500 bg-brand-50/60">
            <tr>
                <th class="px-4 py-3 font-medium"><span class="cursor-help border-b border-dotted border-brand-400" title="Identitas unik versi model (mis. exp4-xgb). Dicatat pada tiap prediksi untuk audit & A/B test.">model_ver</span></th>
                <th class="px-4 py-3 font-medium"><span class="cursor-help border-b border-dotted border-brand-400" title="Algoritma yang dipakai melatih model ini (linear, log-log, Random Forest, XGBoost, hibrida, atau baseline Schoorl).">Metode</span></th>
                <th class="px-4 py-3 font-medium"><span class="cursor-help border-b border-dotted border-brand-400" title="Mean Absolute Percentage Error — rata-rata kesalahan dalam persen. Makin kecil makin akurat; metrik utama yang mudah dijelaskan ke peternak.">MAPE%</span></th>
                <th class="px-4 py-3 font-medium"><span class="cursor-help border-b border-dotted border-brand-400" title="Koefisien determinasi (0–1): proporsi variasi bobot yang dijelaskan model. Mendekati 1 = sangat baik; negatif = lebih buruk dari sekadar menebak rata-rata.">R²</span></th>
                <th class="px-4 py-3 font-medium"><span class="cursor-help border-b border-dotted border-brand-400" title="Pengguna (researcher) yang melatih model ini.">Oleh</span></th>
                <th class="px-4 py-3 font-medium"><span class="cursor-help border-b border-dotted border-brand-400" title="★ aktif = model yang sedang melayani estimasi peternak. arsip = tersimpan tapi tidak dipakai.">Status</span></th>
                <th class="px-4 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-brand-50">
            @forelse ($experiments as $e)
                <tr class="hover:bg-brand-50/50">
                    <td class="px-4 py-3 font-mono text-xs">{{ $e->model_ver }}</td>
                    <td class="px-4 py-3">{{ $e->method_label ?? $e->method }}</td>
                    <td class="px-4 py-3 font-bold text-brand-800">{{ $e->mape }}</td>
                    <td class="px-4 py-3">{{ $e->r2 }}</td>
                    <td class="px-4 py-3 text-brand-500">{{ $e->user?->name ?? '—' }}</td>
                    <td class="px-4 py-3">
                        @if ($e->is_active)
                            <span class="text-xs px-2 py-0.5 rounded-full bg-green-600 text-white">★ aktif</span>
                        @else
                            <span class="text-xs px-2 py-0.5 rounded-full bg-brand-100 text-brand-600">arsip</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        @unless ($e->is_active)
                            <form method="POST" action="{{ route('admin.model.promote', $e) }}">
                                @csrf
                                <button class="px-3 py-1.5 rounded-lg bg-brand-600 text-white text-xs font-semibold hover:bg-brand-700 transition">🚀 Aktifkan</button>
                            </form>
                        @endunless
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="px-6 py-10 text-center text-brand-400">Belum ada model. <a href="{{ route('admin.latih') }}" class="text-brand-600 hover:underline">Latih model →</a></td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
