<?php

namespace Kematjaya\BaseControllerBundle\ClassFinder;

use HaydenPierce\ClassFinder\ClassFinder;
use ReflectionClass;

/**
 * @author Nur Hidayatullah <kematjaya0@gmail.com>
 */
class KmjClassFinder
{
    /**
     *
     * @param string $namespace
     * @param string $intefaceClass
     * @return array
     */
    public static function getClassesInNamespaceByInterface(string $namespace, string $intefaceClass = null): array
    {
        $classes = ClassFinder::getClassesInNamespace($namespace);

        if (null === $intefaceClass) {

            return $classes;
        }

        $data = [];
        foreach ($classes as $class) {
            $reflect = new ReflectionClass($class);
            if (!$reflect->implementsInterface($intefaceClass)) {
                continue;
            }

            $data[$class] = $class;
        }

        return $data;
    }
}
