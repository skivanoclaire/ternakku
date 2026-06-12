<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Manajemen pengguna & peran (RBAC/ABAC) — hanya admin.
 * Aktif/nonaktif = atribut ABAC (akun nonaktif ditolak akses).
 */
class UserController extends Controller
{
    public function index()
    {
        return view('admin.pengguna', [
            'users' => User::with('roles')->orderByDesc('id')->get(),
            'roles' => Role::orderBy('name')->get(),
        ]);
    }

    public function toggleActive(Request $request, User $user)
    {
        if ($user->id === $request->user()->id) {
            return back()->withErrors(['user' => 'Tidak bisa menonaktifkan akun sendiri.']);
        }
        $user->update(['is_active' => ! $user->is_active]);
        return back()->with('ok', "Status {$user->name} diperbarui.");
    }

    public function setRole(Request $request, User $user)
    {
        $data = $request->validate(['role_id' => ['required', 'exists:roles,id']]);
        if ($user->id === $request->user()->id) {
            return back()->withErrors(['user' => 'Tidak bisa mengubah peran sendiri.']);
        }
        $user->roles()->sync([$data['role_id']]);
        return back()->with('ok', "Peran {$user->name} diperbarui.");
    }
}
