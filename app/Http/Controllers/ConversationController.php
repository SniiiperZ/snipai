<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Services\ChatService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class ConversationController extends Controller
{
    protected $chatService;

    public function __construct(ChatService $chatService)
    {
        $this->chatService = $chatService;
    }

    /**
     * Affiche la liste des conversations
     */
    public function index(): Response
    {
        Log::info('Accès à la liste des conversations', [
            'user_id' => auth()->id()
        ]);

        return Inertia::render('Ask/Index', [
            'conversations' => $this->getUserConversations(),
            'models' => $this->chatService->getModels(),
            'selectedModel' => ChatService::DEFAULT_MODEL,
        ]);
    }

    /**
     * Crée une nouvelle conversation
     */
    public function store(Request $request)
    {
        try {
            Log::info('Création d\'une nouvelle conversation', [
                'user_id' => auth()->id()
            ]);

            $conversation = $this->createConversation();

            return redirect()->route('conversations.show', $conversation)
                ->with([
                    'currentConversation' => $conversation,
                    'messages' => [],
                ]);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la création de la conversation', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);

            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Affiche une conversation spécifique
     */
    public function show(Conversation $conversation): Response
    {
        $this->authorizeAccess($conversation);

        Log::info('Affichage d\'une conversation', [
            'conversation_id' => $conversation->id,
            'user_id' => auth()->id()
        ]);

        return Inertia::render('Ask/Index', [
            'currentConversation' => $conversation,
            'conversationHistory' => $this->formatConversationHistory($conversation),
            'conversations' => $this->getUserConversations(),
            'models' => $this->chatService->getModels(),
            'selectedModel' => $conversation->model,
        ]);
    }

    /**
     * Récupère les messages d'une conversation
     */
    public function messages(Conversation $conversation)
    {
        $this->authorizeAccess($conversation);

        Log::info('Récupération des messages', [
            'conversation_id' => $conversation->id,
            'user_id' => auth()->id()
        ]);

        return response()->json($this->formatConversationHistory($conversation));
    }

    /**
     * Génère un titre pour la conversation
     */
    public function generateTitle(Conversation $conversation)
    {
        $this->authorizeAccess($conversation);

        try {
            Log::info('Génération du titre', [
                'conversation_id' => $conversation->id
            ]);

            $messages = $this->getInitialMessages($conversation);

            if ($messages->count() < 2) {
                return $this->defaultTitleResponse($conversation);
            }

            $title = $this->generateTitleFromMessages($messages, $conversation);
            $conversation->update(['title' => $title]);

            return response()->json(['title' => $title]);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la génération du titre', [
                'conversation_id' => $conversation->id,
                'error' => $e->getMessage()
            ]);

            return $this->defaultTitleResponse($conversation);
        }
    }

    /**
     * Supprime une conversation
     */
    public function destroy(Conversation $conversation)
    {
        $this->authorizeAccess($conversation);

        try {
            Log::info('Suppression d\'une conversation', [
                'conversation_id' => $conversation->id
            ]);

            $conversation->delete();

            return Inertia::location(route('conversations.index'));
        } catch (\Exception $e) {
            Log::error('Erreur lors de la suppression', [
                'conversation_id' => $conversation->id,
                'error' => $e->getMessage()
            ]);

            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Met à jour le modèle d'une conversation
     */
    public function updateModel(Conversation $conversation, Request $request)
    {
        $this->authorizeAccess($conversation);

        try {
            $this->validateModelRequest($request);

            Log::info('Mise à jour du modèle', [
                'conversation_id' => $conversation->id,
                'model' => $request->model
            ]);

            $conversation->update(['model' => $request->model]);

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la mise à jour du modèle', [
                'conversation_id' => $conversation->id,
                'error' => $e->getMessage()
            ]);

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Méthodes privées utilitaires
     */
    private function authorizeAccess(Conversation $conversation): void
    {
        abort_if($conversation->user_id !== auth()->id(), 403);
    }

    private function getUserConversations()
    {
        return auth()->user()->conversations()
            ->orderBy('created_at', 'desc')
            ->get();
    }

    private function createConversation(): Conversation
    {
        return auth()->user()->conversations()->create([
            'title' => 'Nouvelle conversation',
            'model' => ChatService::DEFAULT_MODEL,
        ]);
    }

    private function formatConversationHistory(Conversation $conversation): array
    {
        return $conversation->messages()
            ->orderBy('created_at')
            ->get()
            ->map(function ($message) {
                return [
                    'question' => $message->role === 'user' ? $message->content : null,
                    'answer' => $message->role === 'assistant' ? $message->content : null,
                    'image_url' => $message->image_url,
                ];
            })
            ->filter()
            ->values()
            ->toArray();
    }

    private function getInitialMessages(Conversation $conversation)
    {
        return $conversation->messages()
            ->whereIn('role', ['user', 'assistant'])
            ->orderBy('created_at')
            ->limit(2)
            ->get();
    }

    private function generateTitleFromMessages($messages, Conversation $conversation): string
    {
        $prompt = "Génère un titre très court, maximum 5 mots, sans aucun formatage (ni markdown ni backticks) pour cette conversation. Question: {$messages[0]->content} Réponse: {$messages[1]->content}";

        $title = $this->chatService->sendMessage(
            messages: [['role' => 'user', 'content' => $prompt]],
            model: $conversation->model
        );

        return $this->formatTitle($title);
    }

    private function formatTitle(string $title): string
    {
        $title = strip_tags($title);
        $titleWords = preg_split('/\s+/', trim($title));

        return count($titleWords) > 5
            ? implode(' ', array_slice($titleWords, 0, 5))
            : $title;
    }

    private function defaultTitleResponse(Conversation $conversation)
    {
        return response()->json([
            'title' => $conversation->title ?: 'Nouvelle conversation'
        ]);
    }

    private function validateModelRequest(Request $request): void
    {
        $request->validate([
            'model' => 'required|string',
        ]);
    }

    private function errorResponse(string $message)
    {
        return redirect()
            ->back()
            ->withErrors(['error' => $message]);
    }
}
