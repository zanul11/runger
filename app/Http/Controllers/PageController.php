<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\GalleryItem;

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
