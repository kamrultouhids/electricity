<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\BillController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\MeterReadingController;
use App\Http\Controllers\TariffController;
use App\Http\Controllers\UserController;

Route::get('/register', function () {
   abort(404);
 });
Route::get('/', [LoginController::class, 'showLoginForm'])->name('login');

Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);

Route::middleware(['auth'])->group(function () {
   Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

   // Logout
   Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    // Change Password
    Route::get('/change-password', [App\Http\Controllers\ProfileController::class, 'changePassword'])->name('password.change');
    Route::post('/change-password', [App\Http\Controllers\ProfileController::class, 'updatePassword'])->name('update.password');

    // Customer Management
    Route::get('/customers-search', [CustomerController::class, 'search'])->name('customers.search');
    Route::get('/customers/{customer}/delete', [CustomerController::class, 'destroy'])->name('customers.delete');
    Route::resource('customers', CustomerController::class);

    // User Management
    Route::get('/users/{user}/delete', [UserController::class, 'destroy'])->name('users.delete');
    Route::resource('users', UserController::class);

    // Tariff / Per Unit Rate Settings
    Route::get('/tariffs', [TariffController::class, 'index'])->name('tariffs.index');
    Route::put('/tariffs', [TariffController::class, 'update'])->name('tariffs.update');

    // Meter Reading
    Route::get('/meter-readings/{meterReading}/delete', [MeterReadingController::class, 'destroy'])->name('meter-readings.delete');
    Route::resource('meter-readings', MeterReadingController::class)->parameters([
        'meter-readings' => 'meterReading',
    ]);

    // Billing
    Route::get('/bills/pending', [BillController::class, 'pending'])->name('bills.pending');
    Route::get('/bills/generate/{meterReading}', [BillController::class, 'preview'])->name('bills.preview');
    Route::post('/bills/generate/{meterReading}', [BillController::class, 'store'])->name('bills.store');
    Route::get('/bills', [BillController::class, 'index'])->name('bills.index');
    Route::get('/bills/{bill}', [BillController::class, 'show'])->name('bills.show');
});

Route::get('/phpinfo', function () {
  echo phpinfo();
});

Route::get('/clear-all', function () {
   Artisan::call('optimize:clear');
   return 'Cache cleared';
});