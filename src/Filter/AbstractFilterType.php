<?php

/**
 * This file is part of the base-controller-bundle.
 */

namespace Kematjaya\BaseControllerBundle\Filter;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @package Kematjaya\Filter
 * @license https://opensource.org/licenses/MIT MIT
 * @author  Nur Hidayatullah <kematjaya0@gmail.com>
 */
abstract class AbstractFilterType extends AbstractType
{
    use FilterFunctionTrait;
    
    /**
     * 
     * @return string
     */
    public function getBlockPrefix()
    {
        $class = explode('\\', strtolower(get_class($this)));
        
        return end($class);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'csrf_protection'   => true,
            'validation_groups' => array('filtering') 
        ));
    }
}
