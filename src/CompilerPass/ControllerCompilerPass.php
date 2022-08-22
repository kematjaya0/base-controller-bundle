<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

namespace Kematjaya\BaseControllerBundle\CompilerPass;

use Kematjaya\BaseControllerBundle\Controller\LexikFilterControllerInterface;
use Kematjaya\BaseControllerBundle\Controller\PaginationControllerInterface;
use Kematjaya\BaseControllerBundle\Controller\TranslatorControllerInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Description of ControllerCompilerPass
 *
 * @author apple
 */
class ControllerCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container):void 
    {
        $translatorServices = $container->findTaggedServiceIds(TranslatorControllerInterface::CONTROLLER_TAG_NAME);
        foreach (array_keys($translatorServices) as $className) {
            $container->findDefinition($className)->addMethodCall("setTranslator");
        }
        
        $paginatorServices = $container->findTaggedServiceIds(PaginationControllerInterface::TAG_NAME);
        foreach (array_keys($paginatorServices) as $className) {
            $container->findDefinition($className)->addMethodCall("setPaginator");
        }
        
        $filterServices = $container->findTaggedServiceIds(LexikFilterControllerInterface::TAGGING_NAME);
        foreach (array_keys($filterServices) as $className) {
            $container->findDefinition($className)->addMethodCall("setFilterBuilderUpdater");
        }
    }
}
