@extends('layouts.peternak', ['title' => 'Ternak Saya'])

@section('page')
<div class="flex items-center justify-between">
    <h1 class="text-2xl font-bold text-brand-800">Ternak Saya</h1>
    <a href="{{ route('ternak.create') }}" class="px-5 py-2.5 rounded-xl bg-brand-600 text-white font-semibold hover:bg-brand-700 transition shadow-lg shadow-brand-600/20">+ Tambah ternak</a>
</div>

@if ($ternak->isEmpty())
    <div class="bg-white rounded-2xl border border-brand-100 p-10 text-center text-brand-500">
        Belum ada ternak. <a href="{{ route('ternak.create') }}" class="text-brand-600 font-semibold hover:underline">Tambah ternak pertama →</a>
    </div>
@else
    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach ($ternak as $t)
            <a href="{{ route('ternak.show', $t) }}" class="bg-white rounded-2xl border border-brand-100 shadow-sm hover:shadow-lg hover:-translate-y-1 transition p-5 block">
                <div class="flex items-center justify-between">
                    <span class="text-2xl">{{ $t->jenis === 'kambing' ? '🐐' : '🐄' }}</span>
                    <span class="text-xs px-2 py-0.5 rounded-full bg-brand-100 text-brand-700">{{ $t->ras ?: $t->jenis }}</span>
                </div>
                <p class="mt-3 font-bold text-brand-800">Ternak #{{ $t->id }}</p>
                <p class="text-sm text-brand-500">{{ ucfirst($t->jenis) }}{{ $t->kelamin ? ' · '.$t->kelamin : '' }}{{ $t->umur_estimasi_bulan ? ' · '.$t->umur_estimasi_bulan.' bln' : '' }}</p>
                @if ($t->pengukuranTerakhir)
                    <p class="mt-3 text-sm">Estimasi terakhir: <b class="text-brand-700">{{ $t->pengukuranTerakhir->bobot_estimasi_kg }} kg</b></p>
                @else
                    <p class="mt-3 text-sm text-brand-400">Belum ada pengukuran</p>
                @endif
            </a>
        @endforeach
    </div>
@endif
@endsection
