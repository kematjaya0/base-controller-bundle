<?php

namespace Kematjaya\BaseControllerBundle\Filter;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @package Kematjaya\Filter
 *
 * @license https://opensource.org/licenses/MIT MIT
 * @author  Nur Hidayatullah <kematjaya0@gmail.com>
 */
abstract class AbstractFilterType extends AbstractType
{
    use FilterFunctionTrait;

    public function getBlockPrefix(): string
    {
        $class = explode('\\', strtolower(static::class));

        return end($class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => true,
            'validation_groups' => ['filtering'],
        ]);
    }
}
