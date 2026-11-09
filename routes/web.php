<?php

use App\Http\Controllers\Finances\DeletedExpenseController;
use App\Http\Controllers\Finances\ExpenseController;
use App\Http\Controllers\Finances\FinancesController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
    Route::get('finances', [FinancesController::class, 'index'])->name('finances');
    Route::post('finances/expenses', [ExpenseController::class, 'store'])->name('finances.expenses.store');
    Route::patch('finances/expenses/{expense}', [ExpenseController::class, 'update'])->name('finances.expenses.update');
    Route::delete('finances/expenses/{expense}', [ExpenseController::class, 'destroy'])->name('finances.expenses.destroy');
    Route::post('finances/deleted-expenses/{deletedExpense}/restore', [DeletedExpenseController::class, 'restore'])->name('finances.deleted-expenses.restore');
    Route::inertia('tasks', 'Tasks')->name('tasks');
    Route::inertia('projects', 'Projects')->name('projects');
    Route::inertia('pets', 'Pets')->name('pets');
});

require __DIR__.'/settings.php';
