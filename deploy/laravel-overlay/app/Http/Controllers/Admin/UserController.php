<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * Manajemen pengguna & peran (RBAC/ABAC) — hanya admin.
 *  - RBAC: tambah pengguna + tetapkan peran (admin/researcher/peternak).
 *  - ABAC: aktif/nonaktif (akun nonaktif ditolak akses).
 *  - Reset password: password ter-hash tak bisa dibaca; admin menetapkan yang baru.
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

    /** RBAC: buat pengguna baru + peran. */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:100'],
            'email'    => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6'],
            'role_id'  => ['required', 'exists:roles,id'],
        ]);

        $user = User::create([
            'name'      => $data['name'],
            'email'     => $data['email'],
            'password'  => Hash::make($data['password']),
            'is_active' => true,
        ]);
        $user->roles()->sync([$data['role_id']]);

        return back()->with('ok', "Pengguna {$user->email} dibuat.");
    }

    public function toggleActive(Request $request, User $user)
    {
        if ($user->id === $request->user()->id) {
            return back()->withErrors(['user' => 'Tidak bisa menonaktifkan akun sendiri.']);
        }
        $user->update(['is_active' => ! $user->is_active]);
        return back()->with('ok', "Status {$user->name} diperbarui.");
    }

    /** RBAC: ubah peran. */
    public function setRole(Request $request, User $user)
    {
        $data = $request->validate(['role_id' => ['required', 'exists:roles,id']]);
        if ($user->id === $request->user()->id) {
            return back()->withErrors(['user' => 'Tidak bisa mengubah peran sendiri.']);
        }
        $user->roles()->sync([$data['role_id']]);
        return back()->with('ok', "Peran {$user->name} diperbarui.");
    }

    /** Reset password: admin menetapkan password baru (yang ia ketik sendiri). */
    public function resetPassword(Request $request, User $user)
    {
        $data = $request->validate(['password' => ['required', 'string', 'min:6']]);
        $user->update(['password' => Hash::make($data['password'])]);
        return back()->with('ok', "Password {$user->email} berhasil diubah.");
    }
}
