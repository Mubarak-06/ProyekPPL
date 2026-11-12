<?php

namespace App\Http\Controllers\Pelanggan;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Auth\Events\PasswordReset;

class AuthController extends Controller
{
    public function kirimReset(Request $request)
    {
        $data = $request->validate(['email' => 'required|email|max:255']);
        Password::sendResetLink($data);
        return back()->with('success', 'Jika email terdaftar, tautan reset kata sandi akan dikirim.');
    }

    public function resetPassword(Request $request)
    {
        $data = $request->validate([
            'token' => 'required|string', 'email' => 'required|email|max:255',
            'password' => 'required|string|min:8|max:255|confirmed',
        ]);
        $status = Password::reset($data, function (User $user, string $password) {
            $user->forceFill(['password' => $password, 'remember_token' => Str::random(60)])->save();
            event(new PasswordReset($user));
        });
        if ($status !== Password::PASSWORD_RESET) {
            return back()->withInput($request->only('email'))->withErrors(['email' => 'Tautan reset tidak valid atau sudah kedaluwarsa.']);
        }
        return redirect()->route('login')->with('success', 'Kata sandi berhasil diubah. Silakan masuk.');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email|max:255',
            'password' => 'required|string|max:255',
        ]);

        if (!Auth::attempt($data, $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'Email atau kata sandi tidak sesuai.'])->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('pelanggan.profil'));
    }

    public function daftar(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|max:255|confirmed',
        ]);

        Auth::login(User::create($data));
        $request->session()->regenerate();

        return redirect()->route('pelanggan.profil')->with('success', 'Pendaftaran berhasil.');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('pelanggan.beranda');
    }
}
