<?php

use App\Http\Controllers\ActAsController;
use App\Http\Controllers\AvatarController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\CustomerRoleController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\NoteController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Home');
})->name('home');

Route::get('act-as/options', [ActAsController::class, 'options'])->name('act-as.options');
Route::post('act-as', [ActAsController::class, 'store'])->name('act-as.store');

Route::get('users/mentionable', [UserController::class, 'mentionable'])
    ->name('users.mentionable');
Route::resource('users', UserController::class);
Route::resource('companies', CompanyController::class);
Route::resource('customers', CustomerController::class);
Route::resource('leads', LeadController::class);

Route::post('messages', [MessageController::class, 'store'])->name('messages.store');
Route::put('messages/{message}', [MessageController::class, 'update'])->name('messages.update');
Route::delete('messages/{message}', [MessageController::class, 'destroy'])->name('messages.destroy');

// Static task paths must precede the `tasks/{task}` wildcards below.
Route::post('tasks', [TaskController::class, 'store'])->name('tasks.store');
Route::put('tasks/{task}', [TaskController::class, 'update'])->name('tasks.update');
Route::delete('tasks/{task}', [TaskController::class, 'destroy'])->name('tasks.destroy');

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

Route::post('contacts', [ContactController::class, 'store'])->name('contacts.store');
Route::patch('contacts/{contact}', [ContactController::class, 'update'])->name('contacts.update');
Route::delete('contacts/{contact}', [ContactController::class, 'destroy'])->name('contacts.destroy');
Route::patch('contacts/{contact}/primary', [ContactController::class, 'setPrimary'])->name('contacts.set-primary');

Route::patch('notifications/read-all', [NotificationController::class, 'markAllRead'])
    ->name('notifications.readAll');
Route::patch('notifications/{id}/read', [NotificationController::class, 'markRead'])
    ->name('notifications.read');
