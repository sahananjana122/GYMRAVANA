<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\View\View;

class EventController extends Controller
{
    public function index(): View
    {
        $today = now()->startOfDay();

        return view('events.index', [
            'upcomingEvents' => Event::query()
                ->active()
                ->where('starts_at', '>=', $today)
                ->orderByDesc('is_featured')
                ->orderBy('starts_at')
                ->get(),
            'pastEvents' => Event::query()
                ->active()
                ->where('starts_at', '<', $today)
                ->orderByDesc('starts_at')
                ->limit(6)
                ->get(),
        ]);
    }
}
