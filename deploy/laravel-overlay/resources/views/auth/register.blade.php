@extends('layouts.app')
@section('title','Daftar Peternak')

@section('content')
<div class="min-h-screen bg-mesh animate-gradient grid place-items-center px-6 relative overflow-hidden">
    <div class="absolute -top-20 -left-20 w-80 h-80 bg-brand-400/20 rounded-full blur-3xl animate-float"></div>
    <div class="w-full max-w-md animate-pop">
        <div class="text-center mb-6 text-white">
            <a href="/" class="inline-flex items-center gap-2 font-bold text-xl">
                <span class="w-10 h-10 rounded-xl bg-white text-brand-700 grid place-items-center animate-float">🐄</span> TernakKu
            </a>
            <p class="text-brand-50/80 mt-2">Daftar sebagai peternak</p>
        </div>
        <div class="bg-white rounded-3xl shadow-2xl p-8 border border-brand-100">
            <form method="POST" action="{{ route('register') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium mb-1">Nama</label>
                    <input name="name" value="{{ old('name') }}" required autofocus
                           class="w-full rounded-xl border-brand-200 focus:ring-brand-500 focus:border-brand-500 bg-brand-50/50 px-4 py-2.5">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Email</label>
                    <input name="email" type="email" value="{{ old('email') }}" required
                           class="w-full rounded-xl border-brand-200 focus:ring-brand-500 focus:border-brand-500 bg-brand-50/50 px-4 py-2.5">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Wilayah (opsional)</label>
                    <input name="wilayah" value="{{ old('wilayah') }}"
                           class="w-full rounded-xl border-brand-200 focus:ring-brand-500 focus:border-brand-500 bg-brand-50/50 px-4 py-2.5">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium mb-1">Password</label>
                        <input name="password" type="password" required
                               class="w-full rounded-xl border-brand-200 focus:ring-brand-500 focus:border-brand-500 bg-brand-50/50 px-4 py-2.5">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Ulangi</label>
                        <input name="password_confirmation" type="password" required
                               class="w-full rounded-xl border-brand-200 focus:ring-brand-500 focus:border-brand-500 bg-brand-50/50 px-4 py-2.5">
                    </div>
                </div>
                @if ($errors->any())
                    <div class="rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-2">{{ $errors->first() }}</div>
                @endif
                <button class="w-full py-3 rounded-xl bg-brand-600 text-white font-semibold hover:bg-brand-700 transition shadow-lg shadow-brand-600/30">Daftar</button>
            </form>
            <p class="text-center text-sm text-brand-600 mt-4">Sudah punya akun? <a href="{{ route('login') }}" class="font-semibold hover:underline">Masuk</a></p>
        </div>
    </div>
</div>
@endsection
