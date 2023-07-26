<?php

namespace Kematjaya\BaseControllerBundle\Controller;

use Doctrine\ORM\QueryBuilder;
use Knp\Bundle\PaginatorBundle\Pagination\SlidingPaginationInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * @package Kematjaya\BaseControllerBundle\Controller
 * @license https://opensource.org/licenses/MIT MIT
 * @author  Nur Hidayatullah <kematjaya0@gmail.com>
 */
abstract class BasePaginationController extends BaseController implements PaginationControllerInterface
{
    /**
     *
     * @var PaginatorInterface
     */
    protected $paginator;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var int
     */
    protected $limit = 20;

    public function setPaginator(PaginatorInterface $paginator):void
    {
        $this->name = "pagination_".strtolower(str_replace("\\", "_", get_class($this)));
        $this->paginator = $paginator;
    }

    /**
     * create Paginator object
     * @param QueryBuilder $queryBuilder
     * @param Request $request
     * @return SlidingPaginationInterface
     */
    protected function createPaginator(QueryBuilder $queryBuilder, Request $request): SlidingPaginationInterface
    {
        return $this->getPaginator()->paginate(
            $queryBuilder,
            $this->getPage($request),
            $this->processLimit($request)
        );
    }

    /**
     *
     * @param array $data
     * @param Request $request
     * @return SlidingPaginationInterface
     */
    protected function createArrayPaginator(array $data = [], Request $request): SlidingPaginationInterface
    {
        return $this->getPaginator()->paginate(
            $data,
            $this->getPage($request),
            $this->processLimit($request)
        );
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

        return $request->getSession()->get("limit", $this->limit);
    }

    protected function getPage(Request $request):int
    {
        if (!$request->query->has("page")) {
            $page = $this->get('session')->get($this->name);
            if (null === $page) {
                $this->get('session')->set($this->name, 1);
                $page = $this->get('session')->get($this->name);
            }

            return $page;
        }

        $this->get('session')->set($this->name, $request->query->getInt("page"));

        return $this->get('session')->get($this->name);
    }

    public function getPaginator(): PaginatorInterface
    {
        return $this->paginator;
    }

}
