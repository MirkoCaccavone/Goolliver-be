<?php

namespace App\Services\Moderation;

use App\Exceptions\ModerationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAIModerationProvider
{
    protected array $config;
    protected string $apiKey;

    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'model' => 'gpt-4-vision-preview',
            'max_tokens' => 300,
            'detail' => 'low',
            'timeout' => 30,
        ], $config);

        $this->apiKey = $config['api_key'] ?? config('moderation.providers.openai.api_key');

        if (empty($this->apiKey)) {
            throw new ModerationException('OpenAI API key not configured');
        }
    }

    /**
     * Analizza un'immagine per contenuti inappropriati
     */
    public function analyzeImage(UploadedFile $photo): array
    {
        try {
            // Converti immagine in base64
            $imageData = base64_encode(file_get_contents($photo->path()));
            $mimeType = $photo->getMimeType();

            // Prepara il prompt per l'analisi
            $prompt = $this->buildModerationPrompt();

            // Chiama l'API OpenAI
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout($this->config['timeout'])
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => $this->config['model'],
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => [
                                [
                                    'type' => 'text',
                                    'text' => $prompt
                                ],
                                [
                                    'type' => 'image_url',
                                    'image_url' => [
                                        'url' => "data:{$mimeType};base64,{$imageData}",
                                        'detail' => $this->config['detail']
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'max_tokens' => $this->config['max_tokens']
                ]);

            if (!$response->successful()) {
                throw new ModerationException(
                    'OpenAI API request failed: ' . $response->body(),
                    $response->status()
                );
            }

            $result = $response->json();

            // Estrai e processa la risposta
            $content = $result['choices'][0]['message']['content'] ?? '';

            return $this->parseOpenAIResponse($content);
        } catch (\Exception $e) {
            Log::error('OpenAI moderation failed', [
                'error' => $e->getMessage(),
                'photo_size' => $photo->getSize(),
                'photo_type' => $photo->getMimeType()
            ]);

            throw new ModerationException(
                'OpenAI analysis failed: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Costruisce il prompt per la moderazione
     */
    protected function buildModerationPrompt(): string
    {
        return "Analizza questa immagine per contenuti inappropriati e fornisci un punteggio di sicurezza.

Rispondi SOLO con un JSON valido nel seguente formato:
{
  \"overall_score\": 0.0,
  \"categories\": {
    \"adult\": 0.0,
    \"violence\": 0.0,
    \"hatred\": 0.0,
    \"harassment\": 0.0,
    \"self_harm\": 0.0,
    \"illegal\": 0.0,
    \"spam\": 0.0,
    \"inappropriate\": 0.0
  },
  \"confidence\": 0.0,
  \"reasoning\": \"Breve spiegazione\",
  \"detected_objects\": [\"lista oggetti rilevati\"],
  \"detected_text\": \"eventuale testo nell'immagine\"
}

Punteggi da 0.0 (sicuro) a 1.0 (inappropriato).

Categorie:
- adult: contenuti sessuali/nudità
- violence: violenza, armi, sangue
- hatred: simboli odio, discriminazione
- harassment: molestie, bullismo
- self_harm: autolesionismo, suicidio
- illegal: droghe, attività illegali
- spam: pubblicità, watermark eccessivi
- inappropriate: generico inappropriato

Sii conservativo: in caso di dubbio, aumenta il punteggio.";
    }

    /**
     * Parsa la risposta di OpenAI
     */
    protected function parseOpenAIResponse(string $content): array
    {
        try {
            // Rimuovi eventuali backticks o markdown
            $content = trim($content);
            $content = preg_replace('/```json\s*/', '', $content);
            $content = preg_replace('/```\s*$/', '', $content);

            $data = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Invalid JSON response: ' . json_last_error_msg());
            }

            // Valida e normalizza i dati
            return [
                'score' => $this->validateScore($data['overall_score'] ?? 0.0),
                'categories' => $this->validateCategories($data['categories'] ?? []),
                'confidence' => $this->validateScore($data['confidence'] ?? 0.8),
                'weight' => 1.0,
                'provider' => 'openai',
                'reasoning' => $data['reasoning'] ?? '',
                'detected_objects' => $data['detected_objects'] ?? [],
                'detected_text' => $data['detected_text'] ?? '',
                'raw_response' => $data
            ];
        } catch (\Exception $e) {
            Log::warning('Failed to parse OpenAI response', [
                'content' => $content,
                'error' => $e->getMessage()
            ]);

            // Fallback: analisi conservativa
            return [
                'score' => 0.5, // Punteggio medio per sicurezza
                'categories' => ['inappropriate' => 0.5],
                'confidence' => 0.3,
                'weight' => 0.5,
                'provider' => 'openai_fallback',
                'reasoning' => 'Errore parsing risposta: ' . $e->getMessage(),
                'detected_objects' => [],
                'detected_text' => '',
                'raw_response' => $content
            ];
        }
    }

    /**
     * Valida un punteggio (0.0-1.0)
     */
    protected function validateScore(float $score): float
    {
        return max(0.0, min(1.0, $score));
    }

    /**
     * Valida le categorie e i loro punteggi
     */
    protected function validateCategories(array $categories): array
    {
        $validCategories = [
            'adult',
            'violence',
            'hatred',
            'harassment',
            'self_harm',
            'illegal',
            'spam',
            'inappropriate'
        ];

        $result = [];
        foreach ($validCategories as $category) {
            $score = $categories[$category] ?? 0.0;
            $result[$category] = $this->validateScore($score);
        }

        return $result;
    }

    /**
     * Test della connessione API
     */
    public function testConnection(): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
            ])->timeout(10)
                ->get('https://api.openai.com/v1/models');

            if ($response->successful()) {
                return [
                    'status' => 'connected',
                    'message' => 'OpenAI API connection successful',
                    'available_models' => collect($response->json()['data'] ?? [])
                        ->where('id', 'like', '%vision%')
                        ->pluck('id')
                        ->toArray()
                ];
            } else {
                return [
                    'status' => 'error',
                    'message' => 'OpenAI API connection failed: ' . $response->body()
                ];
            }
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Connection test failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Ottieni informazioni sul provider
     */
    public function getProviderInfo(): array
    {
        return [
            'name' => 'OpenAI Vision',
            'version' => '1.0',
            'model' => $this->config['model'],
            'capabilities' => [
                'image_analysis',
                'content_moderation',
                'object_detection',
                'text_extraction'
            ],
            'supported_formats' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
            'max_image_size' => '20MB',
            'rate_limits' => [
                'requests_per_minute' => 60,
                'tokens_per_minute' => 150000
            ]
        ];
    }
}
