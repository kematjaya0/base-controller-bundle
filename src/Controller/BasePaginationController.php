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
        return $this->paginator->paginate($queryBuilder, $request->query->getInt('page', 1), $this->processLimit($request));
    }
    
    /**
     * 
     * @param array $data
     * @param Request $request
     * @return SlidingPaginationInterface
     */
    protected function createArrayPaginator(array $data = [], Request $request): SlidingPaginationInterface
    {
        return $this->paginator->paginate($data, $request->query->getInt('page', 1), $this->processLimit($request));
    }
    
    /**
     * 
     * @param Request $request
     * @return int
     */
    protected function processLimit(Request $request):int
    {
        $limit = is_numeric($request->get('_limit')) ? (int) $request->get('_limit') : null;
        if (null !== $limit) {
            $request->getSession()->set('limit', $limit);
        }
        
        return $request->getSession()->get("limit", $limit);
    }
}
