@extends('layouts.app')
@section('title','Login Admin')

@section('content')
<div class="min-h-screen bg-mesh animate-gradient grid place-items-center px-6 relative overflow-hidden">
    <div class="absolute -top-20 -left-20 w-80 h-80 bg-brand-400/20 rounded-full blur-3xl animate-float"></div>
    <div class="absolute -bottom-20 -right-20 w-80 h-80 bg-brand-300/20 rounded-full blur-3xl animate-float" style="animation-delay:2s"></div>

    <div class="w-full max-w-md animate-pop">
        <div class="text-center mb-6 text-white">
            <a href="/" class="inline-flex items-center gap-2 font-bold text-xl">
                <span class="w-10 h-10 rounded-xl bg-white text-brand-700 grid place-items-center animate-float">🐄</span>
                TernakKu
            </a>
            <p class="text-brand-50/80 mt-2">Panel Administrator</p>
        </div>

        <div class="bg-white rounded-3xl shadow-2xl p-8 border border-brand-100">
            <h1 class="text-2xl font-bold text-brand-800 mb-1">Masuk</h1>
            <p class="text-sm text-brand-600/70 mb-6">Gunakan akun admin Anda.</p>

            @if ($errors->any())
                <div class="mb-4 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium mb-1">Email</label>
                    <input name="email" type="email" value="{{ old('email') }}" required autofocus
                           class="w-full rounded-xl border-brand-200 focus:ring-brand-500 focus:border-brand-500 bg-brand-50/50 px-4 py-2.5">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Password</label>
                    <input name="password" type="password" required
                           class="w-full rounded-xl border-brand-200 focus:ring-brand-500 focus:border-brand-500 bg-brand-50/50 px-4 py-2.5">
                </div>
                <label class="flex items-center gap-2 text-sm text-brand-700">
                    <input name="remember" type="checkbox" class="rounded border-brand-300 text-brand-600 focus:ring-brand-500">
                    Ingat saya
                </label>
                <button type="submit"
                        class="w-full py-3 rounded-xl bg-brand-600 text-white font-semibold hover:bg-brand-700 hover:scale-[1.02] transition shadow-lg shadow-brand-600/30">
                    Masuk ke Dashboard
                </button>
            </form>
        </div>
        <p class="text-center text-brand-50/70 text-sm mt-6">
            <a href="/" class="hover:text-white">← Kembali ke beranda</a>
        </p>
    </div>
</div>
@endsection
