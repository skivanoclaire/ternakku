@extends('layouts.app')
@section('title','Dashboard Admin')

@section('content')
<div class="min-h-screen flex">
    {{-- Sidebar --}}
    <aside class="w-64 bg-brand-900 text-brand-50 hidden md:flex flex-col">
        <div class="h-16 flex items-center gap-2 px-6 font-bold text-lg border-b border-brand-800">
            <span class="w-9 h-9 rounded-xl bg-brand-600 grid place-items-center">🐄</span> TernakKu
        </div>
        <nav class="flex-1 p-4 space-y-1 text-sm">
            <span class="block px-4 py-2.5 rounded-lg bg-brand-800 font-semibold">Dashboard</span>
            <span class="block px-4 py-2.5 rounded-lg text-brand-200/70">Audit Trail</span>
            <span class="block px-4 py-2.5 rounded-lg text-brand-200/70">Pengguna &amp; Peran</span>
        </nav>
        <div class="p-4 border-t border-brand-800 text-xs text-brand-200/60">
            RBAC · ABAC · Audit aktif
        </div>
    </aside>

    {{-- Main --}}
    <div class="flex-1 bg-brand-50">
        <header class="h-16 bg-white border-b border-brand-100 flex items-center justify-between px-6">
            <div>
                <h1 class="font-bold text-brand-800">Dashboard Admin</h1>
                <p class="text-xs text-brand-500">Halo, {{ auth()->user()->name }} · peran: admin</p>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="text-sm font-semibold px-4 py-2 rounded-lg border border-brand-200 text-brand-700 hover:bg-brand-100 transition">Keluar</button>
            </form>
        </header>

        <main class="p-6 space-y-6">
            {{-- Stat cards --}}
            <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
                @foreach ([
                    ['Pengguna', $stats['users'] ?? 0, '👥'],
                    ['Ternak', $stats['ternak'] ?? 0, '🐄'],
                    ['Pengukuran', $stats['pengukuran'] ?? 0, '📏'],
                    ['Entri Audit', $stats['audits'] ?? 0, '🛡️'],
                ] as $i => $card)
                    <div class="bg-white rounded-2xl p-5 border border-brand-100 shadow-sm hover:shadow-lg hover:-translate-y-1 transition animate-fade-up" style="animation-delay:{{ $i*80 }}ms">
                        <div class="text-2xl">{{ $card[2] }}</div>
                        <p class="text-3xl font-extrabold text-brand-800 mt-2">{{ number_format($card[1]) }}</p>
                        <p class="text-sm text-brand-500">{{ $card[0] }}</p>
                    </div>
                @endforeach
            </div>

            {{-- Audit trail --}}
            <div class="bg-white rounded-2xl border border-brand-100 shadow-sm animate-fade-up delay-2">
                <div class="px-6 py-4 border-b border-brand-100 flex items-center justify-between">
                    <h2 class="font-bold text-brand-800">Audit Trail Terbaru</h2>
                    <span class="text-xs px-2.5 py-1 rounded-full bg-brand-100 text-brand-700">50 terakhir</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="text-left text-brand-500 bg-brand-50/60">
                            <tr>
                                <th class="px-6 py-3 font-medium">Waktu</th>
                                <th class="px-6 py-3 font-medium">Pengguna</th>
                                <th class="px-6 py-3 font-medium">Event</th>
                                <th class="px-6 py-3 font-medium">Objek</th>
                                <th class="px-6 py-3 font-medium">IP</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-brand-50">
                            @forelse ($auditTerbaru as $a)
                                <tr class="hover:bg-brand-50/50">
                                    <td class="px-6 py-3 whitespace-nowrap text-brand-600">{{ $a->created_at?->format('d/m H:i') }}</td>
                                    <td class="px-6 py-3">{{ $a->user?->name ?? '—' }}</td>
                                    <td class="px-6 py-3">
                                        <span class="px-2 py-0.5 rounded-full text-xs font-medium
                                            @class([
                                                'bg-green-100 text-green-700' => $a->event==='created'||$a->event==='login',
                                                'bg-amber-100 text-amber-700' => $a->event==='updated',
                                                'bg-red-100 text-red-700' => $a->event==='deleted',
                                                'bg-brand-100 text-brand-700' => !in_array($a->event,['created','updated','deleted','login']),
                                            ])">{{ $a->event }}</span>
                                    </td>
                                    <td class="px-6 py-3 text-brand-600">{{ class_basename($a->auditable_type) }}{{ $a->auditable_id ? ' #'.$a->auditable_id : '' }}</td>
                                    <td class="px-6 py-3 text-brand-500">{{ $a->ip_address }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-6 py-10 text-center text-brand-400">Belum ada aktivitas tercatat.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</div>
@endsection
