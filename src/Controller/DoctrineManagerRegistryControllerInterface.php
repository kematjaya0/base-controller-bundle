<?php

namespace Kematjaya\BaseControllerBundle\Controller;


use Doctrine\Persistence\ManagerRegistry;

/**
 * @author Nur Hidayatullah <kematjaya0@gmail.com>
 */
interface DoctrineManagerRegistryControllerInterface
{
    const DOCTRINE_TAGGING_NAME = "controller.doctrine_arguments";

    public function setManagerRegistry(ManagerRegistry $session):void;

    public function getDoctrine():ManagerRegistry;
}