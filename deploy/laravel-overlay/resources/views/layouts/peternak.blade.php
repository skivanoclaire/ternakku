@extends('layouts.app')
@section('title', $title ?? 'Peternak')

@section('content')
<div class="min-h-screen">
    <nav class="bg-white border-b border-brand-100 sticky top-0 z-40">
        <div class="max-w-5xl mx-auto px-6 h-16 flex items-center justify-between">
            <a href="{{ route('ternak.index') }}" class="flex items-center gap-2 font-bold text-brand-700">
                <span class="w-9 h-9 rounded-xl bg-brand-600 text-white grid place-items-center">🐄</span> TernakKu
            </a>
            <div class="flex items-center gap-4 text-sm">
                <a href="{{ route('ternak.index') }}" class="font-medium {{ request()->routeIs('ternak.index') ? 'text-brand-700' : 'text-brand-500 hover:text-brand-700' }}">Ternak Saya</a>
                <span class="text-brand-400">{{ auth()->user()->name }}</span>
                <form method="POST" action="{{ route('logout') }}">@csrf
                    <button class="px-3 py-1.5 rounded-lg border border-brand-200 text-brand-700 hover:bg-brand-100 transition">Keluar</button>
                </form>
            </div>
        </div>
    </nav>

    <main class="max-w-5xl mx-auto px-6 py-8 space-y-6">
        @if (session('ok'))
            <div class="rounded-xl bg-brand-100 border border-brand-200 text-brand-800 px-4 py-3 text-sm animate-pop">{{ session('ok') }}</div>
        @endif
        @if ($errors->any())
            <div class="rounded-xl bg-red-50 border border-red-200 text-red-700 px-4 py-3 text-sm">{{ $errors->first() }}</div>
        @endif
        @yield('page')
    </main>
</div>
@endsection
