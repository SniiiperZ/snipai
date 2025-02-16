<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class WeatherService
{
    private $apiKey;
    private $baseUrl = 'http://api.openweathermap.org/data/2.5';

    public function __construct()
    {
        $this->apiKey = config('services.openweathermap.api_key');
        Log::info('WeatherService initialisé', [
            'apiKey' => $this->apiKey ? 'présente' : 'manquante'
        ]);
    }

    public function getWeather(string $city, string $country = ''): array
    {
        $cacheKey = "weather_{$city}_{$country}";

        Log::info('WeatherService - Tentative de récupération météo', [
            'city' => $city,
            'country' => $country,
            'apiKey' => $this->apiKey ? 'présente' : 'manquante'
        ]);

        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($city, $country) {
            $location = $country ? "{$city},{$country}" : $city;
            $url = "{$this->baseUrl}/weather";

            Log::info('WeatherService - Appel API', [
                'url' => $url,
                'location' => $location
            ]);

            $response = Http::get("{$this->baseUrl}/weather", [
                'q' => $location,
                'appid' => $this->apiKey,
                'units' => 'metric',
                'lang' => 'fr'
            ]);

            if ($response->failed()) {
                throw new \Exception('Impossible de récupérer les données météo');
            }

            $data = $response->json();
            Log::info('WeatherService - Données reçues', $data);
            return [
                'temperature' => round($data['main']['temp']),
                'description' => $data['weather'][0]['description'],
                'humidity' => $data['main']['humidity'],
                'wind_speed' => round($data['wind']['speed'] * 3.6), // Conversion en km/h
                'city' => $data['name'],
                'country' => $data['sys']['country']
            ];
        });
    }
}
