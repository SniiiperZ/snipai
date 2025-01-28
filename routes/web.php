<?php

use App\Http\Controllers\AskController;
use App\Http\Controllers\AssistantBehaviorController;
use App\Http\Controllers\ConversationController;
use App\Http\Controllers\CustomCommandController;
use App\Http\Controllers\UserInstructionController;
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

    Route::post('/ask/{conversation}/stream', [AskController::class, 'streamMessage'])
        ->name('ask.stream');

    Route::get('/commands', [CustomCommandController::class, 'index'])->name('commands.index');
    Route::post('/commands', [CustomCommandController::class, 'store'])->name('commands.store');
    Route::delete('/commands/{command}', [CustomCommandController::class, 'destroy'])->name('commands.destroy');

    Route::get('/behavior', [AssistantBehaviorController::class, 'index'])->name('behavior.index');
    Route::post('/behavior', [AssistantBehaviorController::class, 'store'])->name('behavior.store');

    Route::get('/instructions', [UserInstructionController::class, 'index'])->name('instructions.index');
    Route::post('/instructions', [UserInstructionController::class, 'store'])->name('instructions.store');

    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    Route::resource('conversations', ConversationController::class);
    Route::get('/conversations/{conversation}/messages', [ConversationController::class, 'messages'])
        ->name('conversations.messages');
    Route::post('/conversations/{conversation}/generate-title', [ConversationController::class, 'generateTitle'])
        ->name('conversations.generate-title');

    Route::post('/conversations/{conversation}/stream', [AskController::class, 'streamMessage'])->name('ask.stream');
});
