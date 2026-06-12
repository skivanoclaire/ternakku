@extends('layouts.app')
@section('title', $title ?? 'Admin')

@php
    $nav = [
        ['admin.dashboard',  'Dashboard',     '📊'],
        ['admin.belajar',    'Modul Belajar', '📚'],
        ['admin.data',       'Data Latih',    '📥'],
        ['admin.eda',        'Eksplorasi Data','🔎'],
        ['admin.latih',      'Latih Model',   '⚙️'],
        ['admin.leaderboard','Leaderboard',   '🏆'],
        ['admin.model',      'Model Aktif',   '🚀'],
        ['admin.pengguna',   'Pengguna',      '👥'],
    ];
@endphp

@section('content')
<div class="min-h-screen flex">
    <aside class="w-64 bg-brand-900 text-brand-50 hidden md:flex flex-col shrink-0">
        <div class="h-16 flex items-center gap-2 px-6 font-bold text-lg border-b border-brand-800">
            <span class="w-9 h-9 rounded-xl bg-brand-600 grid place-items-center">🐄</span> TernakKu
        </div>
        <nav class="flex-1 p-4 space-y-1 text-sm">
            @foreach ($nav as [$route, $label, $icon])
                <a href="{{ route($route) }}"
                   class="flex items-center gap-2 px-4 py-2.5 rounded-lg transition
                   {{ request()->routeIs($route) ? 'bg-brand-800 font-semibold' : 'text-brand-200/70 hover:bg-brand-800/60' }}">
                    <span>{{ $icon }}</span> {{ $label }}
                </a>
            @endforeach
        </nav>
        <div class="p-4 border-t border-brand-800 text-xs text-brand-200/60">RBAC · ABAC · Audit aktif</div>
    </aside>

    <div class="flex-1 bg-brand-50 min-w-0">
        <header class="h-16 bg-white border-b border-brand-100 flex items-center justify-between px-6">
            <div>
                <h1 class="font-bold text-brand-800">@yield('heading', $title ?? 'Admin')</h1>
                <p class="text-xs text-brand-500">Halo, {{ auth()->user()->name }} · peran: admin</p>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="text-sm font-semibold px-4 py-2 rounded-lg border border-brand-200 text-brand-700 hover:bg-brand-100 transition">Keluar</button>
            </form>
        </header>

        <main class="p-6 space-y-6">
            @if (session('ok'))
                <div class="rounded-xl bg-brand-100 border border-brand-200 text-brand-800 px-4 py-3 text-sm animate-pop">{{ session('ok') }}</div>
            @endif
            @if ($errors->any())
                <div class="rounded-xl bg-red-50 border border-red-200 text-red-700 px-4 py-3 text-sm">{{ $errors->first() }}</div>
            @endif
            @yield('page')
        </main>
    </div>
</div>
@endsection
