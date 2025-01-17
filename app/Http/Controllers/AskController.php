<?php

namespace App\Http\Controllers;

use App\Services\ChatService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Conversation;
use App\Models\UserInstruction;
use App\Models\AssistantBehavior;

class AskController extends Controller
{
    public function index()
    {
        $models = (new ChatService())->getModels();
        $selectedModel = ChatService::DEFAULT_MODEL;

        return Inertia::render('Ask/Index', [
            'models' => $models,
            'selectedModel' => $selectedModel,
            'conversations' => auth()->user()->conversations()
                ->orderBy('created_at', 'desc')
                ->get(),
            'currentConversation' => null,
            'conversationHistory' => []
        ]);
    }

    public function ask(Request $request, $conversationId) 
    {
        $request->validate([
            'message' => 'required|string',
            'model' => 'required|string',
        ]);

        try {
            $conversation = Conversation::findOrFail($conversationId);
            abort_if($conversation->user_id !== auth()->id(), 403);

            // Récupérer les instructions et le comportement
            $userInstructions = UserInstruction::where('user_id', auth()->id())->first();
            $userBehavior = AssistantBehavior::where('user_id', auth()->id())->first();
            
            // Créer un message système avec les instructions et le comportement
            $systemMessage = null;
            if ($userInstructions || $userBehavior) {
                $content = [];
                if ($userInstructions) {
                    $content[] = "Information sur l'utilisateur : " . $userInstructions->content;
                }
                if ($userBehavior) {
                    $content[] = "Comportement souhaité : " . $userBehavior->behavior;
                }
                $systemMessage = [
                    'role' => 'system',
                    'content' => implode("\n\n", $content)
                ];
            }

            // Sauvegarde le message utilisateur
            $conversation->messages()->create([
                'role' => 'user',
                'content' => $request->message,
            ]);

            // Préparer les messages pour l'IA
            $messages = $conversation->messages()
                ->orderBy('created_at')
                ->get()
                ->map(fn($msg) => [
                    'role' => $msg->role,
                    'content' => $msg->content,
                ])
                ->toArray();

            // Ajouter le message système au début s'il existe
            if ($systemMessage) {
                array_unshift($messages, $systemMessage);
            }

            // Obtient la réponse de l'IA
            $response = (new ChatService())->sendMessage(
                messages: $messages,
                model: $request->model
            );

            // Sauvegarde la réponse
            $conversation->messages()->create([
                'role' => 'assistant',
                'content' => $response,
            ]);

            // Récupérer les messages mis à jour
            $messages = $conversation->messages()
                    ->orderBy('created_at')
                    ->get()
                    ->map(function ($message) {
                        return [
                            'question' => $message->role === 'user' ? $message->content : null,
                            'answer' => $message->role === 'assistant' ? $message->content : null,
                        ];
                    })
                    ->filter()
                ->values();

            return back()->with([
                'messages' => $messages,
                'currentConversation' => $conversation
            ]);
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
