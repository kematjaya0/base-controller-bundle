<?php

namespace Kematjaya\BaseControllerBundle\Controller;

use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @author apple
 */
interface TranslatorControllerInterface
{
    public const CONTROLLER_TAG_NAME = 'controller.translator_arguments';

    public function setTranslator(TranslatorInterface $translator): void;

    public function getTranslator(): TranslatorInterface;
}
