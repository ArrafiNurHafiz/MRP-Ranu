<?php

declare(strict_types=1);

use App\Livewire\Access\AccessMatrix;
use App\Livewire\Auth\Login;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', Login::class)->name('login');
});

Route::post('/logout', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();

    return redirect()->route('login');
})->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/', fn () => view('home'))->name('home');
    Route::get('/access-matrix', AccessMatrix::class)
        ->middleware('can:access.divisions.manage')
        ->name('access.matrix');
});
