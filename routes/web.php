<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FaeController;
use App\Http\Controllers\TaskController;
use App\Services\MonitoringAuth;
use Illuminate\Support\Facades\Route;

// Root redirect
Route::get('/', function () {
    return MonitoringAuth::role() ? redirect()->route('dashboard') : redirect()->route('access');
});

// Authentication routes
Route::get('/access', [AuthController::class, 'showAccess'])->name('access');
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::get('/logout', [AuthController::class, 'logout'])->name('logout');
Route::get('/fae-link/{code}', [AuthController::class, 'directFaeLink'])->name('fae.link');
Route::get('/fae.php', function (\Illuminate\Http\Request $request) {
    if ($request->has('code')) {
        return redirect()->route('fae.link', ['code' => $request->query('code')]);
    }
    return redirect()->route('fae.index');
});

// Authenticated monitoring routes
Route::middleware(['auth.monitoring'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Tasks
    Route::get('/tasks', [TaskController::class, 'index'])->name('tasks.index');
    Route::get('/tasks/timeline', [TaskController::class, 'getTimeline'])->name('tasks.timeline');
    Route::post('/tasks/update-progress/{task}', [TaskController::class, 'updateProgress'])->name('tasks.updateProgress');
    Route::post('/tasks/store-update', [TaskController::class, 'storeUpdate'])->name('tasks.storeUpdate');
    Route::post('/profile/update-photo', [TaskController::class, 'updateProfile'])->name('profile.updatePhoto');

    // Calendar
    Route::get('/calendar', [CalendarController::class, 'index'])->name('calendar.index');
    Route::post('/calendar/book', [CalendarController::class, 'bookAppointment'])->name('calendar.book');

    // Admin only routes
    Route::middleware(['admin.monitoring'])->group(function () {
        // Task management
        Route::post('/tasks', [TaskController::class, 'store'])->name('tasks.store');
        Route::put('/tasks/{task}', [TaskController::class, 'update'])->name('tasks.update');
        Route::delete('/tasks/{task}', [TaskController::class, 'destroy'])->name('tasks.destroy');

        // FAE management
        Route::get('/fae', [FaeController::class, 'index'])->name('fae.index');
        Route::post('/fae', [FaeController::class, 'store'])->name('fae.store');
        Route::put('/fae/{fae}', [FaeController::class, 'update'])->name('fae.update');
        Route::delete('/fae/{fae}', [FaeController::class, 'destroy'])->name('fae.destroy');

        // Calendar Admin features
        Route::post('/calendar/appointment-status', [CalendarController::class, 'updateAppointmentStatus'])->name('calendar.updateAppointmentStatus');
        Route::post('/calendar/events', [CalendarController::class, 'storeEvent'])->name('calendar.events.store');
        Route::post('/calendar/events/delete', [CalendarController::class, 'destroyEvent'])->name('calendar.events.destroy');
    });
});
