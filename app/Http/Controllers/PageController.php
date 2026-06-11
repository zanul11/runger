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

        return view('pages.gtr', compact('gtrCategories', 'gtrSetting'));
    }

    public function gtrEntryList()
    {
        $categories = \App\Models\GtrCategory::where('is_active', true)
            ->orderBy('sort_order')
            ->with(['registrations' => fn ($q) => $q->orderBy('full_name')])
            ->get();

        return view('pages.gtr-entry-list', ['active' => 'entry', 'categories' => $categories]);
    }

    public function gtrResults()
    {
        return view('pages.gtr-results', ['active' => 'results']);
    }

    public function gtrRules()
    {
        return view('pages.gtr-rules', ['active' => 'rules']);
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
