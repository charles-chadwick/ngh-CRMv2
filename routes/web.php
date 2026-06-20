<?php

use App\Http\Controllers\AvatarController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\CustomerRoleController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\NoteController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Home');
})->name('home');

Route::get('users/mentionable', [UserController::class, 'mentionable'])
    ->name('users.mentionable');
Route::resource('users', UserController::class);
Route::resource('companies', CompanyController::class);
Route::resource('customers', CustomerController::class);
Route::resource('leads', LeadController::class);

Route::prefix('settings')->name('settings.')->group(function () {
    Route::get('/', fn () => Inertia::render('Settings/Index'))->name('index');

    Route::resource('customer-roles', CustomerRoleController::class)
        ->except(['show']);
});

Route::post('avatar', [AvatarController::class, 'store'])->name('avatar.store');
Route::delete('avatar', [AvatarController::class, 'destroy'])->name('avatar.destroy');

Route::post('notes', [NoteController::class, 'store'])->name('notes.store');
Route::put('notes/{note}', [NoteController::class, 'update'])->name('notes.update');
Route::delete('notes/{note}', [NoteController::class, 'destroy'])->name('notes.destroy');

Route::patch('notifications/read-all', [NotificationController::class, 'markAllRead'])
    ->name('notifications.readAll');
Route::patch('notifications/{id}/read', [NotificationController::class, 'markRead'])
    ->name('notifications.read');
