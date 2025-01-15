<?php

namespace IntelligentIntern\OpenAIBundle;

use IntelligentIntern\OpenAIBundle\DependencyInjection\Compiler\AIServiceCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OpenAIBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);
        $container->addCompilerPass(new AIServiceCompilerPass());
    }
}
