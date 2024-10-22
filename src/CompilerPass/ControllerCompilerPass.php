<?php

namespace Kematjaya\BaseControllerBundle\CompilerPass;

use Kematjaya\BaseControllerBundle\Controller\DoctrineManagerRegistryControllerInterface;
use Kematjaya\BaseControllerBundle\Controller\SessionControllerInterface;
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
    public function process(ContainerBuilder $container): void
    {
        $translatorServices = $container->findTaggedServiceIds(TranslatorControllerInterface::CONTROLLER_TAG_NAME);
        foreach (array_keys($translatorServices) as $className) {
            $container->findDefinition($className)->addMethodCall("setTranslator");
        }

        $sessionServices = $container->findTaggedServiceIds(SessionControllerInterface::SESSION_TAGGING_NAME);
        foreach (array_keys($sessionServices) as $className) {
            $container->findDefinition($className)->addMethodCall("setRequestStack");
        }

        $doctrineServices = $container->findTaggedServiceIds(DoctrineManagerRegistryControllerInterface::DOCTRINE_TAGGING_NAME);
        foreach (array_keys($doctrineServices) as $className) {
            $container->findDefinition($className)->addMethodCall("setManagerRegistry");
        }

        $twigServices = $container->findTaggedServiceIds("controller.twig_arguments");
        foreach (array_keys($twigServices) as $className) {
            $container->findDefinition($className)->addMethodCall("setTwig");
        }

        $paginatorServices = $container->findTaggedServiceIds(PaginationControllerInterface::CONST_TAG_NAME);
        foreach (array_keys($paginatorServices) as $className) {
            $container->findDefinition($className)->addMethodCall("setPaginator");
        }

        $filterServices = $container->findTaggedServiceIds(LexikFilterControllerInterface::TAGGING_NAME);
        foreach (array_keys($filterServices) as $className) {
            $container->findDefinition($className)->addMethodCall("setFilterBuilderUpdater");
        }
    }
}
