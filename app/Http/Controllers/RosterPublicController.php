<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Services\RosterService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

/**
 * Roster panitia hari H — halaman publik BACA-SAJA tanpa login (rapat / proyektor / HP), seperti
 * /crew/persiapan. No. HP petugas disamarkan. Mengisi petugas hanya dari GTR Crew (/crew).
 * Tidak diindeks mesin pencari (noindex).
 */
class RosterPublicController extends Controller
{
    public function index(): View
    {
        return view('pages.crew-roster', ['event' => Event::rosterActive()]);
    }

    public function data(RosterService $roster): JsonResponse
    {
        $event = Event::rosterActive();
        abort_unless($event, 404, 'Belum ada event untuk roster.');

        return response()->json($roster->payload($event, null, public: true))
            ->header('Cache-Control', 'no-store');
    }
}
