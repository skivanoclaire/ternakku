@extends('layouts.admin', ['title' => 'Pengguna'])
@section('heading', 'Manajemen Pengguna & Peran')

@section('page')
<x-help title="Apa itu RBAC, ABAC, dan reset password di sini?" :open="true">
    <p><b>RBAC (Role-Based Access Control)</b>: atur <b>peran</b> tiap akun (admin/researcher/peternak) — peran menentukan
    menu apa yang boleh diakses. <b>ABAC (Attribute-Based Access Control)</b>: status <b>aktif/nonaktif</b> — akun nonaktif
    ditolak masuk apa pun perannya.</p>
    <p><b>Reset password</b>: password tersimpan ter-<i>hash</i> (tidak bisa dibaca balik). Jika peternak lupa atau Anda
    perlu password yang diketahui, tetapkan password baru di sini — yang Anda ketik itulah password barunya.</p>
</x-help>

{{-- Tambah pengguna (RBAC) --}}
<div x-data="{ buka:false }" class="bg-white rounded-2xl border border-brand-100 shadow-sm">
    <button @click="buka=!buka" class="w-full flex items-center justify-between px-6 py-4">
        <span class="font-bold text-brand-800">+ Tambah Pengguna</span>
        <span class="text-brand-500" x-text="buka?'–':'+'"></span>
    </button>
    <form x-show="buka" x-cloak method="POST" action="{{ route('admin.pengguna.store') }}" class="px-6 pb-6 grid sm:grid-cols-2 lg:grid-cols-5 gap-3 items-end">
        @csrf
        <div><label class="block text-xs font-medium mb-1">Nama</label>
            <input name="name" required class="w-full rounded-lg border-brand-200 bg-brand-50/50 px-3 py-2 text-sm"></div>
        <div><label class="block text-xs font-medium mb-1">Email</label>
            <input name="email" type="email" required class="w-full rounded-lg border-brand-200 bg-brand-50/50 px-3 py-2 text-sm"></div>
        <div><label class="block text-xs font-medium mb-1">Password</label>
            <input name="password" required minlength="6" class="w-full rounded-lg border-brand-200 bg-brand-50/50 px-3 py-2 text-sm"></div>
        <div><label class="block text-xs font-medium mb-1">Peran</label>
            <select name="role_id" class="w-full rounded-lg border-brand-200 bg-brand-50/50 px-3 py-2 text-sm">
                @foreach ($roles as $r)<option value="{{ $r->id }}">{{ $r->label ?? $r->name }}</option>@endforeach
            </select></div>
        <button class="px-4 py-2 rounded-lg bg-brand-600 text-white text-sm font-semibold hover:bg-brand-700 transition">Buat</button>
    </form>
</div>

{{-- Daftar pengguna --}}
<div class="bg-white rounded-2xl border border-brand-100 shadow-sm overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="text-left text-brand-500 bg-brand-50/60">
            <tr>
                <th class="px-4 py-3">Nama</th><th class="px-4 py-3">Email</th>
                <th class="px-4 py-3">Peran (RBAC)</th><th class="px-4 py-3">Status (ABAC)</th>
                <th class="px-4 py-3">Reset password</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-brand-50">
            @foreach ($users as $u)
                <tr class="hover:bg-brand-50/50 align-top">
                    <td class="px-4 py-3 font-medium text-brand-700">{{ $u->name }}</td>
                    <td class="px-4 py-3 text-brand-500">{{ $u->email }}</td>
                    <td class="px-4 py-3">
                        <form method="POST" action="{{ route('admin.pengguna.peran', $u) }}">
                            @csrf
                            <select name="role_id" onchange="this.form.submit()" class="text-xs rounded-lg border-brand-200 bg-brand-50/50 py-1">
                                @foreach ($roles as $r)
                                    <option value="{{ $r->id }}" @selected($u->roles->contains($r->id))>{{ $r->label ?? $r->name }}</option>
                                @endforeach
                            </select>
                        </form>
                    </td>
                    <td class="px-4 py-3">
                        @if ($u->is_active)
                            <span class="text-xs px-2 py-0.5 rounded-full bg-green-100 text-green-700">aktif</span>
                        @else
                            <span class="text-xs px-2 py-0.5 rounded-full bg-red-100 text-red-700">nonaktif</span>
                        @endif
                        <form method="POST" action="{{ route('admin.pengguna.aktif', $u) }}" class="inline">
                            @csrf
                            <button class="ml-1 text-xs px-2 py-1 rounded-lg border border-brand-200 text-brand-700 hover:bg-brand-100 transition">{{ $u->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                        </form>
                    </td>
                    <td class="px-4 py-3">
                        <form method="POST" action="{{ route('admin.pengguna.reset', $u) }}" class="flex items-center gap-2">
                            @csrf
                            <input name="password" type="text" required minlength="6" placeholder="password baru"
                                   class="w-32 rounded-lg border-brand-200 bg-brand-50/50 px-2 py-1 text-xs">
                            <button class="text-xs px-3 py-1.5 rounded-lg bg-brand-600 text-white font-semibold hover:bg-brand-700 transition">Reset</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
