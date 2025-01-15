<?php

namespace IntelligentIntern\OpenAIBundle\Service;

use App\Interface\AIServiceInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use App\Service\VaultService;

class OpenAIService implements AIServiceInterface
{
    private string $apiKey;

    public function __construct(
        private HttpClientInterface $httpClient,
        private VaultService $vaultService,
        private LoggerInterface $logger
    ) {
        $config = $this->vaultService->fetchSecret('secret/data/data/openai'); // Flexibel für das Bundle
        $this->apiKey = $config['api_key'] ?? throw new \RuntimeException('API Key for OpenAI is not set in Vault.');
    }

    public function supports(string $provider): bool
    {
        return strtolower($provider) === 'openai';
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    public function setVaultService(VaultService $vaultService): void
    {
        $this->vaultService = $vaultService;
    }

    public function generateEmbedding(string $input): array
    {
        $this->logger->info('Generating embedding using OpenAI API.');

        $response = $this->httpClient->request('POST', 'https://api.openai.com/v1/embeddings', [
            'headers' => [
                'Authorization' => "Bearer {$this->apiKey}",
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'input' => $input,
                'model' => 'text-embedding-3-small',
            ],
        ]);

        $data = $response->toArray();

        return $data['data'] ?? [];
    }
}
