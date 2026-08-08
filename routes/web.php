<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\BillController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\ExpenseCategoryController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\MeterReadingController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ReportController;
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

    // Payment Collection
    Route::get('/payments/due', [PaymentController::class, 'dueList'])->name('payments.due');
    Route::get('/payments', [PaymentController::class, 'index'])->name('payments.index');
    Route::get('/customers/{customer}/pay', [PaymentController::class, 'create'])->name('payments.create');
    Route::post('/customers/{customer}/pay', [PaymentController::class, 'store'])->name('payments.store');
    Route::get('/payments/{payment}/receipt', [PaymentController::class, 'receipt'])->name('payments.receipt');

    // Expense Management
    Route::get('/expenses/profit-loss', [ExpenseController::class, 'profitLoss'])->name('expenses.profit-loss');
    Route::resource('expense-categories', ExpenseCategoryController::class)->except(['show'])->parameters([
        'expense-categories' => 'expenseCategory',
    ]);
    Route::get('/expenses/{expense}/delete', [ExpenseController::class, 'destroy'])->name('expenses.delete');
    Route::resource('expenses', ExpenseController::class)->except(['show']);

    // Report Management
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::get('/daily-collection', [ReportController::class, 'dailyCollection'])->name('daily-collection');
        Route::get('/monthly-collection', [ReportController::class, 'monthlyCollection'])->name('monthly-collection');
        Route::get('/customers', [ReportController::class, 'customer'])->name('customers');
        Route::get('/unit-consumption', [ReportController::class, 'unitConsumption'])->name('unit-consumption');
        Route::get('/meter-not-read', [ReportController::class, 'meterNotRead'])->name('meter-not-read');
        Route::get('/outstanding', [ReportController::class, 'outstanding'])->name('outstanding');
        Route::get('/income-expense', [ReportController::class, 'incomeExpense'])->name('income-expense');
    });
});

Route::get('/phpinfo', function () {
  echo phpinfo();
});

Route::get('/clear-all', function () {
   Artisan::call('optimize:clear');
   return 'Cache cleared';
});