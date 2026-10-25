<?php

use App\Http\Controllers\CourseController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NoteController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\VaultFileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', DashboardController::class)->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware('auth')->group(function () {

    Route::resource('notes', NoteController::class);
    Route::resource('courses', CourseController::class);
    Route::get('/vault', [VaultFileController::class, 'index'])->name('vault.index');
    Route::get('/vault/create', [VaultFileController::class, 'create'])->name('vault.create');
    Route::post('/vault', [VaultFileController::class, 'store'])->name('vault.store');
    Route::patch('/vault/{vaultFile}/course', [VaultFileController::class, 'updateCourse'])->name('vault.course.update');
    Route::get('/vault/{vaultFile}/download', [VaultFileController::class, 'download'])->name('vault.download');
    Route::delete('/vault/{vaultFile}', [VaultFileController::class, 'destroy'])->name('vault.destroy');
});

require __DIR__.'/auth.php';
