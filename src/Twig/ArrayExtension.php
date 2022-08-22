<?php

/**
 * This file is part of the base-controller-bundle.
 */

namespace Kematjaya\BaseControllerBundle\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * @package Kematjaya\BaseControllerBundle\Twig
 * @license https://opensource.org/licenses/MIT MIT
 * @author  Nur Hidayatullah <kematjaya0@gmail.com>
 */
class ArrayExtension extends AbstractExtension 
{
    public function getFunctions():array
    {
        return [
            new TwigFunction('in_array', function($value, $arr) {
                return in_array($value, $arr);
            })
        ];
    }

}
