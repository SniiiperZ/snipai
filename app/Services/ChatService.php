<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChatService
{
    private $baseUrl;
    private $apiKey;
    private $client;
    private $weatherService;
    public const DEFAULT_MODEL = 'meta-llama/llama-3.2-11b-vision-instruct:free';

    public function __construct()
    {
        $this->baseUrl = config('services.openrouter.base_url', 'https://openrouter.ai/api/v1');
        $this->apiKey = config('services.openrouter.api_key');
        $this->client = $this->createOpenAIClient();
        $this->weatherService = new WeatherService();
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
                    // Ne garder que les modèles gratuits
                    return str_ends_with($model['id'], ':free');
                })
                ->sortBy('name')
                ->map(function ($model) {
                    return [
                        'id' => $model['id'],
                        'name' => $model['name'] . ' (Gratuit)',
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
        $messages = $this->processToolCalls($messages);
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
            }

            // Traiter la météo avant tout
            $lastMessage = end($messages);
            $weatherInfo = '';

            if ($this->needsWeatherInfo($lastMessage['content'])) {
                try {
                    $location = $this->extractLocation($lastMessage['content']);
                    $weatherData = $this->weatherService->getWeather($location['city'], $location['country']);
                    $weatherInfo = $this->formatWeatherResponse($weatherData);
                } catch (\Exception $e) {
                    Log::error('Erreur météo', ['error' => $e->getMessage()]);
                }
            }

            // Construire le message système avec les informations météo
            $systemPrompt = $this->getChatSystemPrompt();
            if ($weatherInfo) {
                $systemPrompt['content'] .= "\n\n" . $weatherInfo;
            }

            // Ajouter le prompt système au début des messages
            $messagesWithSystem = [$systemPrompt];

            // Formater les messages avec gestion des images
            foreach ($messages as $message) {
                if (isset($message['image_url']) && !empty($message['image_url'])) {
                    $messagesWithSystem[] = [
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
                } else {
                    $messagesWithSystem[] = [
                        'role' => $message['role'],
                        'content' => $message['content']
                    ];
                }
            }

            Log::info('Messages avant streaming', [
                'messages' => array_map(fn($m) => [
                    'role' => $m['role'],
                    'content' => is_array($m['content'])
                        ? substr($m['content'][0]['text'], 0, 100) . '...'
                        : substr($m['content'], 0, 100) . '...'
                ], $messagesWithSystem)
            ]);

            $stream = $this->client->chat()->createStreamed([
                'model' => $model,
                'messages' => $messagesWithSystem,
                'temperature' => $temperature,
            ]);

            logger()->info('Stream créé avec succès');
            return $stream;
        } catch (\Exception $e) {
            logger()->error('Erreur dans streamConversation:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    private function estimateTokenCount(string $text): int
    {
        // Estimation simple: 1 token ≈ 4 caractères
        return (int) ceil(mb_strlen($text) / 4);
    }

    public function isConversationFull(array $messages, string $model): bool
    {
        $models = collect($this->getModels());
        $modelInfo = $models->firstWhere('id', $model);

        if (!$modelInfo) {
            return false;
        }

        $totalTokens = 0;
        foreach ($messages as $message) {
            $content = is_array($message['content'])
                ? collect($message['content'])->pluck('text')->join(' ')
                : $message['content'];
            $totalTokens += $this->estimateTokenCount($content);
        }

        // Garde 20% de marge de sécurité
        $maxTokens = (int) ($modelInfo['context_length'] * 0.8);

        return $totalTokens >= $maxTokens;
    }

    private function needsWeatherInfo(string $message): bool
    {
        $keywords = ['météo', 'température', 'temps qu\'il fait', 'climat', 'quel temps'];
        $message = mb_strtolower($message);

        foreach ($keywords as $keyword) {
            if (mb_strpos($message, mb_strtolower($keyword)) !== false) {
                Log::info('Détection météo positive', [
                    'message' => $message,
                    'keyword_matched' => $keyword
                ]);
                return true;
            }
        }

        return false;
    }

    private function extractLocation(string $message): array
    {
        Log::info('Extraction de la localisation - message original', ['message' => $message]);

        // Pattern amélioré pour la détection de ville
        $patterns = [
            '/(?:à|a|dans|pour|de)\s+([A-Za-zÀ-ÿ\-]+)(?:\s*(?:en|,)\s*([A-Za-zÀ-ÿ]+))?/i',
            '/(?:météo|température|temps).*?(?:à|a|dans|pour|de)\s+([A-Za-zÀ-ÿ\-]+)(?:\s*(?:en|,)\s*([A-Za-zÀ-ÿ]+))?/i'
        ];

        $city = 'Wavre';
        $country = 'BE';

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $message, $matches)) {
                // Nettoyage du nom de la ville
                $matchedCity = trim($matches[1]);
                if (
                    strtolower($matchedCity) === 'wavre' ||
                    !preg_match('/^(météo|température|temps|cette|dans|pour|de)$/i', $matchedCity)
                ) {
                    $city = ucfirst(strtolower($matchedCity));
                    // Si un pays est spécifié
                    if (!empty($matches[2])) {
                        $matchedCountry = trim($matches[2]);
                        $country = strtolower($matchedCountry) === 'belgique' ? 'BE' : strtoupper($matchedCountry);
                    }
                    break;
                }
            }
        }

        // Si aucune ville n'est détectée mais "Wavre" est présent dans le texte
        if ($city === 'Wavre' && stripos($message, 'wavre') !== false) {
            $city = 'Wavre';
            $country = 'BE';
        }

        Log::info('Localisation extraite après traitement', [
            'city' => $city,
            'country' => $country
        ]);

        return [
            'city' => $city,
            'country' => $country
        ];
    }

    private function processToolCalls(array $messages): array
    {
        $lastMessage = end($messages);
        if (!isset($lastMessage['content']) || !is_string($lastMessage['content'])) {
            return $messages;
        }

        Log::info('ProcessToolCalls - Message reçu', [
            'content' => $lastMessage['content']
        ]);

        if ($this->needsWeatherInfo($lastMessage['content'])) {
            try {
                Log::info('Traitement de la demande météo', [
                    'message' => $lastMessage['content']
                ]);

                $location = $this->extractLocation($lastMessage['content']);
                Log::info('Localisation extraite', $location);

                $weatherData = $this->weatherService->getWeather($location['city'], $location['country']);
                Log::info('ProcessToolCalls - Données météo reçues', $weatherData);

                $weatherInfo = "Voici les informations météorologiques actuelles pour {$weatherData['city']} ({$weatherData['country']}) :\n" .
                    "🌡️ Température : {$weatherData['temperature']}°C\n" .
                    "🌤️ Conditions : {$weatherData['description']}\n" .
                    "💧 Humidité : {$weatherData['humidity']}%\n" .
                    "💨 Vitesse du vent : {$weatherData['wind_speed']} km/h";

                $messages[] = [
                    'role' => 'system',
                    'content' => $weatherInfo
                ];

                Log::info('ProcessToolCalls - Message météo ajouté', [
                    'weatherInfo' => $weatherInfo
                ]);
            } catch (\Exception $e) {
                Log::error('Erreur météo:', [
                    'message' => $e->getMessage(),
                    'location' => $location ?? null
                ]);

                $messages[] = [
                    'role' => 'system',
                    'content' => "Désolé, je n'ai pas pu obtenir les informations météo: " . $e->getMessage()
                ];
            }
        }

        return $messages;
    }

    private function formatWeatherResponse(array $weatherData): string
    {
        return "🌡️ Voici les informations météorologiques actuelles pour {$weatherData['city']} ({$weatherData['country']}) :\n" .
            "- Température : {$weatherData['temperature']}°C\n" .
            "- Conditions : {$weatherData['description']}\n" .
            "- Humidité : {$weatherData['humidity']}%\n" .
            "- Vitesse du vent : {$weatherData['wind_speed']} km/h";
    }
}
