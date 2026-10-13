<?php

namespace App\Http\Controllers;

use App\Models\Runner;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password as PasswordBroker;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class RunnerPasswordController extends Controller
{
    public function showForgot()
    {
        return view('pages.runner.forgot-password');
    }

    public function sendResetLink(Request $request)
    {
        $request->validate(['email' => ['required', 'email']]);

        try {
            $status = PasswordBroker::broker('runners')->sendResetLink($request->only('email'));
        } catch (\Throwable $e) {
            Log::error('Gagal kirim link reset password peserta: ' . $e->getMessage());

            return back()->withErrors(['email' => 'Email gagal dikirim, silakan coba lagi beberapa saat lagi.'])->onlyInput('email');
        }

        if ($status === PasswordBroker::RESET_THROTTLED) {
            return back()->withErrors(['email' => 'Link baru saja dikirim. Tunggu 1 menit sebelum meminta lagi.'])->onlyInput('email');
        }

        // Pesan sama untuk email terdaftar maupun tidak, supaya daftar email peserta tidak bisa ditebak.
        return back()->with('success', 'Jika email tersebut terdaftar, link untuk membuat password baru sudah kami kirim. Cek juga folder spam.');
    }

    public function showReset(Request $request, string $token)
    {
        return view('pages.runner.reset-password', [
            'token' => $token,
            'email' => $request->query('email'),
        ]);
    }

    public function reset(Request $request)
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->mixedCase()->numbers()],
        ]);

        $status = PasswordBroker::broker('runners')->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (Runner $runner, string $password) {
                $runner->forceFill(['password' => $password])->setRememberToken(Str::random(60));
                $runner->save();

                event(new PasswordReset($runner));
            }
        );

        if ($status !== PasswordBroker::PASSWORD_RESET) {
            return back()->withErrors(['email' => 'Link reset tidak valid atau sudah kedaluwarsa. Silakan minta link baru.'])->onlyInput('email');
        }

        return redirect()->route('gtr.login')->with('success', 'Password berhasil diganti. Silakan masuk dengan password baru.');
    }

    public function update(Request $request)
    {
        $request->validateWithBag('password', [
            'current_password' => ['required', 'current_password:runner'],
            'password' => ['required', 'confirmed', 'different:current_password', Password::min(8)->letters()->mixedCase()->numbers()],
        ], [
            'current_password.current_password' => 'Password lama salah.',
            'password.different' => 'Password baru harus berbeda dari password lama.',
        ], [
            'current_password' => 'password lama',
            'password' => 'password baru',
        ]);

        $runner = Auth::guard('runner')->user();
        $runner->forceFill(['password' => $request->input('password')])->setRememberToken(Str::random(60));
        $runner->save();

        $request->session()->regenerate();

        return redirect()->route('gtr.account.profile')->with('success', 'Password berhasil diganti.');
    }
}
