<?php

namespace IntelligentIntern\OpenAIBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use IntelligentIntern\OpenAIBundle\DependencyInjection\Compiler\AIServiceCompilerPass;

class OpenAIBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);
        $container->addCompilerPass(new AIServiceCompilerPass());
    }
}
