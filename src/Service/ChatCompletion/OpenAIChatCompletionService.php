<?php

namespace IntelligentIntern\OpenAIBundle\Service\ChatCompletion;

use App\DTO\ModelConfigDTO;
use App\Factory\LogServiceFactory;
use App\Service\VaultService;
use App\Contract\ChatHistoryInterface;
use App\Contract\ChatCompletionServiceInterface;
use App\Contract\LogServiceInterface;
use App\Contract\RateLimiterInterface;
use App\DTO\ChatCompletionResult;
use App\Contract\AskPermissionInterface;
use OpenAI;
use OpenAI\Client as OpenAIClient;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Throwable;

class OpenAIChatCompletionService implements ChatCompletionServiceInterface
{
    private string $apiKey;
    private array $models;
    private LogServiceInterface $logger;

    /**
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     */
    public function __construct(
        private readonly LogServiceFactory $logServiceFactory,
        private readonly RateLimiterInterface $rateLimiter,
        private readonly VaultService $vaultService,
        private readonly AskPermissionInterface $askPermission
    ) {
        $this->logger = $this->logServiceFactory->create();
        $config = $this->vaultService->fetchSecret('secret/data/data/openai');

        $models = $config['models'] ?? throw new \RuntimeException('Model configurations missing.');
        $models_std_class = json_decode($models);
        $this->models = json_decode(json_encode($models_std_class), true);

        $this->apiKey = $config['api_key'] ?? throw new \RuntimeException('API Key for OpenAI is not set in Vault.');
    }

    public function supports(string $provider): bool
    {
        return strtolower($provider) === 'openai';
    }

    /**
     * @throws Throwable
     */
    public function generateResponse(string $model, ChatHistoryInterface $chatHistory, array $options = []): ChatCompletionResult
    {
        $modelConfig = $this->getModelConfig($model);

//        $this->askPermission
//            ->addProvider('openai')
//            ->addChatHistory($chatHistory)
//            ->addModelConfig($modelConfig);
//
//        $permissionRequest = $this->rateLimiter->acquirePermit($this->askPermission);
//
//        if (!$permissionRequest->isGranted()) {
//            throw new \RuntimeException('Permission to perform this request was denied.');
//        }

//        $this->logger->info('Rate limiter granted permission.', [
//            'metadata' => $permissionRequest->getMetadata(),
//        ]);

        $messages = [];
        $messageEntries = $chatHistory->getChatMessageEntries();
        foreach($messageEntries as $messageEntry) {
            $messages[] = ['content' => $messageEntry->getMessage(), 'role' => $messageEntry->getRole()];
        }

        $payload = array_merge([
            'model' => $modelConfig->getDeploymentId(),
            'messages' => $messages,
            'temperature' => $options['temperature'] ?? 0.7,
            'max_tokens' => $options['max_tokens'] ?? 512,
            'top_p' => $options['top_p'] ?? 1.0,
            'n' => $options['n'] ?? 1,
        ], $options);


//        $tokensExpected = $permissionRequest->getMetadata()['tokensExpected'] ?? 'unknown';
        $tokensExpected = 1000;
        $this->logger->info('Sending chat request to OpenAI.', [
            'payload' => $payload,
            'tokensExpected' => $tokensExpected,
        ]);
        try {
            $client = OpenAI::client($this->apiKey);
            $response = $client->chat()->create($payload);
            if (empty($response->choices)) {
                throw new \RuntimeException('No choices returned from OpenAI Chat Completion.');
            }
            $tokensUsed = [
                'requestTokens' => $response->usage->prompt_tokens ?? 0,
                'responseTokens' => $response->usage->completion_tokens ?? 0,
                'totalTokens' => $response->usage->total_tokens ?? 0,
            ];
            $this->logger->info('Received response from OpenAI.', [
                'response' => $response,
                'tokensUsed' => $tokensUsed,
            ]);

            return new ChatCompletionResult(
                content: $response->choices[0]->message->content ?? throw new \RuntimeException('No content in response.'),
                modelUsed: $modelConfig->getDeploymentId(),
                requestId: $response->id ?? 'unknown',
                rawChoices: $response->choices
            );
        } catch (Throwable $e) {
            $this->logger->error('Error during OpenAI chat completion request.', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    private function getModelConfig(string $model): ModelConfigDTO
    {
        if (!isset($this->models[$model])) {
            throw new \RuntimeException("Model configuration for '$model' not found.");
        }
        $config = $this->models[$model];
        return new ModelConfigDTO(
            deploymentId: $config['deploymentId'] ?? throw new \RuntimeException("Missing 'deploymentId' for model '$model'."),
            apiVersion: $config['apiVersion'] ?? throw new \RuntimeException("Missing 'apiVersion' for model '$model'."),
            rpm: $config['rpm'] ?? throw new \RuntimeException("Missing 'rpm' for model '$model'."),
            tpm: $config['tpm'] ?? throw new \RuntimeException("Missing 'tpm' for model '$model'."),
            modelName: $config['deploymentId']
        );
    }
}
