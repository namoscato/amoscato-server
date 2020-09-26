<?php

declare(strict_types=1);

namespace Amoscato;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    private const CONFIG_EXTS = '.{php,xml,yaml,yml}';

    protected function configureContainer(ContainerConfigurator $c): void
    {
        $c->import($this->getProjectDir().'/config/bundles.php');
        $c->parameters()->set('container.dumper.inline_class_loader', true);

        $confDir = $this->getProjectDir().'/config';
        $c->import($confDir.'/{packages}/*'.self::CONFIG_EXTS, 'glob');
        $c->import($confDir.'/{packages}/'.$this->environment.'/**/*'.self::CONFIG_EXTS, 'glob');
        $c->import($confDir.'/{services}'.self::CONFIG_EXTS, 'glob');
        $c->import($confDir.'/{services}_'.$this->environment.self::CONFIG_EXTS, 'glob');
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $confDir = $this->getProjectDir().'/config';

        $routes->import($confDir.'/{routes}/*'.self::CONFIG_EXTS, 'glob');
        $routes->import($confDir.'/{routes}/'.$this->environment.'/**/*'.self::CONFIG_EXTS, 'glob');
        $routes->import($confDir.'/{routes}'.self::CONFIG_EXTS, 'glob');
    }
}
