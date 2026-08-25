<?php

namespace App\Http\Controllers\Trainer;

use App\Http\Controllers\Controller;
use App\Services\ExternalLibraryService;
use Illuminate\View\View;

class LibraryController extends Controller
{
    public function index(ExternalLibraryService $library): View
    {
        return view('trainer.library.index', ['library' => $library->details()]);
    }
}
