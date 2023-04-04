<?php

namespace Kematjaya\BaseControllerBundle\Controller;

use Lexik\Bundle\FormFilterBundle\Filter\FilterBuilderUpdaterInterface;

/**
 *
 * @author apple
 */
interface LexikFilterControllerInterface
{
    const TAGGING_NAME = "controller.lexik_filter_arguments";
    
    public function setFilterBuilderUpdater(FilterBuilderUpdaterInterface $filterBuilderUpdater):void;
    
    public function getFilterBuilderUpdater():FilterBuilderUpdaterInterface;
}
