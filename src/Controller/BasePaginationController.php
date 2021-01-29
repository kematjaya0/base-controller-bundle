<?php

/**
 * This file is part of the base-controller-bundle.
 */

namespace Kematjaya\BaseControllerBundle\Controller;

use Doctrine\ORM\QueryBuilder;
use Knp\Bundle\PaginatorBundle\Pagination\SlidingPaginationInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * @package Kematjaya\BaseControllerBundle\Controller
 * @license https://opensource.org/licenses/MIT MIT
 * @author  Nur Hidayatullah <kematjaya0@gmail.com>
 */
class BasePaginationController extends BaseController
{
    /**
     * 
     * @var PaginatorInterface
     */
    protected $paginator;
    
    /**
     * @var int
     */
    protected $limit = 20;
    
    public function __construct(PaginatorInterface $paginator, TranslatorInterface $translator) {
        
        $this->paginator = $paginator;
        parent::__construct($translator);
    }
    
    /**
     * create Paginator object
     * @param QueryBuilder $queryBuilder
     * @param Request $request
     * @return SlidingPaginationInterface
     */
    protected function createPaginator(QueryBuilder $queryBuilder, Request $request): SlidingPaginationInterface
    {
        if ($request->get('_limit') && is_numeric($request->get('_limit'))) {
            $request->getSession()->set('limit', $request->get('_limit'));
        }
        
        if (!$request->getSession()->get("limit")) {
            $request->getSession()->set('limit', $this->limit);
        }
        
        $limit = $request->getSession()->get("limit", $this->limit);
        
        return $this->paginator->paginate($queryBuilder, $request->query->getInt('page', 1), $limit);
    }
}
