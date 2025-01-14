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
            $messages = $conversation->messages()
                ->orderBy('created_at')
                ->limit(2)
                ->get();

            if ($messages->count() < 2) {
                return response()->json(['title' => 'Nouvelle conversation']);
            }

            $prompt = "Génère un titre court (max 6 mots) pour cette conversation. Question: {$messages[0]->content} Réponse: {$messages[1]->content}";

            $title = (new ChatService())->sendMessage(
                messages: [['role' => 'user', 'content' => $prompt]],
                model: $conversation->model
            );

            $conversation->update(['title' => $title]);

            return response()->json(['title' => $title]);
        } catch (\Exception $e) {
            return response()->json(['title' => 'Nouvelle conversation']);
        }
    }

    public function destroy(Conversation $conversation)
    {
        abort_if($conversation->user_id !== auth()->id(), 403);

        $conversation->delete();

        return Inertia::location(route('conversations.index'));
    }
}
