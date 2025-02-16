<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class ChatService
{
    private $baseUrl;
    private $apiKey;
    private $client;
    public const DEFAULT_MODEL = 'gpt-4-vision-preview';

    public function __construct()
    {
        $this->baseUrl = config('services.openrouter.base_url', 'https://openrouter.ai/api/v1');
        $this->apiKey = config('services.openrouter.api_key');
        $this->client = $this->createOpenAIClient();
    }

    /**
     * @return array<array-key, array{
     *     id: string,
     *     name: string,
     *     context_length: int,
     *     max_completion_tokens: int,
     *     pricing: array{prompt: int, completion: int}
     * }>
     */
    public function getModels(): array
    {
        return cache()->remember('openai.models', now()->addHour(), function () {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
            ])->get($this->baseUrl . '/models');

            return collect($response->json()['data'])
                ->filter(function ($model) {
                    // Inclure les modèles gratuits ET les modèles avec capacité vision
                    return str_ends_with($model['id'], ':free') ||
                        str_contains($model['id'], 'vision') ||
                        str_contains($model['name'], 'vision');
                })
                ->sortBy('name')
                ->map(function ($model) {
                    // Ajouter un indicateur pour les modèles payants
                    $isFree = str_ends_with($model['id'], ':free');
                    return [
                        'id' => $model['id'],
                        'name' => $model['name'] . ($isFree ? ' (Gratuit)' : ' (Payant)'),
                        'context_length' => $model['context_length'],
                        'max_completion_tokens' => $model['top_provider']['max_completion_tokens'],
                        'pricing' => $model['pricing'],
                        'supports_vision' => str_contains($model['id'], 'vision') ||
                            str_contains($model['name'], 'vision'),
                    ];
                })
                ->values()
                ->all();
        });
    }

    /**
     * @param array{role: 'user'|'assistant'|'system'|'function', content: string} $messages
     * @param string|null $model
     * @param float $temperature
     *
     * @return string
     */
    public function sendMessage(array $messages, string $model = null, float $temperature = 0.5): string
    {
        try {
            $formattedMessages = array_map(function ($message) {
                if (isset($message['image_url']) && !empty($message['image_url'])) {
                    return [
                        'role' => $message['role'],
                        'content' => [
                            [
                                'type' => 'text',
                                'text' => $message['content']
                            ],
                            [
                                'type' => 'image_url',
                                'image_url' => [
                                    'url' => $message['image_url']
                                ]
                            ]
                        ]
                    ];
                }
                return [
                    'role' => $message['role'],
                    'content' => $message['content']
                ];
            }, $messages);

            $response = $this->client->chat()->create([
                'model' => $model ?? self::DEFAULT_MODEL,
                'messages' => $formattedMessages,
                'temperature' => $temperature,
            ]);

            return $response->choices[0]->message->content;
        } catch (\Exception $e) {
            if ($e->getMessage() === 'Undefined array key "choices"') {
                throw new \Exception("Limite de messages atteinte");
            }

            logger()->error('Erreur dans sendMessage:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    private function createOpenAIClient(): \OpenAI\Client
    {
        return \OpenAI::factory()
            ->withApiKey($this->apiKey)
            ->withBaseUri($this->baseUrl)
            ->make()
        ;
    }

    /**
     * @return array{role: 'system', content: string}
     */
    private function getChatSystemPrompt(): array
    {
        $user = auth()->user();
        $now = now()->locale('fr')->format('l d F Y H:i');

        return [
            'role' => 'system',
            'content' => <<<EOT
                Tu es un assistant de chat. La date et l'heure actuelle est le {$now}.
                Tu es actuellement utilisé par {$user->name}.
                EOT,
        ];
    }

    public function streamConversation(array $messages, string $model = null, float $temperature = 0.5)
    {
        try {
            logger()->info('Début du streaming', ['messages' => $messages]);

            $models = collect($this->getModels());
            if (!$model || !$models->contains('id', $model)) {
                $model = self::DEFAULT_MODEL;
                logger()->info('Modèle par défaut utilisé:', ['model' => $model]);
            }

            $messages = [$this->getChatSystemPrompt(), ...$messages];

            // Formatage des messages avec images
            $formattedMessages = array_map(function ($message) {
                if (isset($message['image_url']) && !empty($message['image_url'])) {
                    return [
                        'role' => $message['role'],
                        'content' => [
                            [
                                'type' => 'text',
                                'text' => $message['content']
                            ],
                            [
                                'type' => 'image_url',
                                'image_url' => [
                                    'url' => $message['image_url']
                                ]
                            ]
                        ]
                    ];
                }
                return [
                    'role' => $message['role'],
                    'content' => $message['content']
                ];
            }, $messages);

            $stream = $this->client->chat()->createStreamed([
                'model' => $model,
                'messages' => $formattedMessages,
                'temperature' => $temperature,
            ]);

            logger()->info('Stream créé avec succès');

            return $stream;
        } catch (\Exception $e) {
            logger()->error('Erreur dans streamConversation:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}
