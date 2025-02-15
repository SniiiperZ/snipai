<?php

use App\Http\Controllers\AskController;
use App\Http\Controllers\AssistantBehaviorController;
use App\Http\Controllers\ConversationController;
use App\Http\Controllers\CustomCommandController;
use App\Http\Controllers\UserInstructionController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Pages publiques
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

/*
|--------------------------------------------------------------------------
| Routes protégées
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum', config('jetstream.auth_session'), 'verified'])->group(function () {

    // Dashboard
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    /*
    |--------------------------------------------------------------------------
    | Routes de chat
    |--------------------------------------------------------------------------
    */

    // Interface principale de chat
    Route::get('/ask', [AskController::class, 'index'])->name('ask.index');
    Route::post('/ask/{conversation}', [AskController::class, 'ask'])->name('ask');
    Route::post('/ask/{conversation}/stream', [AskController::class, 'streamMessage'])->name('ask.stream');

    /*
    |--------------------------------------------------------------------------
    | Gestion des conversations
    |--------------------------------------------------------------------------
    */

    Route::resource('conversations', ConversationController::class);
    Route::prefix('conversations')->name('conversations.')->group(function () {
        Route::get('{conversation}/messages', [ConversationController::class, 'messages'])->name('messages');
        Route::post('{conversation}/generate-title', [ConversationController::class, 'generateTitle'])->name('generate-title');
        Route::patch('{conversation}/model', [ConversationController::class, 'updateModel'])->name('update-model');
    });

    /*
    |--------------------------------------------------------------------------
    | Configuration de l'assistant
    |--------------------------------------------------------------------------
    */

    // Commandes personnalisées
    Route::prefix('commands')->name('commands.')->group(function () {
        Route::get('/', [CustomCommandController::class, 'index'])->name('index');
        Route::post('/', [CustomCommandController::class, 'store'])->name('store');
        Route::delete('{command}', [CustomCommandController::class, 'destroy'])->name('destroy');
    });

    // Comportement de l'assistant
    Route::prefix('behavior')->name('behavior.')->group(function () {
        Route::get('/', [AssistantBehaviorController::class, 'index'])->name('index');
        Route::post('/', [AssistantBehaviorController::class, 'store'])->name('store');
    });

    // Instructions utilisateur
    Route::prefix('instructions')->name('instructions.')->group(function () {
        Route::get('/', [UserInstructionController::class, 'index'])->name('index');
        Route::post('/', [UserInstructionController::class, 'store'])->name('store');
    });
});
