<?php

use App\Http\Controllers\ClientController;
use App\Http\Controllers\InquiryController;
use App\Http\Controllers\MilestoneController;
use App\Http\Controllers\ProjectController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';

/*
 * The studio's internal admin panel. Everything here is behind auth: every
 * signed-in user is staff, and the product has no roles.
 */
Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('clients', ClientController::class)->except(['show']);

    Route::resource('projects', ProjectController::class);

    Route::post('projects/{project}/milestones', [MilestoneController::class, 'store'])
        ->name('projects.milestones.store');
    Route::put('projects/{project}/milestones/{milestone}', [MilestoneController::class, 'update'])
        ->name('projects.milestones.update');
    Route::put('projects/{project}/milestones/{milestone}/move/{direction}', [MilestoneController::class, 'move'])
        ->name('projects.milestones.move');
    Route::delete('projects/{project}/milestones/{milestone}', [MilestoneController::class, 'destroy'])
        ->name('projects.milestones.destroy');

    Route::get('inquiries', [InquiryController::class, 'index'])->name('inquiries.index');
    Route::put('inquiries/{inquiry}/status', [InquiryController::class, 'updateStatus'])
        ->name('inquiries.status');
    Route::post('inquiries/{inquiry}/convert', [InquiryController::class, 'convert'])
        ->name('inquiries.convert');
});
