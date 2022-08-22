<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPInterface.php to edit this template
 */

namespace Kematjaya\BaseControllerBundle\Controller;

use Knp\Component\Pager\PaginatorInterface;

/**
 *
 * @author apple
 */
interface PaginationControllerInterface 
{
    const CONST_TAG_NAME = "controller.pagination_arguments";
    
    public function setPaginator(PaginatorInterface $paginator):void;
    
    public function getPaginator():PaginatorInterface;
}
