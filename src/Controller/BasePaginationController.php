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
        $this->name = "pagination_" . strtolower(str_replace("\\", "_", static::class));
        $this->paginator = $paginator;
    }

    /**
     * create Paginator object
     */
    protected function createPaginator(QueryBuilder $queryBuilder, Request $request): PaginationInterface
    {
        return $this->getPaginator()->paginate(
            $queryBuilder,
            $this->getPage($request),
            $this->processLimit($request)
        );
    }

    protected function createArrayPaginator(array $data, Request $request): PaginationInterface
    {
        return $this->getPaginator()->paginate(
            $data,
            $this->getPage($request),
            $this->processLimit($request)
        );
    }

    protected function processLimit(Request $request): int
    {
        $limit = is_numeric($request->get('_limit')) ? (int) $request->get('_limit') : null;
        if (null !== $limit) {
            $this->getSession()->set($this->name . '_limit', $limit);
        }

        // Scoped per controller: the previous global "limit" key let a page
        // size chosen on one list silently change every other list.
        return $this->getSession()->get($this->name . '_limit', $this->limit);
    }

    protected function getPage(Request $request): int
    {
        if (Request::METHOD_POST === $request->getMethod()) {
            return 1;
        }

        $session = $this->getSession();
        if (!$request->query->has("page")) {
            $page = $session->get($this->name);
            if (null === $page) {
                $session->set($this->name, 1);
                $page = $session->get($this->name);
            }

            return $page;
        }

        $session->set($this->name, $request->query->getInt("page"));

        return $session->get($this->name);
    }

    public function getPaginator(): PaginatorInterface
    {
        return $this->paginator;
    }
}
