<?php

namespace IntelligentIntern\OpenAIBundle;

use App\DependencyInjection\ChatCompletion\ChatCompletionStrategyCompilerPass;
use App\DependencyInjection\Compiler\AIStrategyCompilerPass;
use App\DependencyInjection\Embedding\EmbeddingStrategyCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class OpenAIBundle extends AbstractBundle
{
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->import(__DIR__ . '/../config/services.yaml');
    }

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new EmbeddingStrategyCompilerPass());
        $container->addCompilerPass(new ChatCompletionStrategyCompilerPass());
    }
}
