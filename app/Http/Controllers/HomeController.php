<?php

namespace App\Http\Controllers;

use App\Models\MembershipTier;
use App\Models\ServiceCategory;
use App\Models\TrainerProfile;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        return view('home', [
            'serviceCategories' => ServiceCategory::with(['services' => fn ($query) => $query->where('is_active', true)])->orderBy('display_order')->get(),
            'tiers' => MembershipTier::where('is_active', true)->orderBy('price')->get(),
            'trainers' => TrainerProfile::approved()->with('user')->latest()->limit(3)->get(),
            'promotions' => [
                ['eyebrow' => 'New member start', 'title' => 'Build your first balanced week', 'text' => 'Choose a tier and combine one Body service with one Mind service.'],
                ['eyebrow' => 'Recovery focus', 'title' => 'Reset with yoga therapy', 'text' => 'Request a non-emergency wellness consultation without creating an account.'],
                ['eyebrow' => 'Member store', 'title' => 'Training essentials, simplified', 'text' => 'Browse practical equipment, apparel and recovery products.'],
            ],
            'faqs' => [
                ['question' => 'Can I explore GymRaavana before registering?', 'answer' => 'Yes. Services, trainers, memberships, products and yoga therapy information are all publicly available.'],
                ['question' => 'Do I need an account to request yoga therapy?', 'answer' => 'No. The therapy form is public and is used by the team to contact you for a simple follow-up.'],
                ['question' => 'How are personal trainers approved?', 'answer' => 'Trainer applicants submit their background during registration. An administrator reviews the profile before it appears publicly.'],
                ['question' => 'Is online payment active?', 'answer' => 'Not in this undergraduate MVP. Checkout records a pending order so payment can be integrated safely in a later phase.'],
            ],
        ]);
    }
}
