<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Services\ChatService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ConversationController extends Controller
{
    public function index()
    {
        // Retourne la vue principale avec les conversations
        return Inertia::render('Ask/Index', [
            'conversations' => auth()->user()->conversations()
                ->orderBy('created_at', 'desc')
                ->get(),
            'models' => (new ChatService())->getModels(),
            'selectedModel' => ChatService::DEFAULT_MODEL,
        ]);
    }

    public function store(Request $request)
    {
        $conversation = auth()->user()->conversations()->create([
            'title' => 'Nouvelle conversation',
            'model' => ChatService::DEFAULT_MODEL,
        ]);

        return redirect()->route('conversations.show', $conversation)->with([
            'currentConversation' => $conversation,
            'messages' => [],
        ]);
    }

    public function show(Conversation $conversation)
    {
        abort_if($conversation->user_id !== auth()->id(), 403);

        return Inertia::render('Ask/Index', [
            'currentConversation' => $conversation,
            'conversationHistory' => $conversation->messages()
                ->orderBy('created_at')
                ->get()
                ->map(function ($message) {
                    return [
                        'question' => $message->role === 'user' ? $message->content : null,
                        'answer' => $message->role === 'assistant' ? $message->content : null,
                    ];
                })
                ->filter()
                ->values(),
            'conversations' => auth()->user()->conversations()
                ->orderBy('created_at', 'desc')
                ->get(),
            'models' => (new ChatService())->getModels(),
            'selectedModel' => $conversation->model,
        ]);
    }

    public function messages(Conversation $conversation)
    {
        abort_if($conversation->user_id !== auth()->id(), 403);

        return response()->json(
            $conversation->messages()
                ->orderBy('created_at')
                ->get()
                ->map(function ($message) {
                    return [
                        'question' => $message->role === 'user' ? $message->content : null,
                        'answer' => $message->role === 'assistant' ? $message->content : null,
                    ];
                })
                ->filter()
                ->values()
        );
    }

    public function generateTitle(Conversation $conversation)
    {
        abort_if($conversation->user_id !== auth()->id(), 403);

        try {
            // On ne récupère que les messages utiles (user et assistant)
            $messages = $conversation->messages()
                ->whereIn('role', ['user', 'assistant'])
                ->orderBy('created_at')
                ->limit(2)
                ->get();

            if ($messages->count() < 2) {
                return response()->json(['title' => $conversation->title ?: 'Nouvelle conversation']);
            }

            // Demander un titre très court, maximum 5 mots, et sans markdown/backticks
            $prompt = "Génère un titre très court, maximum 5 mots, sans aucun formatage (ni markdown ni backticks) pour cette conversation. Question: {$messages[0]->content} Réponse: {$messages[1]->content}";

            $title = (new \App\Services\ChatService())->sendMessage(
                messages: [['role' => 'user', 'content' => $prompt]],
                model: $conversation->model
            );

            // Suppression d'éventuels tags HTML et nettoyage du résultat
            $title = strip_tags($title);
            $titleWords = preg_split('/\s+/', trim($title));
            if (count($titleWords) > 5) {
                $title = implode(' ', array_slice($titleWords, 0, 5));
            }

            $conversation->update(['title' => $title]);

            return response()->json(['title' => $title]);
        } catch (\Exception $e) {
            return response()->json(['title' => $conversation->title ?: 'Nouvelle conversation']);
        }
    }

    public function destroy(Conversation $conversation)
    {
        abort_if($conversation->user_id !== auth()->id(), 403);

        $conversation->delete();

        return Inertia::location(route('conversations.index'));
    }
}
