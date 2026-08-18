<?php

use App\Http\Controllers\AboutController;
use App\Http\Controllers\Admin\BookingManagementController;
use App\Http\Controllers\Admin\MembershipTierController as AdminMembershipTierController;
use App\Http\Controllers\Admin\OrderManagementController;
use App\Http\Controllers\Admin\ProductManagementController;
use App\Http\Controllers\Admin\ServiceManagementController;
use App\Http\Controllers\Admin\TherapyAppointmentController as AdminTherapyAppointmentController;
use App\Http\Controllers\Admin\TrainerApplicationController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\BodyMeasurementController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GroupProgramController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MembershipController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\TherapyFinderController;
use App\Http\Controllers\TherapyRequestController;
use App\Http\Controllers\Trainer\BookingController as TrainerBookingController;
use App\Http\Controllers\Trainer\ProfileController as TrainerProfileController;
use App\Http\Controllers\TrainerDirectoryController;
use App\Http\Controllers\WellnessController;
use App\Http\Controllers\WorkoutController;
use App\Http\Controllers\YogaTherapyController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/about', [AboutController::class, 'index'])->name('about.index');
Route::get('/programs', [ServiceController::class, 'index'])->name('programs.index');
Route::get('/group-programs', [GroupProgramController::class, 'index'])->name('group-programs.index');
Route::post('/group-programs/{groupProgram}/register', [GroupProgramController::class, 'register'])->middleware('throttle:6,1')->name('group-programs.register');
Route::get('/contact', [ContactController::class, 'index'])->name('contact.index');
Route::post('/contact', [ContactController::class, 'store'])->middleware('throttle:6,1')->name('contact.store');

Route::get('/services', [ServiceController::class, 'index'])->name('services.index');
Route::get('/services/{serviceCategory}', [ServiceController::class, 'category'])->name('services.category');
Route::get('/services/{serviceCategory}/{service}', [ServiceController::class, 'show'])->name('services.show');

Route::get('/yoga-therapy', [YogaTherapyController::class, 'index'])->name('yoga-therapy.index');
Route::post('/yoga-therapy', [YogaTherapyController::class, 'store'])->name('yoga-therapy.store');
Route::get('/find-your-therapy', [TherapyFinderController::class, 'index'])->name('therapy-finder.index');
Route::post('/find-your-therapy/appointments', [TherapyFinderController::class, 'store'])->middleware('throttle:6,1')->name('therapy-finder.store');
Route::get('/therapy-appointments/{therapyAppointment}', [TherapyFinderController::class, 'success'])->name('therapy-appointments.success');

Route::get('/trainers', [TrainerDirectoryController::class, 'index'])->name('trainers.index');
Route::get('/trainers/{trainerProfile}', [TrainerDirectoryController::class, 'show'])->name('trainers.show');

Route::get('/memberships', [MembershipController::class, 'index'])->name('memberships.index');

Route::get('/products', [ProductController::class, 'index'])->name('products.index');
Route::get('/products/{productCategory}', [ProductController::class, 'category'])->name('products.category');
Route::get('/products/{productCategory}/{product}', [ProductController::class, 'show'])->name('products.show');

Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::post('/cart/{product}', [CartController::class, 'add'])->name('cart.add');
Route::patch('/cart/{product}', [CartController::class, 'update'])->name('cart.update');
Route::delete('/cart/{product}', [CartController::class, 'remove'])->name('cart.remove');
Route::get('/checkout', [CheckoutController::class, 'create'])->name('checkout.create');
Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
Route::get('/checkout/success/{order}', [CheckoutController::class, 'success'])->name('checkout.success');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'redirect'])->name('dashboard');

    Route::middleware('role:member')->group(function () {
        Route::get('/trainers/{trainerProfile}/book', [TrainerDirectoryController::class, 'bookingForm'])->name('trainers.book');
        Route::post('/trainers/{trainerProfile}/book', [TrainerDirectoryController::class, 'book'])->name('trainers.book.store');
        Route::post('/services/{service}/enroll', [ServiceController::class, 'enroll'])->name('member.services.enroll');

        Route::prefix('member')->name('member.')->group(function () {
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
    });

    Route::prefix('trainer')->name('trainer.')->middleware('role:trainer')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'trainer'])->name('dashboard');
        Route::get('/profile', [TrainerProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/profile', [TrainerProfileController::class, 'update'])->name('profile.update');
        Route::get('/bookings', [TrainerBookingController::class, 'index'])->name('bookings.index');
        Route::patch('/bookings/{trainerBooking}', [TrainerBookingController::class, 'update'])->name('bookings.update');
    });

    Route::prefix('admin')->name('admin.')->middleware('role:admin')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'admin'])->name('dashboard');
        Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
        Route::patch('/users/{user}/role', [AdminUserController::class, 'updateRole'])->name('users.role');

        Route::get('/trainers', [TrainerApplicationController::class, 'index'])->name('trainers.index');
        Route::patch('/trainers/{trainerProfile}', [TrainerApplicationController::class, 'update'])->name('trainers.update');
        Route::get('/memberships', [AdminMembershipTierController::class, 'index'])->name('memberships.index');
        Route::post('/memberships', [AdminMembershipTierController::class, 'store'])->name('memberships.store');
        Route::patch('/memberships/{membershipTier}', [AdminMembershipTierController::class, 'update'])->name('memberships.update');
        Route::patch('/members/{user}/tier', [AdminMembershipTierController::class, 'assign'])->name('members.tier');
        Route::get('/services', [ServiceManagementController::class, 'index'])->name('services.index');
        Route::post('/services', [ServiceManagementController::class, 'store'])->name('services.store');
        Route::patch('/services/{service}', [ServiceManagementController::class, 'update'])->name('services.update');
        Route::get('/products', [ProductManagementController::class, 'index'])->name('products.index');
        Route::post('/product-categories', [ProductManagementController::class, 'storeCategory'])->name('product-categories.store');
        Route::post('/products', [ProductManagementController::class, 'store'])->name('products.store');
        Route::patch('/products/{product}', [ProductManagementController::class, 'update'])->name('products.update');
        Route::get('/orders', [OrderManagementController::class, 'index'])->name('orders.index');
        Route::patch('/orders/{order}', [OrderManagementController::class, 'update'])->name('orders.update');
        Route::get('/bookings', [BookingManagementController::class, 'index'])->name('bookings.index');
        Route::patch('/bookings/{trainerBooking}', [BookingManagementController::class, 'update'])->name('bookings.update');
        Route::get('/therapy-requests', [TherapyRequestController::class, 'manage'])->name('therapy.index');
        Route::patch('/therapy-requests/{therapyRequest}', [TherapyRequestController::class, 'update'])->name('therapy.update');
        Route::get('/therapy-appointments', [AdminTherapyAppointmentController::class, 'index'])->name('therapy-appointments.index');
        Route::patch('/therapy-appointments/{therapyAppointment}', [AdminTherapyAppointmentController::class, 'update'])->name('therapy-appointments.update');
    });

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
