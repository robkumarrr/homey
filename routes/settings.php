<?php

use App\Http\Controllers\Settings\CategoryController;
use App\Http\Controllers\Settings\HouseController;
use App\Http\Controllers\Settings\PaymentMethodController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\SecurityController;
use Illuminate\Auth\Middleware\RequirePassword;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', '/settings/profile');

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('settings/security', [SecurityController::class, 'edit'])
        ->middleware(RequirePassword::class)
        ->name('security.edit');

    Route::put('settings/password', [SecurityController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('user-password.update');

    Route::get('settings/house', [HouseController::class, 'edit'])->name('house.edit');
    Route::patch('settings/house', [HouseController::class, 'update'])->name('house.update');

    Route::get('settings/categories', [CategoryController::class, 'index'])->name('categories.index');
    Route::post('settings/categories', [CategoryController::class, 'store'])->name('categories.store');
    Route::patch('settings/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
    Route::post('settings/categories/{category}/archive', [CategoryController::class, 'archive'])->name('categories.archive');
    Route::delete('settings/categories/{category}/archive', [CategoryController::class, 'unarchive'])->name('categories.unarchive');

    Route::get('settings/payment-methods', [PaymentMethodController::class, 'index'])->name('payment-methods.index');
    Route::post('settings/payment-methods', [PaymentMethodController::class, 'store'])->name('payment-methods.store');
    Route::patch('settings/payment-methods/{paymentMethod}', [PaymentMethodController::class, 'update'])->name('payment-methods.update');
    Route::post('settings/payment-methods/{paymentMethod}/archive', [PaymentMethodController::class, 'archive'])->name('payment-methods.archive');
    Route::delete('settings/payment-methods/{paymentMethod}/archive', [PaymentMethodController::class, 'unarchive'])->name('payment-methods.unarchive');

    Route::inertia('settings/appearance', 'settings/Appearance')->name('appearance.edit');
});

Route::get('.well-known/passkey-endpoints', function () {
    return response()->json([
        'enroll' => route('security.edit'),
        'manage' => route('security.edit'),
    ]);
})->name('well-known.passkeys');
