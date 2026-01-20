<?php

namespace Kematjaya\BaseControllerBundle\Controller;

use Doctrine\ORM\QueryBuilder;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * @package Kematjaya\BaseControllerBundle\Controller
 * @license https://opensource.org/licenses/MIT MIT
 * @author  Nur Hidayatullah <kematjaya0@gmail.com>
 */
abstract class BasePaginationController extends BaseController implements PaginationControllerInterface
{
    protected PaginatorInterface $paginator;
    protected string $name;
    protected int $limit = 20;

    public function setPaginator(PaginatorInterface $paginator): void
    {
        $this->name = "pagination_" . strtolower(str_replace("\\", "_", get_class($this)));
        $this->paginator = $paginator;
    }

    /**
     * create Paginator object
     * @param QueryBuilder $queryBuilder
     * @param Request $request
     * @return PaginationInterface
     */
    protected function createPaginator(QueryBuilder $queryBuilder, Request $request): PaginationInterface
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
     * @return PaginationInterface
     */
    protected function createArrayPaginator(array $data = [], Request $request = null): PaginationInterface
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
    protected function processLimit(Request $request): int
    {
        $limit = is_numeric($request->query->get('_limit')) ? (int)$request->query->get('_limit') : null;
        if (null !== $limit) {
            $request->getSession()->set('limit', $limit);
        }

        return $request->getSession()->get("limit", $this->limit);
    }

    protected function getPage(Request $request): int
    {
        if (Request::METHOD_POST === $request->getMethod()) {
            return 1;
        }

        if (!$request->query->has("page")) {
            $page = $this->getSession()->get($this->name);
            if (null === $page) {
                $this->getSession()->set($this->name, 1);
                $page = $this->getSession()->get($this->name);
            }

            return $page;
        }

        $this->getSession()->set($this->name, $request->query->getInt("page"));

        return $this->getSession()->get($this->name);
    }

    public function getPaginator(): PaginatorInterface
    {
        return $this->paginator;
    }

}
