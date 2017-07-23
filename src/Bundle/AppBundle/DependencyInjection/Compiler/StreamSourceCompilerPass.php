<?php

namespace Amoscato\Bundle\AppBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class StreamSourceCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $streamSources = [];

        foreach ($container->findTaggedServiceIds('amoscato.stream_source') as $id => $tags) {
            $streamSources[] = new Reference($id);
        }

        $container->getDefinition('amoscato.stream_source.collection')->addArgument($streamSources);
    }
}
