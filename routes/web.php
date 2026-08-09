<?php

use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\BodyMeasurementController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TherapyRequestController;
use App\Http\Controllers\WellnessController;
use App\Http\Controllers\WorkoutController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'redirect'])->name('dashboard');

    Route::prefix('admin')->name('admin.')->middleware('role:admin')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'admin'])->name('dashboard');
        Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
        Route::patch('/users/{user}/role', [AdminUserController::class, 'updateRole'])->name('users.role');
    });

    Route::prefix('master')->name('master.')->middleware('role:master')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'master'])->name('dashboard');
    });

    Route::prefix('trainer')->name('trainer.')->middleware('role:trainer')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'trainer'])->name('dashboard');
    });

    Route::prefix('member')->name('member.')->middleware('role:member')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'member'])->name('dashboard');
        Route::get('/workouts', [WorkoutController::class, 'index'])->name('workouts.index');
        Route::post('/workouts/{workoutPlan}/complete', [WorkoutController::class, 'complete'])->name('workouts.complete');
        Route::get('/measurements', [BodyMeasurementController::class, 'index'])->name('measurements.index');
        Route::post('/measurements', [BodyMeasurementController::class, 'store'])->name('measurements.store');
        Route::get('/wellness', [WellnessController::class, 'index'])->name('wellness.index');
        Route::post('/wellness/{wellnessActivity}/complete', [WellnessController::class, 'complete'])->name('wellness.complete');
        Route::get('/therapy', [TherapyRequestController::class, 'index'])->name('therapy.index');
        Route::post('/therapy', [TherapyRequestController::class, 'store'])->name('therapy.store');
    });

    Route::middleware('role:admin|trainer')->group(function () {
        Route::get('/therapy-requests', [TherapyRequestController::class, 'manage'])->name('therapy.manage');
        Route::patch('/therapy-requests/{therapyRequest}', [TherapyRequestController::class, 'update'])->name('therapy.update');
    });

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
