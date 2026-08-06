<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;

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
});

Route::get('/phpinfo', function () {
  echo phpinfo();
});

Route::get('/clear-all', function () {
   Artisan::call('optimize:clear');
   return 'Cache cleared';
});