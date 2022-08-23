<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPInterface.php to edit this template
 */

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
