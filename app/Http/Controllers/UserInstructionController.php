<?php

namespace App\Http\Controllers;

use App\Models\UserInstruction;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class UserInstructionController extends Controller
{
    /**
     * Affiche la page des instructions utilisateur
     */
    public function index(): Response
    {
        Log::info('Accès à la page des instructions utilisateur', [
            'user_id' => auth()->id()
        ]);

        return Inertia::render('Instructions/Index', [
            'instructions' => $this->getUserInstructions()
        ]);
    }

    /**
     * Enregistre ou met à jour les instructions utilisateur
     */
    public function store(Request $request): RedirectResponse
    {
        try {
            $this->validateInstructionRequest($request);

            Log::info('Tentative de mise à jour des instructions', [
                'user_id' => auth()->id()
            ]);

            $instruction = $this->updateOrCreateInstruction($request->content);

            Log::info('Instructions mises à jour avec succès', [
                'user_id' => auth()->id(),
                'instruction_id' => $instruction->id
            ]);

            return $this->successResponse();
        } catch (\Exception $e) {
            Log::error('Erreur lors de la mise à jour des instructions', [
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
    private function getUserInstructions(): ?UserInstruction
    {
        return auth()->user()->instructions;
    }

    private function validateInstructionRequest(Request $request): void
    {
        $request->validate([
            'content' => 'required|string|max:2000'
        ]);
    }

    private function updateOrCreateInstruction(string $content): UserInstruction
    {
        return auth()->user()->instructions()->updateOrCreate(
            ['user_id' => auth()->id()],
            ['content' => $content]
        );
    }

    private function successResponse(): RedirectResponse
    {
        return redirect()
            ->back()
            ->with('success', 'Instructions mises à jour avec succès');
    }

    private function errorResponse(string $message): RedirectResponse
    {
        return redirect()
            ->back()
            ->withErrors(['error' => $message]);
    }
}
