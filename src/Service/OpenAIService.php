<?php

namespace IntelligentIntern\OpenAIBundle\Service;

use App\Interface\AIServiceInterface;
use App\Service\VaultService;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use App\Interface\LogServiceInterface;


class OpenAIService implements AIServiceInterface
{
    private string $apiKey;
    private ?LogServiceInterface $logger = null;

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private VaultService                 $vaultService,
        private readonly LogServiceFactory   $logServiceFactory
    ) {
        $this->logger = $this->logServiceFactory->create();
        $config = $this->vaultService->fetchSecret('secret/data/data/openai');
        $this->apiKey = $config['api_key'] ?? throw new \RuntimeException('API Key for OpenAI is not set in Vault.');
    }

    public function supports(string $provider): bool
    {
        return strtolower($provider) === 'openai';
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
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
