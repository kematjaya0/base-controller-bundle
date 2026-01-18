<?php

namespace Kematjaya\BaseControllerBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
/**
 * @author Nur Hidayatullah <kematjaya0@gmail.com>
 */
class BaseControllerExtension extends Extension 
{
    
    public function load(array $configs, ContainerBuilder $container) :void
    {
        $locator = new FileLocator(__DIR__.'/../Resources/config');
        $loader = new YamlFileLoader($container, $locator);
        $loader->load('services.yml');
    }

}
