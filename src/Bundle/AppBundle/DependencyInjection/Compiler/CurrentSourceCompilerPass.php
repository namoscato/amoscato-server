<?php

namespace Amoscato\Bundle\AppBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class CurrentSourceCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $currentSources = [];

        foreach ($container->findTaggedServiceIds('amoscato.current_source') as $id => $tags) {
            $currentSources[] = new Reference($id);
        }

        $container->getDefinition('amoscato.current_source.collection')->addArgument($currentSources);
    }
}
