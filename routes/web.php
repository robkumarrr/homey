<?php

use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
    Route::inertia('finances', 'Finances')->name('finances');
    Route::inertia('tasks', 'Tasks')->name('tasks');
    Route::inertia('projects', 'Projects')->name('projects');
    Route::inertia('pets', 'Pets')->name('pets');
});

require __DIR__.'/settings.php';
