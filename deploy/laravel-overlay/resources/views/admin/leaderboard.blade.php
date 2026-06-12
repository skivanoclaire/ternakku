@extends('layouts.admin', ['title' => 'Leaderboard'])
@section('heading', 'Papan Banding Metode')

@section('page')
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
            <tr>
                <th class="px-4 py-3 font-medium">#</th>
                <th class="px-4 py-3 font-medium">Metode</th>
                <th class="px-4 py-3 font-medium">Skenario</th>
                <th class="px-4 py-3 font-medium">Fitur</th>
                <th class="px-4 py-3 font-medium">Eval</th>
                <th class="px-4 py-3 font-medium">MAPE%</th>
                <th class="px-4 py-3 font-medium">MAE</th>
                <th class="px-4 py-3 font-medium">RMSE</th>
                <th class="px-4 py-3 font-medium">R²</th>
                <th class="px-4 py-3 font-medium">Oleh</th>
                <th class="px-4 py-3 font-medium">Tanggal</th>
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
