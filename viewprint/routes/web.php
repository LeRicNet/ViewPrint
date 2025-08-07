<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('welcome');
});

// Temporary test route for Niivue setup verification
Route::get('/test-niivue', function () {
    return view('test-niivue');
})->name('test.niivue');

// Future workspace routes (commented for now)
// Route::get('/workspaces', [WorkspaceController::class, 'index'])->name('workspaces.index');
// Route::get('/workspaces/{workspace}', [WorkspaceController::class, 'show'])->name('workspaces.show');
