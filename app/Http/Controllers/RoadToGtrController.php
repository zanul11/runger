<?php

namespace App\Http\Controllers;

use App\Models\RoadToGtrRegistration;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Form pendaftaran publik "Road to GTR" fun trail (Nama, No. HP, Komunitas, Alamat). */
class RoadToGtrController extends Controller
{
    public function index(): View
    {
        return view('pages.gtr-road-to-gtr', [
            'active' => 'road',
            'open' => RoadToGtrRegistration::isOpen(),
            'eventDate' => RoadToGtrRegistration::eventDate(),
            'total' => $total = RoadToGtrRegistration::count(),
            'quota' => RoadToGtrRegistration::QUOTA,
            'remaining' => max(0, RoadToGtrRegistration::QUOTA - $total),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(RoadToGtrRegistration::isOpen(), 403, 'Pendaftaran Road to GTR sudah ditutup.');

        // Honeypot: kolom tersembunyi yang hanya diisi bot → pura-pura sukses.
        if ($request->filled('website')) {
            return redirect()->route('gtr.road');
        }

        $request->merge(['phone' => RoadToGtrRegistration::normalizePhone($request->input('phone')) ?? $request->input('phone')]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'regex:/^08\d{7,12}$/', Rule::unique('road_to_gtr_registrations', 'phone')],
            'community' => ['nullable', 'string', 'max:120'],
            'address' => ['required', 'string', 'max:500'],
        ], [
            'name.required' => 'Nama wajib diisi.',
            'phone.required' => 'No. HP wajib diisi.',
            'phone.regex' => 'No. HP tidak valid. Contoh: 081234567890.',
            'phone.unique' => 'No. HP ini sudah terdaftar di Road to GTR.',
            'address.required' => 'Alamat wajib diisi.',
        ]);

        // Kuota dicek di dalam transaksi + lock, supaya dua pendaftar yang submit bersamaan
        // tidak sama-sama lolos saat slot tinggal 1.
        $saved = DB::transaction(function () use ($data) {
            if (RoadToGtrRegistration::query()->lockForUpdate()->count() >= RoadToGtrRegistration::QUOTA) {
                return false;
            }
            RoadToGtrRegistration::create(array_map(fn ($v) => is_string($v) ? trim($v) : $v, $data));

            return true;
        });

        if (! $saved) {
            return redirect()->route('gtr.road')
                ->withErrors(['quota' => 'Maaf, kuota Road to GTR ('.RoadToGtrRegistration::QUOTA.' orang) sudah penuh.']);
        }

        return redirect()->route('gtr.road')
            ->with('success', 'Terima kasih, '.trim($data['name']).'! Kamu sudah terdaftar di Road to GTR. Sampai ketemu di trail! 🏃');
    }
}
