<?php

namespace App\Http\Controllers;

use App\Models\AssistantBehavior;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class AssistantBehaviorController extends Controller
{
    /**
     * Affiche la page de configuration du comportement de l'assistant
     */
    public function index(): Response
    {
        Log::info('Accès à la page de configuration du comportement', [
            'user_id' => auth()->id()
        ]);

        return Inertia::render('Behavior/Index', [
            'behavior' => $this->getUserBehavior()
        ]);
    }

    /**
     * Enregistre ou met à jour le comportement de l'assistant
     */
    public function store(Request $request)
    {
        try {
            $this->validateBehaviorRequest($request);

            Log::info('Tentative de mise à jour du comportement', [
                'user_id' => auth()->id()
            ]);

            $behavior = $this->updateBehavior($request->behavior);

            Log::info('Comportement mis à jour avec succès', [
                'user_id' => auth()->id(),
                'behavior_id' => $behavior->id
            ]);

            return $this->successResponse();
        } catch (\Exception $e) {
            Log::error('Erreur lors de la mise à jour du comportement', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Méthodes privées utilitaires
     */
    private function getUserBehavior(): ?AssistantBehavior
    {
        return auth()->user()->behavior;
    }

    private function validateBehaviorRequest(Request $request): void
    {
        $request->validate([
            'behavior' => 'required|string|max:2000'
        ]);
    }

    private function updateBehavior(string $behaviorContent): AssistantBehavior
    {
        return auth()->user()->behavior()->updateOrCreate(
            ['user_id' => auth()->id()],
            ['behavior' => $behaviorContent]
        );
    }

    private function successResponse()
    {
        return redirect()
            ->back()
            ->with('success', 'Comportement mis à jour avec succès');
    }

    private function errorResponse(string $message)
    {
        return redirect()
            ->back()
            ->withErrors(['error' => $message]);
    }
}
