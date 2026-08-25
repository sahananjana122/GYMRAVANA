<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\View\View;

class NotificationActivityController extends Controller
{
    public function index(): View
    {
        return view('admin.notifications.index', [
            'notifications' => DatabaseNotification::query()
                ->with('notifiable')
                ->latest()
                ->paginate(25),
        ]);
    }
}
