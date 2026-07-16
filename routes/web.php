<?php

use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\BookingController as AdminBookingController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Public\BookingController;
use App\Http\Controllers\Public\HomeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public routes (no login required)
|--------------------------------------------------------------------------
*/

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/facilities/{resource:slug}', [BookingController::class, 'show'])->name('facilities.show');
Route::get('/facilities/{resource:slug}/availability', [BookingController::class, 'availability'])->name('facilities.availability');
Route::post('/facilities/{resource:slug}/bookings', [BookingController::class, 'store'])->name('bookings.store');
Route::get('/bookings/{referenceNo}/confirmation', [BookingController::class, 'confirmation'])->name('bookings.confirmation');

/*
|--------------------------------------------------------------------------
| Admin authentication
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {
    Route::get('/admin/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/admin/login', [AuthenticatedSessionController::class, 'store']);
});

Route::middleware('auth')->post('/admin/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

/*
|--------------------------------------------------------------------------
| Admin panel (auth required)
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/bookings', [AdminBookingController::class, 'index'])->name('bookings.index');
    Route::get('/bookings/{booking}', [AdminBookingController::class, 'show'])->name('bookings.show');
    Route::post('/bookings/{booking}/receipt', [AdminBookingController::class, 'uploadReceipt'])->name('bookings.receipt');
    Route::post('/bookings/{booking}/confirm', [AdminBookingController::class, 'confirm'])->name('bookings.confirm');
    Route::post('/bookings/{booking}/cancel', [AdminBookingController::class, 'cancel'])->name('bookings.cancel');
    Route::post('/bookings/{booking}/reject', [AdminBookingController::class, 'reject'])->name('bookings.reject');

    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/export', [ReportController::class, 'export'])->name('reports.export');

    Route::get('/admins', [AdminUserController::class, 'index'])->name('admins.index');
    Route::post('/admins', [AdminUserController::class, 'store'])->name('admins.store');
    Route::delete('/admins/{user}', [AdminUserController::class, 'destroy'])->name('admins.destroy');
});
