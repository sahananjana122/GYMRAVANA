<?php

namespace App\Http\Controllers;

use App\Models\Notice;
use Illuminate\View\View;

class NoticeBoardController extends Controller
{
    public function index(): View
    {
        $notices = Notice::query()
            ->published()
            ->with(['event', 'member'])
            ->orderByDesc('is_featured')
            ->orderByDesc('published_at')
            ->get();

        return view('notices.index', [
            'featuredNotice' => $notices->firstWhere('is_featured', true),
            'notices' => $notices,
        ]);
    }
}
