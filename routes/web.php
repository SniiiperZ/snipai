<?php

use App\Http\Controllers\AskController;
use App\Http\Controllers\ConversationController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});
Route::middleware(['auth:sanctum', config('jetstream.auth_session'), 'verified'])->group(function () {
    Route::get('/ask', [AskController::class, 'index'])->name('ask.index');
    Route::post('/ask/{conversation}', [AskController::class, 'ask'])->name('ask');

    Route::resource('conversations', ConversationController::class);
    Route::get('/conversations/{conversation}/messages', [ConversationController::class, 'messages'])
        ->name('conversations.messages');
    Route::post('/conversations/{conversation}/generate-title', [ConversationController::class, 'generateTitle'])
        ->name('conversations.generate-title');
});
