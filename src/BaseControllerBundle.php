<?php

namespace Kematjaya\BaseControllerBundle;

use Kematjaya\BaseControllerBundle\Controller\LexikFilterControllerInterface;
use Kematjaya\BaseControllerBundle\Controller\PaginationControllerInterface;
use Kematjaya\BaseControllerBundle\Controller\TranslatorControllerInterface;
use Kematjaya\BaseControllerBundle\CompilerPass\ControllerCompilerPass;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @author Nur Hidayatullah <kematjaya0@gmail.com>
 */
class BaseControllerBundle extends Bundle
{
    public function build(ContainerBuilder $container) 
    {
        $container->registerForAutoconfiguration(TranslatorControllerInterface::class)
                ->addTag(TranslatorControllerInterface::TAG_NAME);
        
        $container->registerForAutoconfiguration(PaginationControllerInterface::class)
                ->addTag(PaginationControllerInterface::TAG_NAME);
        
        $container->registerForAutoconfiguration(LexikFilterControllerInterface::class)
                ->addTag(LexikFilterControllerInterface::TAG_NAME);
        
        $container->addCompilerPass(new ControllerCompilerPass());
        
        parent::build($container);
    }
}
