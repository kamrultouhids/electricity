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

    // Customer Management (view for all; add/edit/delete restricted)
    Route::get('/customers-search', [CustomerController::class, 'search'])->name('customers.search');
    Route::middleware('can:manage-customers')->group(function () {
        Route::get('/customers/{customer}/delete', [CustomerController::class, 'destroy'])->name('customers.delete');
        Route::resource('customers', CustomerController::class)->only(['create', 'store', 'edit', 'update', 'destroy']);
    });
    Route::resource('customers', CustomerController::class)->only(['index', 'show']);

    // User Management (manager has no access)
    Route::middleware('can:manage-users')->group(function () {
        Route::get('/users/{user}/delete', [UserController::class, 'destroy'])->name('users.delete');
        Route::resource('users', UserController::class);
    });

    // Tariff / Per Unit Rate Settings (manager has no access)
    Route::middleware('can:rate-settings')->group(function () {
        Route::get('/tariffs', [TariffController::class, 'index'])->name('tariffs.index');
        Route::put('/tariffs', [TariffController::class, 'update'])->name('tariffs.update');
    });

    // Meter Reading (collector has no access)
    Route::middleware('can:access-meter-readings')->group(function () {
        Route::get('/meter-readings/{meterReading}/delete', [MeterReadingController::class, 'destroy'])->name('meter-readings.delete');
        Route::resource('meter-readings', MeterReadingController::class)->parameters([
            'meter-readings' => 'meterReading',
        ]);
    });

    // Billing — viewing bills is open; generation is restricted
    Route::middleware('can:generate-bills')->group(function () {
        Route::get('/bills/pending', [BillController::class, 'pending'])->name('bills.pending');
        Route::post('/bills/generate-all', [BillController::class, 'generateAll'])->name('bills.generate-all');
        Route::get('/bills/generate/{meterReading}', [BillController::class, 'preview'])->name('bills.preview');
        Route::post('/bills/generate/{meterReading}', [BillController::class, 'store'])->name('bills.store');
    });
    Route::middleware('can:view-bills')->group(function () {
        Route::get('/bills', [BillController::class, 'index'])->name('bills.index');
        Route::get('/bills/{bill}', [BillController::class, 'show'])->name('bills.show');
    });

    // Payment Collection
    Route::get('/payments/due', [PaymentController::class, 'dueList'])->middleware('can:view-due-list')->name('payments.due');
    Route::middleware('can:collect-payments')->group(function () {
        Route::get('/customers/{customer}/pay', [PaymentController::class, 'create'])->name('payments.create');
        Route::post('/customers/{customer}/pay', [PaymentController::class, 'store'])->name('payments.store');
    });
    Route::middleware('can:view-payments')->group(function () {
        Route::get('/payments', [PaymentController::class, 'index'])->name('payments.index');
        Route::get('/payments/{payment}/receipt', [PaymentController::class, 'receipt'])->name('payments.receipt');
    });

    // Expense Management (admin & manager only)
    Route::middleware('can:manage-expenses')->group(function () {
        Route::get('/expenses/profit-loss', [ExpenseController::class, 'profitLoss'])->name('expenses.profit-loss');
        Route::resource('expense-categories', ExpenseCategoryController::class)->except(['show'])->parameters([
            'expense-categories' => 'expenseCategory',
        ]);
        Route::get('/expenses/{expense}/delete', [ExpenseController::class, 'destroy'])->name('expenses.delete');
        Route::resource('expenses', ExpenseController::class)->except(['show']);
    });

    // Report Management
    Route::prefix('reports')->name('reports.')->middleware('can:view-reports')->group(function () {
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

/*
| Customer Portal (separate "customer" guard, login by mobile number)
*/
Route::prefix('customer')->name('portal.')->group(function () {
    Route::get('/login', [\App\Http\Controllers\Portal\AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [\App\Http\Controllers\Portal\AuthController::class, 'login']);
    Route::post('/logout', [\App\Http\Controllers\Portal\AuthController::class, 'logout'])->name('logout');

    // Public bill download (accessible without login)
    Route::get('/bills/{bill}', [\App\Http\Controllers\Portal\DashboardController::class, 'bill'])->name('bills.show');

    Route::middleware('auth:customer')->group(function () {
        Route::get('/', [\App\Http\Controllers\Portal\DashboardController::class, 'index'])->name('dashboard');
        Route::get('/payments/{payment}/receipt', [\App\Http\Controllers\Portal\DashboardController::class, 'receipt'])->name('payments.receipt');
    });
});

Route::get('/phpinfo', function () {
  echo phpinfo();
});

Route::get('/clear-all', function () {
   Artisan::call('optimize:clear');
   return 'Cache cleared';
});