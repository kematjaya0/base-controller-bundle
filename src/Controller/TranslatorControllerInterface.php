<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPInterface.php to edit this template
 */

namespace Kematjaya\BaseControllerBundle\Controller;

use Symfony\Contracts\Translation\TranslatorInterface;

/**
 *
 * @author apple
 */
interface TranslatorControllerInterface 
{
    const CONTROLLER_TAG_NAME = "controller.translator_arguments";
    
    public function setTranslator(TranslatorInterface $translator):void;
    
    public function getTranslator():TranslatorInterface;
}
