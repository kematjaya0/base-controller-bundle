<?php

namespace Kematjaya\BaseControllerBundle;

use Kematjaya\BaseControllerBundle\Controller\DoctrineManagerRegistryControllerInterface;
use Kematjaya\BaseControllerBundle\Controller\SessionControllerInterface;
use Kematjaya\BaseControllerBundle\Controller\TwigControllerInterface;
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
    public function build(ContainerBuilder $container):void
    {
        $container->registerForAutoconfiguration(TwigControllerInterface::class)
            ->addTag("controller.twig_arguments");

        $container->registerForAutoconfiguration(SessionControllerInterface::class)
            ->addTag(SessionControllerInterface::SESSION_TAGGING_NAME);

        $container->registerForAutoconfiguration(DoctrineManagerRegistryControllerInterface::class)
            ->addTag(DoctrineManagerRegistryControllerInterface::DOCTRINE_TAGGING_NAME);

        $container->registerForAutoconfiguration(TranslatorControllerInterface::class)
            ->addTag(TranslatorControllerInterface::CONTROLLER_TAG_NAME);

        $container->registerForAutoconfiguration(PaginationControllerInterface::class)
            ->addTag(PaginationControllerInterface::CONST_TAG_NAME);

        $container->registerForAutoconfiguration(LexikFilterControllerInterface::class)
            ->addTag(LexikFilterControllerInterface::TAGGING_NAME);

        $container->addCompilerPass(new ControllerCompilerPass());

        parent::build($container);
    }
}
