<?php

use App\Http\Controllers\CourseController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NoteController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\StarredController;
use App\Http\Controllers\TrashController;
use App\Http\Controllers\VaultFileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PersonalVaultController;

Route::get('/', function () {
    return redirect()->route('login');
});


Route::get('/dashboard', DashboardController::class)->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware('auth')->group(function () {

    Route::get('/search', SearchController::class)->name('search.index');
    Route::get('/starred', StarredController::class)->name('starred.index');
    Route::get('/trash', [TrashController::class, 'index'])->name('trash.index');
    Route::patch('/trash/notes/{note}/restore', [TrashController::class, 'restoreNote'])->name('trash.notes.restore');
    Route::delete('/trash/notes/{note}', [TrashController::class, 'destroyNote'])->name('trash.notes.destroy');
    Route::patch('/trash/files/{vaultFile}/restore', [TrashController::class, 'restoreFile'])->name('trash.files.restore');
    Route::delete('/trash/files/{vaultFile}', [TrashController::class, 'destroyFile'])->name('trash.files.destroy');

    Route::resource('notes', NoteController::class);
    Route::patch('/notes/{note}/star', [NoteController::class, 'toggleStar'])->name('notes.star');
    Route::resource('courses', CourseController::class);
    Route::get('/vault', [VaultFileController::class, 'index'])->name('vault.index');
    Route::get('/vault/create', [VaultFileController::class, 'create'])->name('vault.create');
    Route::post('/vault', [VaultFileController::class, 'store'])->name('vault.store');
    Route::patch('/vault/{vaultFile}/course', [VaultFileController::class, 'updateCourse'])->name('vault.course.update');
    Route::patch('/vault/{vaultFile}/star', [VaultFileController::class, 'toggleStar'])->name('vault.star');
    Route::get('/vault/{vaultFile}/download', [VaultFileController::class, 'download'])->name('vault.download');
    Route::delete('/vault/{vaultFile}', [VaultFileController::class, 'destroy'])->name('vault.destroy');
    Route::get('/personal-vault', [PersonalVaultController::class, 'index'])->name('personal-vault.index');
    Route::get('/personal-vault/create', [PersonalVaultController::class, 'create'])->name('personal-vault.create');
    Route::post('/personal-vault', [PersonalVaultController::class, 'store'])->name('personal-vault.store');
    Route::get('/personal-vault/{personalData}/edit', [PersonalVaultController::class, 'edit'])->whereNumber('personalData')->name('personal-vault.edit');
    Route::put('/personal-vault/{personalData}', [PersonalVaultController::class, 'update'])->whereNumber('personalData')->name('personal-vault.update');
    Route::delete('/personal-vault/{personalData}', [PersonalVaultController::class, 'destroy'])->whereNumber('personalData')->name('personal-vault.destroy');
});

require __DIR__.'/auth.php';
