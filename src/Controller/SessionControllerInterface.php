<?php

namespace Kematjaya\BaseControllerBundle\Controller;


use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @author Nur Hidayatullah <kematjaya0@gmail.com>
 */
interface SessionControllerInterface
{
    const SESSION_TAGGING_NAME = "controller.session_arguments";
    public function setRequestStack(RequestStack $session):void;
    public function getSession():SessionInterface;
}