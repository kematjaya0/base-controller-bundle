<?php

namespace Kematjaya\BaseControllerBundle\Controller;

use Twig\Environment;

/**
 *
 * @author apple
 */
interface TwigControllerInterface
{
    public function setTwig(Environment $twig):void;
    
    public function getTwig():Environment;
}
