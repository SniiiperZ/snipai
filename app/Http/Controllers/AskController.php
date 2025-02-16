<?php

namespace App\Http\Controllers;

use App\Events\ChatMessageStreamed;
use App\Models\Conversation;
use App\Models\UserInstruction;
use App\Models\AssistantBehavior;
use App\Models\CustomCommand;
use App\Services\ChatService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use App\Traits\ImageProcessingTrait;

class AskController extends Controller
{
    use ImageProcessingTrait;

    protected $chatService;

    public function __construct(ChatService $chatService)
    {
        $this->chatService = $chatService;
        $this->setImageService(new \App\Services\ImageService());
    }

    /**
     * Affiche la page principale de chat
     */
    public function index(): Response
    {
        Log::info('Accès à la page de chat', ['user_id' => auth()->id()]);

        $models = $this->chatService->getModels();
        $selectedModel = ChatService::DEFAULT_MODEL;

        return Inertia::render('Ask/Index', [
            'models' => $models,
            'selectedModel' => $selectedModel,
            'conversations' => $this->getUserConversations(),
            'currentConversation' => null,
            'conversationHistory' => []
        ]);
    }

    /**
     * Traite une demande de chat non streamée
     */
    public function ask(Request $request, $conversationId)
    {
        $this->validateRequestWithImage($request);

        try {
            Log::info('Nouvelle demande de chat', [
                'user_id' => auth()->id(),
                'conversation_id' => $conversationId
            ]);

            $conversation = $this->getAndVerifyConversation($conversationId);
            $systemMessage = $this->buildSystemMessage();

            // Traitement de l'image si présente
            $imageData = null;
            if ($request->hasFile('image')) {
                $imagePath = $request->file('image')->path();
                $imageData = $this->processImageInput($imagePath);
            }

            // Sauvegarde le message utilisateur avec l'image
            $this->saveUserMessage($conversation, $request->message, $imageData);

            // Prépare et envoie les messages
            $messages = $this->prepareMessages($conversation, $systemMessage);
            $response = $this->chatService->sendMessage(
                messages: $messages,
                model: $request->model
            );

            // Sauvegarde la réponse
            $this->saveAssistantMessage($conversation, $response);

            Log::info('Réponse générée avec succès', [
                'conversation_id' => $conversationId
            ]);

            return back()->with([
                'messages' => $this->formatConversationMessages($conversation),
                'currentConversation' => $conversation
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur lors du traitement de la demande', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    private function canAcceptNewMessage(Conversation $conversation, string $message): bool
    {
        $systemMessage = $this->buildSystemMessage();
        $messages = $this->prepareMessages($conversation, $systemMessage);

        // Ajoute le nouveau message pour la vérification
        $messages[] = [
            'role' => 'user',
            'content' => $message
        ];

        return !$this->chatService->isConversationFull($messages, $conversation->model);
    }

    /**
     * Traite une demande de chat en streaming
     */
    public function streamMessage(Conversation $conversation, Request $request)
    {
        $this->validateRequestWithImage($request);

        try {
            if (!$this->canAcceptNewMessage($conversation, $request->message)) {
                Log::info('Conversation pleine', [
                    'conversation_id' => $conversation->id,
                    'user_id' => auth()->id()
                ]);

                throw new \Exception("Limite de contexte atteinte. Veuillez créer une nouvelle conversation.");
            }

            Log::info('Début du streaming', [
                'conversation_id' => $conversation->id,
                'user_id' => auth()->id()
            ]);

            abort_if($conversation->user_id !== auth()->id(), 403);

            // Traitement de l'image
            $imageData = null;
            if ($request->hasFile('image')) {
                $imagePath = $request->file('image')->path();
                $imageData = $this->processImageInput($imagePath);
            }

            // Sauvegarde du message utilisateur avec l'image
            $this->saveUserMessage($conversation, $request->message, $imageData);

            $systemMessage = $this->buildSystemMessage();
            $messages = $this->prepareMessages($conversation, $systemMessage);

            return $this->handleStreamResponse($conversation, $messages, $request->model);
        } catch (\Exception $e) {
            Log::error('Erreur lors du streaming', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'conversation_id' => $conversation->id
            ]);

            $channelName = "private-chat.{$conversation->id}";
            $this->broadcastError($channelName, $e->getMessage());

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Méthodes privées utilitaires
     */
    private function validateRequest(Request $request): void
    {
        $request->validate([
            'message' => 'required|string',
            'model' => 'required|string',
        ]);
    }

    private function validateRequestWithImage(Request $request): void
    {
        $request->validate([
            'message' => 'required|string',
            'model' => 'required|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:10240', // 10MB max
        ]);
    }

    private function getUserConversations()
    {
        return auth()->user()->conversations()
            ->orderBy('created_at', 'desc')
            ->get();
    }

    private function getAndVerifyConversation($conversationId): Conversation
    {
        $conversation = Conversation::findOrFail($conversationId);
        abort_if($conversation->user_id !== auth()->id(), 403);
        return $conversation;
    }

    private function buildSystemMessage(): ?array
    {
        $userInstructions = UserInstruction::where('user_id', auth()->id())->first();
        $userBehavior = AssistantBehavior::where('user_id', auth()->id())->first();
        $userCommands = auth()->user()->customCommands()->get();

        if (!$userInstructions && !$userBehavior && $userCommands->isEmpty()) {
            return null;
        }

        $content = [];
        if ($userInstructions) {
            $content[] = "Information sur l'utilisateur : " . $userInstructions->content;
        }
        if ($userBehavior) {
            $content[] = "Comportement souhaité : " . $userBehavior->behavior;
        }
        if ($userCommands->isNotEmpty()) {
            $commandsList = $userCommands->map(function ($cmd) {
                return "- {$cmd->command} : {$cmd->description} => {$cmd->action}";
            })->join("\n");
            $content[] = "Commandes personnalisées disponibles :\n" . $commandsList;
        }

        return [
            'role' => 'system',
            'content' => implode("\n\n", $content)
        ];
    }

    private function prepareMessages(Conversation $conversation, ?array $systemMessage): array
    {
        $messages = $conversation->messages()
            ->orderBy('created_at')
            ->get()
            ->map(fn($msg) => [
                'role' => $msg->role,
                'content' => $msg->content,
                'image_url' => $msg->image_url, // Ajout de l'URL de l'image
            ])
            ->toArray();

        if ($systemMessage) {
            array_unshift($messages, $systemMessage);
        }

        return $messages;
    }

    private function saveUserMessage(Conversation $conversation, string $message, ?string $imageUrl = null): void
    {
        $conversation->messages()->create([
            'role' => 'user',
            'content' => $message,
            'image_url' => $imageUrl,
        ]);
    }

    private function saveAssistantMessage(Conversation $conversation, string $message): void
    {
        $conversation->messages()->create([
            'role' => 'assistant',
            'content' => $message,
        ]);
    }

    private function formatConversationMessages(Conversation $conversation): array
    {
        return $conversation->messages()
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
            ->toArray();
    }

    private function handleStreamResponse(Conversation $conversation, array $messages, string $model)
    {
        $stream = $this->chatService->streamConversation(
            messages: $messages,
            model: $model
        );

        // Modification ici : utilisez .= au lieu de +=
        $fullResponse = '';
        $channelName = "private-chat.{$conversation->id}";

        foreach ($stream as $response) {
            $content = $response->choices[0]->delta->content ?? '';
            $fullResponse .= $content;

            $this->broadcastMessage($channelName, $content);
        }

        $this->saveAssistantMessage($conversation, $fullResponse);
        $this->broadcastCompletion($channelName);

        return response()->noContent();
    }

    private function broadcastMessage(string $channel, string $content): void
    {
        broadcast(new ChatMessageStreamed(
            channel: $channel,
            content: $content,
            isComplete: false
        ));
    }

    private function broadcastCompletion(string $channel): void
    {
        broadcast(new ChatMessageStreamed(
            channel: $channel,
            content: '',
            isComplete: true
        ));
    }

    private function broadcastError(string $channel, string $error): void
    {
        broadcast(new ChatMessageStreamed(
            channel: $channel,
            content: "Erreur: " . $error,
            isComplete: true,
            error: true
        ));
    }
}
