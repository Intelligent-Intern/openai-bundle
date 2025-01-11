<?php

namespace IntelligentIntern\OpenAIBundle\Service;

use App\Service\Api\Strategies\AIServiceInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class OpenAIService implements AIServiceInterface
{
    private HttpClientInterface $httpClient;
    private string $apiKey;

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
        $this->apiKey = $_ENV['OPENAI_API_KEY'] ?? throw new \RuntimeException('OPENAI_API_KEY is not set');
    }

    public function generateEmbedding(string $input): array
    {
        $response = $this->httpClient->request('POST', 'https://api.openai.com/v1/embeddings', [
            'headers' => [
                'Authorization' => "Bearer {$this->apiKey}",
                'Content-Type' => 'application/json',
            ],
            'json' => ['model' => 'text-embedding-ada-002', 'input' => $input],
        ]);

        $data = $response->toArray();
        return $data['data'][0]['embedding'] ?? throw new \RuntimeException('Failed to fetch embedding');
    }
}
