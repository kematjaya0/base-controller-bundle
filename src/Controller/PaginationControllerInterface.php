<?php

namespace Kematjaya\BaseControllerBundle\Controller;

use Knp\Component\Pager\PaginatorInterface;

/**
 * @author apple
 */
interface PaginationControllerInterface
{
    public const CONST_TAG_NAME = 'controller.pagination_arguments';

    public function setPaginator(PaginatorInterface $paginator): void;

    public function getPaginator(): PaginatorInterface;
}
