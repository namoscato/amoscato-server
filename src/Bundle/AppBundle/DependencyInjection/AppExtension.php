<?php

namespace Amoscato\Bundle\AppBundle\DependencyInjection;

use Amoscato\Bundle\AppBundle\Current\CurrentSourceInterface;
use Amoscato\Bundle\AppBundle\Stream\Source\StreamSourceInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class AppExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');

        $container->registerForAutoconfiguration(CurrentSourceInterface::class)->addTag('amoscato.current_source');
        $container->registerForAutoconfiguration(StreamSourceInterface::class)->addTag('amoscato.stream_source');
    }
}
