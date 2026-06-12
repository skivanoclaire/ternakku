<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/**
 * Auth tanpa Breeze/Vite. Mendukung dua peran:
 *  - admin/researcher: dibuat lewat seeder, login -> dashboard admin
 *  - peternak: registrasi mandiri, login -> portal ternak
 * Event Login tercatat ke audit trail (lihat AppServiceProvider).
 */
class AuthController extends Controller
{
    public function showLogin()
    {
        return Auth::check() ? $this->home() : view('auth.login');
    }

    public function login(Request $request)
    {
        $cred = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($cred, $request->boolean('remember'))) {
            $request->session()->regenerate();
            return $this->home();
        }

        return back()->withErrors(['email' => 'Email atau password salah.'])->onlyInput('email');
    }

    public function showRegister()
    {
        return Auth::check() ? $this->home() : view('auth.register');
    }

    /** Registrasi mandiri -> selalu peran peternak. */
    public function register(Request $request)
    {
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:100'],
            'email'    => ['required', 'email', 'unique:users,email'],
            'wilayah'  => ['nullable', 'string', 'max:100'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::create([
            'name'      => $data['name'],
            'email'     => $data['email'],
            'password'  => Hash::make($data['password']),
            'is_active' => true,
        ]);

        if ($role = Role::where('name', 'peternak')->first()) {
            $user->roles()->syncWithoutDetaching([$role->id]);
        }

        Auth::login($user);
        $request->session()->regenerate();
        return redirect()->route('ternak.index');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('beranda');
    }

    /** Arahkan ke beranda peran masing-masing. */
    protected function home()
    {
        return redirect()->intended(
            Auth::user()->isAdmin() ? route('admin.dashboard') : route('ternak.index')
        );
    }
}
