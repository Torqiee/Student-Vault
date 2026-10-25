<?php

use App\Http\Controllers\CourseController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NoteController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\VaultFileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PersonalVaultController;

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
    Route::get('/personal-vault', [PersonalVaultController::class, 'index'])->name('personal-vault.index');
    Route::get('/personal-vault/create', [PersonalVaultController::class, 'create'])->name('personal-vault.create');
    Route::post('/personal-vault', [PersonalVaultController::class, 'store'])->name('personal-vault.store');
    Route::get('/personal-vault/{personalData}/edit', [PersonalVaultController::class, 'edit'])->whereNumber('personalData')->name('personal-vault.edit');
    Route::put('/personal-vault/{personalData}', [PersonalVaultController::class, 'update'])->whereNumber('personalData')->name('personal-vault.update');
    Route::delete('/personal-vault/{personalData}', [PersonalVaultController::class, 'destroy'])->whereNumber('personalData')->name('personal-vault.destroy');
});

require __DIR__.'/auth.php';
