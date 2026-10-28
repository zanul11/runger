<?php

namespace App\Http\Controllers;

use App\Mail\RegistrationConfirmation;
use App\Models\GtrCategory;
use App\Models\GtrRegistration;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

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
            'gtrSetting' => \App\Models\GtrSetting::first(),
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

        $target = $registration->target_finish_minutes ?: self::defaultTarget($registration->category);
        $plan = $target && $registration->category ? $registration->category->racePlan($target) : [];

        return view('pages.runner.race-detail', [
            'tab' => 'race',
            'runner' => $runner,
            'reg' => $registration,
            'plan' => $plan,
            'planTarget' => $target,
        ]);
    }

    /** Simpan target finish peserta (race plan). */
    public function saveRacePlan(\Illuminate\Http\Request $request, GtrRegistration $registration)
    {
        abort_unless($registration->runner_id === $this->runner()->id, 403);

        $data = $request->validate([
            'target_finish_minutes' => ['required', 'integer', 'min:20', 'max:720'],
            'race_plan_mode' => ['nullable', 'in:pos,km'],
        ]);
        $registration->update($data);

        $t = $data['target_finish_minutes'];

        return back()->with('success', 'Race plan disimpan — target finish ' . intdiv($t, 60) . ':' . str_pad((string) ($t % 60), 2, '0', STR_PAD_LEFT) . '.');
    }

    /** Pace band (gambar/print) dari race plan peserta. */
    public function paceBand(\Illuminate\Http\Request $request, GtrRegistration $registration)
    {
        abort_unless($registration->runner_id === $this->runner()->id, 403);
        $registration->load('category');

        $cat = $registration->category;
        $target = $registration->target_finish_minutes ?: self::defaultTarget($cat);
        $plan = $target ? $cat->racePlan($target) : [];
        abort_if(empty($plan), 404);

        // mode mengikuti pilihan saat menyimpan race plan; bisa diganti lewat ?mode=
        $mode = in_array($request->query('mode'), ['pos', 'km'], true)
            ? $request->query('mode')
            : ($registration->race_plan_mode ?: 'pos');

        return view('pages.runner.pace-band', [
            'tab' => 'race',
            'reg' => $registration,
            'cat' => $cat,
            'target' => $target,
            'plan' => $plan,
            'splits' => $mode === 'km' ? $cat->racePlanSplits($target) : [],
            'mode' => $mode,
            'isDefault' => ! $registration->target_finish_minutes,
        ]);
    }

    /** Galeri foto race peserta (ditandai BIB oleh panitia). */
    public function photos(GtrRegistration $registration)
    {
        abort_unless($registration->runner_id === $this->runner()->id, 403);

        return view('pages.runner.photos', [
            'tab' => 'race',
            'reg' => $registration,
            'photos' => $registration->bib_number ? $registration->photos()->get() : collect(),
        ]);
    }

        /** Target bawaan bila peserta belum menyimpan: ~72% COT (sama dengan estimator). */
    public static function defaultTarget(?GtrCategory $cat): ?int
    {
        if (! $cat) {
            return null;
        }
        $cot = $cat->cutOffMinutes();
        if ($cot) {
            return (int) (round($cot * 0.72 / 5) * 5);
        }
        $effort = array_sum(array_column($cat->courseSegments()['segments'] ?? [], 'effort'));

        return $effort ? (int) (round($effort * 7.5 / 5) * 5) : null;
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

    /** Detail kategori di app: course map, pos, segmen & estimasi, rundown, gear, GPX. */
    public function category(GtrCategory $category)
    {
        abort_unless($category->is_active, 404);

        $runner = $this->runner();

        return view('pages.runner.category', [
            'tab' => 'home',
            'runner' => $runner,
            'cat' => $category,
            'registration' => $runner->registrations()->where('gtr_category_id', $category->id)->first(),
        ]);
    }

        public function registrationForm(GtrCategory $category)
    {
        $runner = $this->runner();

        $existing = $runner->registrations()->where('gtr_category_id', $category->id)->first();
        if ($existing) {
            return redirect()->route('gtr.account.race')->with('success', 'Kamu sudah terdaftar di kategori ' . $category->distance . '.');
        }

        if (\App\Models\GtrSetting::registrationClosed()) {
            return redirect()->route('gtr.dashboard')->with('success', 'Maaf, pendaftaran GTR 2026 sudah ditutup.');
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

        if (\App\Models\GtrSetting::registrationClosed()) {
            return redirect()->route('gtr.dashboard')->with('success', 'Maaf, pendaftaran GTR 2026 sudah ditutup.');
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
            'emergency_contact' => ['required', 'string', 'max:30', function ($attribute, $value, $fail) use ($request) {
                if (\App\Support\Phone::normalize($value) === \App\Support\Phone::normalize($request->input('whatsapp'))) {
                    $fail('Kontak darurat tidak boleh sama dengan nomor WhatsApp kamu.');
                }
            }],
            'pay' => ['required', 'string', 'max:50'],
            'discount_code' => ['nullable', 'string', 'max:50'],
            'agree_terms' => ['accepted'],
        ], [
            'nik.digits' => 'NIK harus 16 digit angka.',
            'agree_terms.accepted' => 'Kamu harus menyetujui syarat & ketentuan.',
        ]);

        // Validasi kode voucher (bila diisi) sebelum membuat pendaftaran.
        $discount = null;
        if (! empty($request->input('discount_code'))) {
            $discount = \App\Models\GtrDiscount::findUsable($request->input('discount_code'));
            if (! $discount) {
                return back()->withInput()->withErrors([
                    'discount_code' => 'Kode voucher tidak valid, tidak aktif, atau kuotanya sudah habis.',
                ]);
            }
        }

        unset($validated['discount_code']);

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

        // Terapkan voucher: kunci baris diskon, cek ulang kuota, catat potongan &
        // pemakai, lalu tambah used_count (atomik) — supaya kuota akurat & tertrack.
        if ($discount) {
            \Illuminate\Support\Facades\DB::transaction(function () use ($discount, $registration, $category) {
                $locked = \App\Models\GtrDiscount::whereKey($discount->id)->lockForUpdate()->first();
                if (! $locked || ! $locked->isUsable()) {
                    return; // keburu habis oleh orang lain → lanjut tanpa diskon
                }

                $potongan = $locked->amountFor((int) ($category->currentPrice() ?? 0));

                $registration->forceFill([
                    'gtr_discount_id' => $locked->id,
                    'discount_code' => $locked->code,
                    'discount_amount' => $potongan,
                    'discount_consumed' => true, // sedang memakai slot kuota
                ])->save();

                $locked->increment('used_count');
            });
        }

        // Kirim email konfirmasi pendaftaran (jangan gagalkan pendaftaran bila email error).
        try {
            Mail::to($registration->email)->send(new RegistrationConfirmation($registration->fresh('category')));
        } catch (\Throwable $e) {
            Log::warning('Gagal kirim email konfirmasi pendaftaran: ' . $e->getMessage());
        }

        return redirect()->route('gtr.account.transaction')->with('success', 'Pendaftaran ' . $category->distance . ' berhasil! No. registrasi: ' . $registration->nomor_registrasi . '. Cek email kamu untuk konfirmasi, lalu lanjutkan pembayaran.');
    }
}
