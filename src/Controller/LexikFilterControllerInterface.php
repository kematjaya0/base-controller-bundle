<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPInterface.php to edit this template
 */

namespace Kematjaya\BaseControllerBundle\Controller;

use Lexik\Bundle\FormFilterBundle\Filter\FilterBuilderUpdaterInterface;

/**
 *
 * @author apple
 */
interface LexikFilterControllerInterface 
{
    const TAG_NAME = "controller.lexik_filter_arguments";
    
    public function setFilterBuilderUpdater(FilterBuilderUpdaterInterface $filterBuilderUpdater):void;
    
    public function getFilterBuilderUpdater():FilterBuilderUpdaterInterface;
}
