<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Automatically-Generated Web Routes
|--------------------------------------------------------------------------
*/

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

require __DIR__.'/auth.php';

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    // Get recent workspaces for the current user (when auth is implemented)
    $recentWorkspaces = collect(); // For now, empty collection

    return view('welcome', compact('recentWorkspaces'));
})->name('home');

// Workspace routes (to be implemented)
Route::prefix('workspace')->name('workspace.')->group(function () {
    Route::get('/{workspace}', function ($workspace) {
        // Placeholder until Workspace model is created
        return "Workspace {$workspace} view coming soon";
    })->name('show');
});
