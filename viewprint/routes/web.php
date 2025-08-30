<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('welcome');
})->name('welcome');

// Temporary test route for Niivue setup verification
Route::get('/test-niivue', function () {
    return view('test-niivue');
})->name('test.niivue');

// Future workspace routes (commented for now)
// Route::get('/workspaces', [WorkspaceController::class, 'index'])->name('workspaces.index');
// Route::get('/workspaces/{workspace}', [WorkspaceController::class, 'show'])->name('workspaces.show');
// Workspace routes
Route::prefix('workspace')->name('workspace.')->group(function () {
    // Store new workspace
    Route::post('/', function () {
        // For now, create with hardcoded user ID
        $workspace = \App\Models\Workspace::create([
            'name' => request('name'),
            'description' => request('description'),
            'created_by' => 1, // TODO: Use auth()->id() when auth is implemented
        ]);

        // Touch last accessed
        $workspace->touchLastAccessed();

        return redirect()->route('workspace.show', $workspace);
    })->name('store');

    // Show workspace
//    Route::get('/{workspace}', function (\App\Models\Workspace $workspace) {
//        // For now, just return a placeholder
//        return view('workspace.show', compact('workspace'));
//    })->name('show');
    Route::get('/{workspace}', \App\Livewire\WorkspaceViewer::class)->name('show');
});
