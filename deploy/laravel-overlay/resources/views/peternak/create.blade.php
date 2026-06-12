@extends('layouts.peternak', ['title' => 'Tambah Ternak'])

@section('page')
<div class="max-w-lg">
    <a href="{{ route('ternak.index') }}" class="text-sm text-brand-600 hover:underline">← kembali</a>
    <h1 class="text-2xl font-bold text-brand-800 mt-2 mb-6">Tambah Ternak</h1>

    <form method="POST" action="{{ route('ternak.store') }}" class="bg-white rounded-2xl border border-brand-100 shadow-sm p-6 space-y-4">
        @csrf
        <div>
            <label class="block text-sm font-medium mb-1">Jenis</label>
            <select name="jenis" class="w-full rounded-xl border-brand-200 focus:ring-brand-500 focus:border-brand-500 bg-brand-50/50 px-4 py-2.5">
                <option value="sapi">Sapi</option>
                <option value="kambing">Kambing</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Ras / bangsa (opsional)</label>
            <input name="ras" value="{{ old('ras') }}" placeholder="mis. Bali, Limousin"
                   class="w-full rounded-xl border-brand-200 focus:ring-brand-500 focus:border-brand-500 bg-brand-50/50 px-4 py-2.5">
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-sm font-medium mb-1">Kelamin</label>
                <select name="kelamin" class="w-full rounded-xl border-brand-200 focus:ring-brand-500 focus:border-brand-500 bg-brand-50/50 px-4 py-2.5">
                    <option value="">—</option>
                    <option value="jantan">Jantan</option>
                    <option value="betina">Betina</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Umur (bulan)</label>
                <input name="umur_estimasi_bulan" type="number" min="0" max="600" value="{{ old('umur_estimasi_bulan') }}"
                       class="w-full rounded-xl border-brand-200 focus:ring-brand-500 focus:border-brand-500 bg-brand-50/50 px-4 py-2.5">
            </div>
        </div>
        <button class="w-full py-3 rounded-xl bg-brand-600 text-white font-semibold hover:bg-brand-700 transition">Simpan</button>
    </form>
</div>
@endsection
