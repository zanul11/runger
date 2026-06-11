<?php

namespace App\Http\Controllers;

use App\Models\GtrCategory;
use App\Models\GtrRegistration;
use Illuminate\Support\Facades\Auth;

class RunnerDashboardController extends Controller
{
    protected function runner()
    {
        return Auth::guard('runner')->user();
    }

    public function index()
    {
        $runner = $this->runner();
        $categories = GtrCategory::where('is_active', true)->orderBy('sort_order')->get();
        $registeredIds = $runner->registrations()->pluck('gtr_category_id')->all();

        return view('pages.runner.home', [
            'tab' => 'home',
            'runner' => $runner,
            'categories' => $categories,
            'registeredIds' => $registeredIds,
        ]);
    }

    public function race()
    {
        $runner = $this->runner();
        $registrations = $runner->registrations()->with('category')->latest()->get();

        return view('pages.runner.race', [
            'tab' => 'race',
            'runner' => $runner,
            'registrations' => $registrations,
        ]);
    }

    public function raceDetail(GtrRegistration $registration)
    {
        $runner = $this->runner();
        abort_unless($registration->runner_id === $runner->id, 403);

        $registration->load('category');

        return view('pages.runner.race-detail', [
            'tab' => 'race',
            'runner' => $runner,
            'reg' => $registration,
        ]);
    }

    public function transaction()
    {
        $runner = $this->runner();
        $registrations = $runner->registrations()->with('category')->latest()->get();

        return view('pages.runner.transaction', [
            'tab' => 'transaction',
            'runner' => $runner,
            'registrations' => $registrations,
        ]);
    }

    public function profile()
    {
        return view('pages.runner.profile', [
            'tab' => 'profile',
            'runner' => $this->runner(),
        ]);
    }

    public function registrationForm(GtrCategory $category)
    {
        $runner = $this->runner();

        $existing = $runner->registrations()->where('gtr_category_id', $category->id)->first();
        if ($existing) {
            return redirect()->route('gtr.account.race')->with('success', 'Kamu sudah terdaftar di kategori ' . $category->distance . '.');
        }

        return view('pages.runner.register-race', [
            'tab' => 'home',
            'runner' => $runner,
            'category' => $category,
        ]);
    }

    public function storeRegistration(\Illuminate\Http\Request $request, GtrCategory $category)
    {
        $runner = $this->runner();

        if ($runner->registrations()->where('gtr_category_id', $category->id)->exists()) {
            return redirect()->route('gtr.account.race')->with('success', 'Kamu sudah terdaftar di kategori ' . $category->distance . '.');
        }

        $validated = $request->validate([
            'size' => ['required', 'in:' . implode(',', GtrRegistration::SIZES)],
            'full_name' => ['required', 'string', 'max:255'],
            'nik' => ['required', 'digits:16'],
            'email' => ['required', 'email', 'max:255'],
            'whatsapp' => ['required', 'string', 'max:30'],
            'birth_date' => ['required', 'date'],
            'gender' => ['required', 'in:' . implode(',', GtrRegistration::GENDERS)],
            'address' => ['required', 'string', 'max:255'],
            'club' => ['nullable', 'string', 'max:255'],
            'blood_type' => ['required', 'in:' . implode(',', GtrRegistration::BLOOD_TYPES)],
            'emergency_name' => ['required', 'string', 'max:255'],
            'emergency_contact' => ['required', 'string', 'max:30'],
            'pay' => ['required', 'string', 'max:50'],
            'agree_terms' => ['accepted'],
        ], [
            'nik.digits' => 'NIK harus 16 digit angka.',
            'agree_terms.accepted' => 'Kamu harus menyetujui syarat & ketentuan.',
        ]);

        $registration = GtrRegistration::create(array_merge($validated, [
            'runner_id' => $runner->id,
            'gtr_category_id' => $category->id,
            'club' => $validated['club'] ?? '-',
            'payment_status' => 'pending',
            'agree_terms' => true,
            'registered_at' => now(),
        ]));

        $registration->update([
            'nomor_registrasi' => 'GTR2026' . str_pad($registration->id, 5, '0', STR_PAD_LEFT),
        ]);

        return redirect()->route('gtr.account.transaction')->with('success', 'Pendaftaran ' . $category->distance . ' berhasil! No. registrasi: ' . $registration->nomor_registrasi . '. Lanjutkan pembayaran.');
    }
}
