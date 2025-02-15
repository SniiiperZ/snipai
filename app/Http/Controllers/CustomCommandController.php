<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\CustomCommand;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;

class CustomCommandController extends Controller
{
    /**
     * Affiche la liste des commandes personnalisées
     */
    public function index(): Response
    {
        Log::info('Accès à la page des commandes personnalisées', [
            'user_id' => auth()->id()
        ]);

        return Inertia::render('Commands/Index', [
            'commands' => $this->getUserCommands()
        ]);
    }

    /**
     * Enregistre ou met à jour une commande personnalisée
     */
    public function store(Request $request): RedirectResponse
    {
        try {
            $this->validateCommandRequest($request);

            Log::info('Tentative de création/mise à jour d\'une commande', [
                'user_id' => auth()->id(),
                'command_id' => $request->id ?? 'new'
            ]);

            $command = $this->updateOrCreateCommand($request);

            Log::info('Commande mise à jour avec succès', [
                'user_id' => auth()->id(),
                'command_id' => $command->id
            ]);

            return $this->successResponse('Commande mise à jour avec succès');
        } catch (\Exception $e) {
            Log::error('Erreur lors de la mise à jour de la commande', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Supprime une commande personnalisée
     */
    public function destroy($id): RedirectResponse
    {
        try {
            Log::info('Tentative de suppression d\'une commande', [
                'user_id' => auth()->id(),
                'command_id' => $id
            ]);

            $command = $this->findAndVerifyCommand($id);
            $command->delete();

            Log::info('Commande supprimée avec succès', [
                'user_id' => auth()->id(),
                'command_id' => $id
            ]);

            return $this->successResponse('Commande supprimée avec succès');
        } catch (\Exception $e) {
            Log::error('Erreur lors de la suppression de la commande', [
                'user_id' => auth()->id(),
                'command_id' => $id,
                'error' => $e->getMessage()
            ]);

            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Méthodes privées utilitaires
     */
    private function getUserCommands()
    {
        return auth()->user()->customCommands;
    }

    private function validateCommandRequest(Request $request): void
    {
        $request->validate([
            'command' => 'required|string|max:50|unique:custom_commands,command,' . $request->id,
            'description' => 'required|string|max:255',
            'action' => 'required|string|max:2000'
        ]);
    }

    private function updateOrCreateCommand(Request $request): CustomCommand
    {
        return auth()->user()->customCommands()->updateOrCreate(
            ['id' => $request->id ?? null],
            [
                'command' => $request->command,
                'description' => $request->description,
                'action' => $request->action
            ]
        );
    }

    private function findAndVerifyCommand($id): CustomCommand
    {
        $command = CustomCommand::findOrFail($id);
        abort_if($command->user_id !== auth()->id(), 403);
        return $command;
    }

    private function successResponse(string $message): RedirectResponse
    {
        return redirect()->back()->with('success', $message);
    }

    private function errorResponse(string $message): RedirectResponse
    {
        return redirect()->back()->withErrors(['error' => $message]);
    }
}
