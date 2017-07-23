<?php

namespace Amoscato\Bundle\AppBundle;

use Amoscato\Bundle\AppBundle\DependencyInjection\Compiler\CurrentSourceCompilerPass;
use Amoscato\Bundle\AppBundle\DependencyInjection\Compiler\StreamSourceCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class AppBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new CurrentSourceCompilerPass());
        $container->addCompilerPass(new StreamSourceCompilerPass());
    }
}
