<?php

use App\Http\Controllers\Finances\CommitmentAmountController;
use App\Http\Controllers\Finances\CommitmentController;
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
    Route::post('finances/bills', [CommitmentController::class, 'store'])->name('finances.commitments.store');
    Route::patch('finances/bills/{commitment}', [CommitmentController::class, 'update'])->name('finances.commitments.update');
    Route::post('finances/bills/{commitment}/archive', [CommitmentController::class, 'archive'])->name('finances.commitments.archive');
    Route::delete('finances/bills/{commitment}/archive', [CommitmentController::class, 'unarchive'])->name('finances.commitments.unarchive');
    Route::put('finances/bills/{commitment}/amounts', [CommitmentAmountController::class, 'update'])->name('finances.commitments.amounts.update');
    Route::inertia('tasks', 'Tasks')->name('tasks');
    Route::inertia('projects', 'Projects')->name('projects');
    Route::inertia('pets', 'Pets')->name('pets');
});

require __DIR__.'/settings.php';
