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
        $type = in_array($request->query('type'), ['registration', 'reminder', 'payment', 'racepack', 'briefing'], true)
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
            'racepack' => new \App\Mail\RacepackReminder($reg),
            'briefing' => new \App\Mail\RaceBriefing($reg),
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
        $published = (bool) \App\Models\GtrSetting::first()?->results_published;
        $categories = collect();

        if ($published) {
            $categories = \App\Models\GtrCategory::where('is_active', true)->orderBy('sort_order')->get()
                ->map(function ($cat) {
                    $rows = \App\Models\GtrResult::with('registration')
                        ->whereHas('registration', fn ($q) => $q->where('gtr_category_id', $cat->id)->where('payment_status', 'paid'))
                        ->whereIn('status', ['finisher', 'dnf', 'dq'])
                        ->get()
                        ->sortBy(fn ($r) => [$r->status === 'finisher' ? 0 : 1, $r->rank_category ?? PHP_INT_MAX, $r->registration->full_name])
                        ->values();

                    return ['cat' => $cat, 'rows' => $rows];
                })
                ->filter(fn ($c) => $c['rows']->count());
        }

        return view('pages.gtr-results', ['active' => 'results', 'published' => $published, 'categories' => $categories]);
    }

    /** Hasil personal: waktu, ranking, split per pos, perbandingan race plan, e-sertifikat & kartu story. */
    public function gtrResult(string $bib)
    {
        abort_unless(\App\Models\GtrSetting::first()?->results_published, 404);

        $reg = \App\Models\GtrRegistration::with(['category', 'result'])
            ->where('payment_status', 'paid')->where('bib_number', $bib)->firstOrFail();
        $result = $reg->result;
        abort_unless($result && in_array($result->status, ['finisher', 'dnf', 'dq'], true), 404);

        $finishers = \App\Models\GtrResult::where('status', 'finisher')->whereNotNull('net_time_seconds')
            ->whereHas('registration', fn ($q) => $q->where('gtr_category_id', $reg->gtr_category_id));
        $genderRank = null;
        $genderTotal = 0;
        if ($result->status === 'finisher' && $result->net_time_seconds !== null) {
            $sameGender = (clone $finishers)->whereHas('registration', fn ($q) => $q->where('gender', $reg->gender));
            $genderTotal = (clone $sameGender)->count();
            $genderRank = (clone $sameGender)->where('net_time_seconds', '<', $result->net_time_seconds)->count() + 1;
        }

        return view('pages.gtr-result', [
            'active' => 'results',
            'reg' => $reg,
            'cat' => $reg->category,
            'result' => $result,
            'catTotal' => (clone $finishers)->count(),
            'genderRank' => $genderRank,
            'genderTotal' => $genderTotal,
            'track' => app(\App\Services\LiveTracker::class)->build($reg),
        ]);
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

    /** Preview course map (sama dengan halaman detail kategori) + export gambar — admin only. */
    public function gtrCoursePreview(\App\Models\GtrCategory $category)
    {
        $ep = $category->elevation_profile ?? [];

        return view('admin.gtr-course-preview', [
            'cat' => $category,
            'categories' => \App\Models\GtrCategory::orderBy('sort_order')->get(['id', 'name', 'distance']),
            'export' => [
                'route' => $category->route_points ?? [],
                'ep' => $ep,
                'points' => $category->coursePoints()->values(),
                'pois' => $category->coursePois()->values(),
                'color' => $category->color ?: '#E53935',
                'distance' => $category->distance,
                'name' => $category->name,
                'tag' => $category->tag,
                'gain' => $category->elevation_gain,
                'start' => $category->start_time,
                'cot' => $category->cut_off_time,
                'slug' => $category->slug,
                'url' => route('gtr.category', $category->slug),
            ],
        ]);
    }

        /** Unduh GPX kategori — hanya bila diizinkan admin. */
    public function gtrCategoryGpx(string $slug)
    {
        $cat = \App\Models\GtrCategory::where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        abort_unless($cat->canDownloadGpx(), 404);

        $filename = 'GTR2026-' . strtoupper(preg_replace('/[^A-Za-z0-9]+/', '', $cat->distance ?: $cat->slug)) . '.gpx';

        return \Illuminate\Support\Facades\Storage::disk('public')
            ->download($cat->gpx_file, $filename, ['Content-Type' => 'application/gpx+xml']);
    }

    /** Live tracking: cari peserta (BIB / nama). */
    public function gtrTrackSearch(\Illuminate\Http\Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $results = collect();

        if ($q !== '') {
            $exact = \App\Models\GtrRegistration::where('payment_status', 'paid')->where('bib_number', $q)->first();
            if ($exact) {
                return redirect()->route('gtr.track', $exact->bib_number);
            }
            if (mb_strlen($q) >= 3) {
                $results = \App\Models\GtrRegistration::with('category')
                    ->where('payment_status', 'paid')
                    ->whereNotNull('bib_number')
                    ->where('full_name', 'like', '%' . $q . '%')
                    ->orderBy('full_name')
                    ->limit(20)
                    ->get();
            }
        }

        return view('pages.gtr-track-search', ['active' => 'track', 'q' => $q, 'results' => $results]);
    }

    /** Live tracking satu peserta. */
    public function gtrTrack(string $bib)
    {
        $reg = $this->trackedRegistration($bib);

        return view('pages.gtr-track', [
            'active' => 'track',
            'reg' => $reg,
            'cat' => $reg->category,
            'data' => app(\App\Services\LiveTracker::class)->build($reg),
        ]);
    }

    /** Data live tracking (JSON) untuk refresh otomatis. */
    public function gtrTrackData(string $bib)
    {
        return response()->json(app(\App\Services\LiveTracker::class)->build($this->trackedRegistration($bib)))
            ->header('Cache-Control', 'no-store');
    }

    private function trackedRegistration(string $bib): \App\Models\GtrRegistration
    {
        return \App\Models\GtrRegistration::with('category')
            ->where('payment_status', 'paid')
            ->where('bib_number', $bib)
            ->firstOrFail();
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
