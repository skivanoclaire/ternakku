@extends('layouts.admin', ['title' => 'Model Aktif'])
@section('heading', 'Versi & Promosi Model')

@section('page')
<p class="text-sm text-brand-600/80">
    Satu model aktif pada satu waktu — itulah yang melayani estimasi bobot ke peternak.
    Promosikan model terbaik (disarankan: yang mengalahkan baseline pada evaluasi data nyata).
</p>

<div class="bg-white rounded-2xl border border-brand-100 shadow-sm overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="text-left text-brand-500 bg-brand-50/60">
            <tr>
                <th class="px-4 py-3 font-medium">model_ver</th>
                <th class="px-4 py-3 font-medium">Metode</th>
                <th class="px-4 py-3 font-medium">MAPE%</th>
                <th class="px-4 py-3 font-medium">R²</th>
                <th class="px-4 py-3 font-medium">Oleh</th>
                <th class="px-4 py-3 font-medium">Status</th>
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
