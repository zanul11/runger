<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\GalleryItem;
use App\Models\Volunteer;
use Illuminate\Http\Request;

class PageController extends Controller
{
    public function home()
    {
        $featuredGallery = GalleryItem::orderBy('is_featured', 'desc')
            ->orderBy('sort_order')
            ->take(8)
            ->get();

        return view('pages.home', compact('featuredGallery'));
    }

    public function agenda()
    {
        // Bucket ordering: upcoming (0) → coming_soon (1) → completed (2)
        $today = now('Asia/Makassar')->toDateString();
        $events = Event::where('is_published', true)
            ->orderByRaw("
                CASE
                  WHEN is_coming_soon = 1 THEN 1
                  WHEN date >= ? THEN 0
                  ELSE 2
                END ASC
            ", [$today])
            ->orderBy('date')
            ->get();

        $upcomingCount = $events->where('status', 'upcoming')->count();
        $soonCount = $events->where('status', 'coming_soon')->count();

        return view('pages.agenda', compact('events', 'upcomingCount', 'soonCount'));
    }

    public function gallery()
    {
        $items = GalleryItem::orderBy('sort_order')->get();
        return view('pages.gallery', compact('items'));
    }

    public function gtr()
    {
        $gtrCategories = \App\Models\GtrCategory::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $gtrSetting = \App\Models\GtrSetting::first();

        $gtrScenics = \App\Models\GtrScenic::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $gtrOverview = \App\Models\GtrOverview::first();

        $gtrSponsors = \App\Models\GtrSponsor::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return view('pages.gtr', compact('gtrCategories', 'gtrSetting', 'gtrScenics', 'gtrOverview', 'gtrSponsors'));
    }

    /** Render HTML email (pendaftaran/pembayaran) untuk pratinjau di admin. */
    public function gtrEmailPreview(\Illuminate\Http\Request $request)
    {
        $type = in_array($request->query('type'), ['registration', 'reminder', 'payment'], true)
            ? $request->query('type')
            : 'payment';

        $reg = $request->filled('registration')
            ? \App\Models\GtrRegistration::with('category')->find($request->query('registration'))
            : null;

        // Tanpa data → contoh dummy agar format tetap bisa dilihat.
        if (! $reg) {
            $reg = new \App\Models\GtrRegistration([
                'nomor_registrasi' => 'GTR202600001',
                'full_name' => 'Budi Santoso (Contoh)',
                'email' => 'peserta@example.com',
                'bib_number' => '7001',
                'pay' => 'QRIS',
                'payment_status' => 'paid',
                'amount' => 150000,
                'discount_code' => 'RUNGER25',
                'discount_amount' => 25000,
            ]);
            $reg->paid_at = now();
            $reg->setRelation('category', \App\Models\GtrCategory::first()
                ?? new \App\Models\GtrCategory(['name' => 'Keteri Trail Run', 'distance' => '7 KM', 'price_normal' => 175000]));
        }

        $mailable = match ($type) {
            'registration' => new \App\Mail\RegistrationConfirmation($reg),
            'reminder' => new \App\Mail\PaymentReminder($reg),
            default => new \App\Mail\PaymentConfirmation($reg),
        };

        return response($mailable->render());
    }

    /** Laporan pembayaran GTR versi cetak. */
    public function gtrPaymentReport()
    {
        return view('gtr.payment-report', [
            'setting' => \App\Models\GtrSetting::first(),
            'report' => \App\Models\GtrRegistration::paymentReport(),
        ]);
    }

    /** Formulir pendaftaran kosong untuk dicetak (pendaftaran offline). */
    public function gtrRegistrationForm()
    {
        $setting = \App\Models\GtrSetting::first();
        $categories = \App\Models\GtrCategory::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return view('gtr.registration-form', compact('setting', 'categories'));
    }

    public function gtrEntryList()
    {
        $categories = \App\Models\GtrCategory::where('is_active', true)
            ->orderBy('sort_order')
            ->with(['registrations' => fn ($q) => $q
                // Urut: lunas dulu → nomor BIB (numerik, 4 digit >999) → nama.
                ->orderByRaw("CASE WHEN payment_status = 'paid' THEN 0 ELSE 1 END")
                ->orderByRaw('bib_number IS NULL')      // yang punya BIB dulu
                ->orderByRaw('LENGTH(bib_number)')       // 3 digit sebelum 4 digit
                ->orderBy('bib_number')
                ->orderByRaw('LOWER(full_name)')])
            ->get();

        return view('pages.gtr-entry-list', ['active' => 'entry', 'categories' => $categories]);
    }

    public function gtrResults()
    {
        return view('pages.gtr-results', ['active' => 'results']);
    }

    public function gtrRules()
    {
        $rules = \App\Models\GtrRule::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return view('pages.gtr-rules', ['active' => 'rules', 'rules' => $rules]);
    }

    public function gtrCategory(string $slug)
    {
        $cat = \App\Models\GtrCategory::where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        return view('pages.gtr-category', [
            'active' => 'category',
            'cat' => $cat,
        ]);
    }

    public function volunteer()
    {
        return view('pages.volunteer');
    }

    public function volunteerStore(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'address' => ['required', 'string', 'max:1000'],
            'interests' => ['required', 'array', 'min:1', 'max:2'],
            'interests.*' => ['in:' . implode(',', array_keys(Volunteer::INTERESTS))],
            'reason' => ['required', 'string', 'max:2000'],
            'experience' => ['nullable', 'string', 'max:2000'],
            'skills' => ['nullable', 'string', 'max:1000'],
        ], [
            'name.required' => 'Nama wajib diisi.',
            'phone.required' => 'No. HP wajib diisi.',
            'address.required' => 'Alamat wajib diisi.',
            'interests.required' => 'Pilih minimal 1 minat kepanitiaan.',
            'interests.max' => 'Maksimal pilih 2 minat kepanitiaan.',
            'reason.required' => 'Alasan wajib diisi.',
        ]);

        Volunteer::create($validated);

        return redirect()->route('volunteer')->with('success', 'Terima kasih! Pendaftaran volunteer GTR kamu sudah kami terima. 🙌');
    }

    public function eventDetail(string $slug)
    {
        $event = Event::where('slug', $slug)
            ->where('is_published', true)
            ->firstOrFail();
        $sponsors = $event->sponsors;
        $route = $event->routes()->where('is_active', true)->orderBy('sort_order')->first();
        return view('pages.event-detail', compact('event', 'sponsors', 'route'));
    }
}
