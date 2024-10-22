<?php

namespace Kematjaya\BaseControllerBundle\Controller;

use Spiriit\Bundle\FormFilterBundle\Filter\FilterBuilderUpdaterInterface;
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
