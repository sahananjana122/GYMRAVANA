<?php

use App\Http\Controllers\AboutController;
use App\Http\Controllers\Admin\AiReadinessController;
use App\Http\Controllers\Admin\BookingManagementController;
use App\Http\Controllers\Admin\EventManagementController;
use App\Http\Controllers\Admin\FinanceController;
use App\Http\Controllers\Admin\GamificationController;
use App\Http\Controllers\Admin\MasterGateController as AdminMasterGateController;
use App\Http\Controllers\Admin\MembershipTierController as AdminMembershipTierController;
use App\Http\Controllers\Admin\NoticeManagementController;
use App\Http\Controllers\Admin\NotificationActivityController;
use App\Http\Controllers\Admin\OrderManagementController;
use App\Http\Controllers\Admin\ProductManagementController;
use App\Http\Controllers\Admin\ServiceManagementController;
use App\Http\Controllers\Admin\TherapistAccountController;
use App\Http\Controllers\Admin\TherapyAppointmentController as AdminTherapyAppointmentController;
use App\Http\Controllers\Admin\TrainerApplicationController;
use App\Http\Controllers\Admin\TrainerWorkController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\BodyMeasurementController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\GroupProgramController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Member\MasterGateController as MemberMasterGateController;
use App\Http\Controllers\Member\MissionController as MemberMissionController;
use App\Http\Controllers\Member\PortalController as MemberPortalController;
use App\Http\Controllers\Member\ProgressPhotoController;
use App\Http\Controllers\MembershipController;
use App\Http\Controllers\NoticeBoardController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\Therapist\AppointmentController as TherapistAppointmentController;
use App\Http\Controllers\TherapyFinderController;
use App\Http\Controllers\TherapyRequestController;
use App\Http\Controllers\Trainer\BookingController as TrainerBookingController;
use App\Http\Controllers\Trainer\LibraryController as TrainerLibraryController;
use App\Http\Controllers\Trainer\MemberPlanController as TrainerMemberPlanController;
use App\Http\Controllers\Trainer\MonthlyTrackerController as TrainerMonthlyTrackerController;
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
Route::get('/events', [EventController::class, 'index'])->name('events.index');
Route::get('/notice-board', [NoticeBoardController::class, 'index'])->name('notices.index');
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
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::patch('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');

    Route::middleware('role:member')->group(function () {
        Route::get('/trainers/{trainerProfile}/book', [TrainerDirectoryController::class, 'bookingForm'])->name('trainers.book');
        Route::post('/trainers/{trainerProfile}/book', [TrainerDirectoryController::class, 'book'])->name('trainers.book.store');
        Route::post('/services/{service}/enroll', [ServiceController::class, 'enroll'])->name('member.services.enroll');

        Route::prefix('member')->name('member.')->group(function () {
            Route::get('/dashboard', [DashboardController::class, 'member'])->name('dashboard');
            Route::get('/level-and-xp', [MemberPortalController::class, 'progression'])->name('progression.index');
            Route::get('/quests', [MemberMissionController::class, 'index'])->name('missions.index');
            Route::post('/quests/{gamificationMission}/join', [MemberMissionController::class, 'join'])->name('missions.join');
            Route::get('/master-gate', [MemberMasterGateController::class, 'index'])->name('master-gate.index');
            Route::post('/master-gate/applications', [MemberMasterGateController::class, 'store'])->name('master-gate.applications.store');
            Route::patch('/master-gate/applications/{masterGateApplication}/withdraw', [MemberMasterGateController::class, 'withdraw'])->name('master-gate.applications.withdraw');
            Route::get('/progress', [MemberPortalController::class, 'progress'])->name('progress.index');
            Route::get('/library', [MemberPortalController::class, 'library'])->name('library.index');
            Route::get('/meal-plan', [MemberPortalController::class, 'mealPlan'])->name('meal-plan.index');
            Route::get('/schedules', [MemberPortalController::class, 'schedules'])->name('schedules.index');
            Route::patch('/progress-photos', [ProgressPhotoController::class, 'update'])->name('progress-photos.update');
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
        Route::post('/bookings/{trainerBooking}/reminder', [TrainerBookingController::class, 'remind'])->middleware('throttle:3,1')->name('bookings.remind');
        Route::get('/plans', [TrainerMemberPlanController::class, 'index'])->name('plans.index');
        Route::get('/plans/create', [TrainerMemberPlanController::class, 'create'])->name('plans.create');
        Route::post('/plans', [TrainerMemberPlanController::class, 'store'])->name('plans.store');
        Route::get('/plans/{memberPlan}', [TrainerMemberPlanController::class, 'show'])->name('plans.show');
        Route::get('/plans/{memberPlan}/edit', [TrainerMemberPlanController::class, 'edit'])->name('plans.edit');
        Route::patch('/plans/{memberPlan}', [TrainerMemberPlanController::class, 'update'])->name('plans.update');
        Route::get('/tracker', [TrainerMonthlyTrackerController::class, 'index'])->name('tracker.index');
        Route::put('/tracker/{member}', [TrainerMonthlyTrackerController::class, 'update'])->name('tracker.update');
        Route::get('/library', [TrainerLibraryController::class, 'index'])->name('library.index');
    });

    Route::prefix('therapist')->name('therapist.')->middleware('role:therapist')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'therapist'])->name('dashboard');
        Route::get('/appointments', [TherapistAppointmentController::class, 'index'])->name('appointments.index');
        Route::patch('/appointments/{therapyAppointment}', [TherapistAppointmentController::class, 'update'])->name('appointments.update');
        Route::post('/appointments/{therapyAppointment}/reminder', [TherapistAppointmentController::class, 'remind'])->middleware('throttle:3,1')->name('appointments.remind');
    });

    Route::prefix('admin')->name('admin.')->middleware('role:admin')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'admin'])->name('dashboard');
        Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
        Route::patch('/users/{user}/role', [AdminUserController::class, 'updateRole'])->name('users.role');

        Route::get('/trainers', [TrainerApplicationController::class, 'index'])->name('trainers.index');
        Route::patch('/trainers/{trainerProfile}', [TrainerApplicationController::class, 'update'])->name('trainers.update');
        Route::get('/therapists', [TherapistAccountController::class, 'index'])->name('therapists.index');
        Route::post('/therapists', [TherapistAccountController::class, 'store'])->name('therapists.store');
        Route::patch('/therapists/{therapySpecialist}', [TherapistAccountController::class, 'update'])->name('therapists.update');
        Route::delete('/therapists/{therapySpecialist}', [TherapistAccountController::class, 'destroy'])->name('therapists.destroy');
        Route::get('/notification-activity', [NotificationActivityController::class, 'index'])->name('notifications.index');
        Route::get('/memberships', [AdminMembershipTierController::class, 'index'])->name('memberships.index');
        Route::post('/memberships', [AdminMembershipTierController::class, 'store'])->name('memberships.store');
        Route::patch('/memberships/{membershipTier}', [AdminMembershipTierController::class, 'update'])->name('memberships.update');
        Route::patch('/members/{user}/tier', [AdminMembershipTierController::class, 'assign'])->name('members.tier');
        Route::get('/services', [ServiceManagementController::class, 'index'])->name('services.index');
        Route::post('/services', [ServiceManagementController::class, 'store'])->name('services.store');
        Route::patch('/services/{service}', [ServiceManagementController::class, 'update'])->name('services.update');
        Route::get('/events', [EventManagementController::class, 'index'])->name('events.index');
        Route::post('/events', [EventManagementController::class, 'store'])->name('events.store');
        Route::patch('/events/{event}', [EventManagementController::class, 'update'])->name('events.update');
        Route::delete('/events/{event}', [EventManagementController::class, 'destroy'])->name('events.destroy');
        Route::get('/gamification', [GamificationController::class, 'index'])->name('gamification.index');
        Route::post('/gamification/missions', [GamificationController::class, 'storeMission'])->name('gamification.missions.store');
        Route::patch('/gamification/missions/{gamificationMission}', [GamificationController::class, 'updateMission'])->name('gamification.missions.update');
        Route::delete('/gamification/missions/{gamificationMission}', [GamificationController::class, 'destroyMission'])->name('gamification.missions.destroy');
        Route::post('/gamification/achievements', [GamificationController::class, 'storeAchievement'])->name('gamification.achievements.store');
        Route::patch('/gamification/achievements/{achievement}', [GamificationController::class, 'updateAchievement'])->name('gamification.achievements.update');
        Route::delete('/gamification/achievements/{achievement}', [GamificationController::class, 'destroyAchievement'])->name('gamification.achievements.destroy');
        Route::get('/master-gate', [AdminMasterGateController::class, 'index'])->name('master-gate.index');
        Route::patch('/master-gate/applications/{masterGateApplication}', [AdminMasterGateController::class, 'decide'])->name('master-gate.applications.decide');
        Route::get('/ai-readiness', [AiReadinessController::class, 'index'])->name('ai-readiness.index');
        Route::post('/ai-readiness/members/{member}/predict', [AiReadinessController::class, 'predict'])
            ->middleware('throttle:5,1')
            ->name('ai-readiness.members.predict');
        Route::get('/notices', [NoticeManagementController::class, 'index'])->name('notices.index');
        Route::get('/notices/create', [NoticeManagementController::class, 'create'])->name('notices.create');
        Route::post('/notices', [NoticeManagementController::class, 'store'])->name('notices.store');
        Route::get('/notices/{notice}/edit', [NoticeManagementController::class, 'edit'])->name('notices.edit');
        Route::patch('/notices/{notice}', [NoticeManagementController::class, 'update'])->name('notices.update');
        Route::delete('/notices/{notice}', [NoticeManagementController::class, 'destroy'])->name('notices.destroy');
        Route::get('/products', [ProductManagementController::class, 'index'])->name('products.index');
        Route::post('/product-categories', [ProductManagementController::class, 'storeCategory'])->name('product-categories.store');
        Route::post('/products', [ProductManagementController::class, 'store'])->name('products.store');
        Route::patch('/products/{product}', [ProductManagementController::class, 'update'])->name('products.update');
        Route::get('/orders', [OrderManagementController::class, 'index'])->name('orders.index');
        Route::patch('/orders/{order}', [OrderManagementController::class, 'update'])->name('orders.update');
        Route::get('/finance', [FinanceController::class, 'index'])->name('finance.index');
        Route::get('/finance/export', [FinanceController::class, 'export'])->name('finance.export');
        Route::post('/finance/transactions', [FinanceController::class, 'storeTransaction'])->name('finance.transactions.store');
        Route::patch('/finance/transactions/{financialTransaction}', [FinanceController::class, 'updateTransaction'])->name('finance.transactions.update');
        Route::delete('/finance/transactions/{financialTransaction}', [FinanceController::class, 'destroyTransaction'])->name('finance.transactions.destroy');
        Route::post('/finance/categories', [FinanceController::class, 'storeCategory'])->name('finance.categories.store');
        Route::patch('/finance/categories/{financeCategory}', [FinanceController::class, 'updateCategory'])->name('finance.categories.update');
        Route::get('/bookings', [BookingManagementController::class, 'index'])->name('bookings.index');
        Route::patch('/bookings/{trainerBooking}', [BookingManagementController::class, 'update'])->name('bookings.update');
        Route::post('/bookings/{trainerBooking}/reminder', [BookingManagementController::class, 'remind'])->middleware('throttle:3,1')->name('bookings.remind');
        Route::get('/trainer-work', [TrainerWorkController::class, 'index'])->name('trainer-work.index');
        Route::get('/therapy-requests', [TherapyRequestController::class, 'manage'])->name('therapy.index');
        Route::patch('/therapy-requests/{therapyRequest}', [TherapyRequestController::class, 'update'])->name('therapy.update');
        Route::get('/therapy-appointments', [AdminTherapyAppointmentController::class, 'index'])->name('therapy-appointments.index');
        Route::patch('/therapy-appointments/{therapyAppointment}', [AdminTherapyAppointmentController::class, 'update'])->name('therapy-appointments.update');
        Route::post('/therapy-appointments/{therapyAppointment}/reminder', [AdminTherapyAppointmentController::class, 'remind'])->middleware('throttle:3,1')->name('therapy-appointments.remind');
    });

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
