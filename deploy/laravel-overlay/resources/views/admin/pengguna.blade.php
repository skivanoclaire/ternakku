@extends('layouts.admin', ['title' => 'Pengguna'])
@section('heading', 'Manajemen Pengguna & Peran')

@section('page')
<p class="text-sm text-brand-600/80">Atur peran (RBAC) dan status aktif (ABAC). Akun nonaktif ditolak akses ke seluruh sistem.</p>

<div class="bg-white rounded-2xl border border-brand-100 shadow-sm overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="text-left text-brand-500 bg-brand-50/60">
            <tr>
                <th class="px-4 py-3">Nama</th><th class="px-4 py-3">Email</th>
                <th class="px-4 py-3">Peran</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">Aksi</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-brand-50">
            @foreach ($users as $u)
                <tr class="hover:bg-brand-50/50">
                    <td class="px-4 py-3 font-medium text-brand-700">{{ $u->name }}</td>
                    <td class="px-4 py-3 text-brand-500">{{ $u->email }}</td>
                    <td class="px-4 py-3">
                        <form method="POST" action="{{ route('admin.pengguna.peran', $u) }}" class="flex items-center gap-2">
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
                    </td>
                    <td class="px-4 py-3">
                        <form method="POST" action="{{ route('admin.pengguna.aktif', $u) }}">
                            @csrf
                            <button class="text-xs px-3 py-1.5 rounded-lg border border-brand-200 text-brand-700 hover:bg-brand-100 transition">
                                {{ $u->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                            </button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
