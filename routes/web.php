<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\FaeController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\TaskController;
use App\Services\MonitoringAuth;
use Illuminate\Support\Facades\Route;

// Public Landing Page with interactive calendar
Route::get('/', [LandingController::class, 'index'])->name('landing');

// Authentication routes
Route::get('/access', [AuthController::class, 'showAccess'])->name('access');
Route::get('/login', function () {
    return redirect()->route('access');
});
Route::post('/login', [AuthController::class, 'login'])->name('login')->middleware('throttle:monitoring-login');
Route::get('/logout', [AuthController::class, 'logout'])->name('logout');
Route::get('/forgot-admin-key', function () {
    return redirect()->route('access');
});
Route::post('/forgot-admin-key', [AuthController::class, 'forgotKey'])->name('forgot.admin.key')->middleware('throttle:3,5');
Route::get('/fae-link/{code}', [AuthController::class, 'directFaeLink'])->name('fae.link')->middleware('throttle:monitoring-login');
Route::get('/fae.php', function (\Illuminate\Http\Request $request) {
    if ($request->has('code')) {
        return redirect()->route('fae.link', ['code' => $request->query('code')]);
    }
    return redirect()->route('fae.index');
});

// Public self-registration
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register'])->name('register.store')->middleware('throttle:10,5');

// Authenticated monitoring routes
Route::middleware(['auth.monitoring'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Tasks
    Route::get('/tasks', [TaskController::class, 'index'])->name('tasks.index');
    Route::get('/tasks/timeline', [TaskController::class, 'getTimeline'])->name('tasks.timeline');
    Route::get('/tasks/{task}/report', [TaskController::class, 'report'])->name('tasks.report');
    Route::get('/tasks-export-report', [TaskController::class, 'exportSummaryReport'])->name('tasks.exportReport');
    Route::post('/tasks/update-progress/{task}', [TaskController::class, 'updateProgress'])->name('tasks.updateProgress');
    Route::post('/tasks/store-update', [TaskController::class, 'storeUpdate'])->name('tasks.storeUpdate');
    Route::post('/profile/update-photo', [ProfileController::class, 'updatePhoto'])->name('profile.updatePhoto');

    // Authenticated file serving (private uploads)
    Route::get('/files/{path}', [FileController::class, 'show'])
        ->where('path', '.*')
        ->name('files.show');

    // Calendar
    Route::get('/calendar', [CalendarController::class, 'index'])->name('calendar.index');
    Route::post('/calendar/book', [CalendarController::class, 'bookAppointment'])->name('calendar.book');
    Route::post('/calendar/cancel', [CalendarController::class, 'cancelAppointment'])->name('calendar.cancelAppointment');
    Route::post('/calendar/appointment/delete', [CalendarController::class, 'destroyAppointment'])->name('calendar.appointment.destroy');

    // Admin only routes
    Route::middleware(['admin.monitoring'])->group(function () {
        // Task management
        Route::post('/tasks', [TaskController::class, 'store'])->name('tasks.store');
        Route::put('/tasks/{task}', [TaskController::class, 'update'])->name('tasks.update');
        Route::delete('/tasks/{task}', [TaskController::class, 'destroy'])->name('tasks.destroy');

        // Department management
        Route::get('/departments', [DepartmentController::class, 'index'])->name('departments.index');
        Route::post('/departments', [DepartmentController::class, 'store'])->name('departments.store');
        Route::put('/departments/{department}', [DepartmentController::class, 'update'])->name('departments.update');
        Route::delete('/departments/{department}', [DepartmentController::class, 'destroy'])->name('departments.destroy');

        // FAE / Contact management
        Route::get('/fae', [FaeController::class, 'index'])->name('fae.index');
        Route::post('/fae', [FaeController::class, 'store'])->name('fae.store');
        Route::put('/fae/{fae}', [FaeController::class, 'update'])->name('fae.update');
        Route::delete('/fae/{fae}', [FaeController::class, 'destroy'])->name('fae.destroy');
        Route::post('/fae/{fae}/approve', [FaeController::class, 'approve'])->name('fae.approve');
        Route::post('/fae/{fae}/reject', [FaeController::class, 'reject'])->name('fae.reject');

        // Calendar Admin features
        Route::post('/calendar/appointment-status', [CalendarController::class, 'updateAppointmentStatus'])->name('calendar.updateAppointmentStatus');
        Route::post('/calendar/events', [CalendarController::class, 'storeEvent'])->name('calendar.events.store');
        Route::put('/calendar/events/{event}', [CalendarController::class, 'updateEvent'])->name('calendar.events.update');
        Route::post('/calendar/events/delete', [CalendarController::class, 'destroyEvent'])->name('calendar.events.destroy');

        // FAE Direct Email
        Route::post('/fae/{fae}/send-email', [FaeController::class, 'sendEmail'])->name('fae.sendEmail');

        // Settings
        Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
        Route::post('/settings/update-name', [SettingsController::class, 'updateName'])->name('settings.updateName');
        Route::post('/settings/update-key', [SettingsController::class, 'updateKey'])->name('settings.updateKey');
        Route::post('/settings/update-email', [SettingsController::class, 'updateEmail'])->name('settings.updateEmail');
        Route::post('/settings/test-smtp', [SettingsController::class, 'testSmtp'])->name('settings.testSmtp');
    });
});

