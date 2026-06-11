<?php

namespace App\Http\Controllers;

use App\Models\Runner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;

class RunnerAuthController extends Controller
{
    public function showRegister()
    {
        return view('pages.runner.register');
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:runners,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:1000'],
            'birthdate' => ['required', 'date'],
            'gender' => ['required', 'in:male,female'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->mixedCase()->numbers()],
        ], [], [
            'first_name' => 'nama depan',
            'birthdate' => 'tanggal lahir',
            'gender' => 'jenis kelamin',
        ]);

        $runner = Runner::create($validated);

        Auth::guard('runner')->login($runner);

        return redirect()->route('gtr.dashboard')->with('success', 'Akun berhasil dibuat. Selamat datang!');
    }

    public function showLogin()
    {
        return view('pages.runner.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::guard('runner')->attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            return redirect()->intended(route('gtr.dashboard'));
        }

        return back()->withErrors(['email' => 'Email atau password salah.'])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::guard('runner')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('gtr');
    }
}
