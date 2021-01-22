<?php

/**
 * This file is part of the base-controller-bundle.
 */

namespace Kematjaya\BaseControllerBundle\Controller;

use Doctrine\ORM\QueryBuilder;
use Doctrine\Common\Proxy\Proxy;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;
use Lexik\Bundle\FormFilterBundle\Filter\FilterBuilderUpdaterInterface;
use Knp\Component\Pager\PaginatorInterface;
use Knp\Bundle\PaginatorBundle\Pagination\SlidingPaginationInterface;

/**
 * @package Kematjaya\BaseControllerBundle\Controller
 * @license https://opensource.org/licenses/MIT MIT
 * @author  Nur Hidayatullah <kematjaya0@gmail.com>
 */
abstract class BaseLexikFilterController extends BaseController
{
    
    /**
     * 
     * @var FilterBuilderUpdaterInterface
     */
    protected $filterBuilderUpdater;
    
    /**
     * 
     * @var PaginatorInterface
     */
    protected $paginator;
    
    /**
     * @var int
     */
    protected $limit = 20;
    
    public function __construct(TranslatorInterface $translator, FilterBuilderUpdaterInterface $filterBuilderUpdater, PaginatorInterface $paginator) 
    {
        $this->filterBuilderUpdater = $filterBuilderUpdater;
        $this->paginator = $paginator;
        
        parent::__construct($translator);
    }
    
    /**
     * create paginator object
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
    
    /**
     * Process form with QueryBuilder object
     * @param Request $request
     * @param FormInterface $form
     * @param QueryBuilder $queryBuilder
     * @return QueryBuilder
     */
    protected function buildFilter(Request $request, FormInterface $form, QueryBuilder $queryBuilder): QueryBuilder 
    {
        $this->setFilters($request, $form);
        
        return $this->filterBuilderUpdater->addFilterConditions($form, $queryBuilder);
    }
    
    
    /**
     * Creating filter form object
     * @param string $type
     * @param type $data
     * @param array $options
     * @return FormInterface
     */
    protected function createFormFilter(string $type, $data = null, array $options = array()): FormInterface 
    {
        $form = parent::createForm($type, $data, $options);
        $filters = $this->getFilters($form->getName());
        if (!empty($filters)) {
            $form->setData($this->getFilters($form->getName()));
        }
        
        return $form;
    }
    
    /**
     * Reset filter value
     * 
     * @param FormInterface $form
     */
    protected function resetFilters(FormInterface $form):void
    {
        $this->get('session')->set($form->getName(), null);
    }
    
    /**
     * Set filter value
     * @param Request $request
     * @param FormInterface $form
     * @return type
     */
    protected function setFilters(Request $request, FormInterface $form)
    {
        if ($request->get('_reset')) {
            $form->setData(null);
            $this->resetFilters($form);
        }
        
        $filters = $request->get($form->getName());
        if ($filters) {
            $form->submit($filters);
            $this->get('session')->set($form->getName(), $form->getData());
        } else {
            $filters = $this->getFilters($form->getName());
        }
        
        return $filters;
    }
    
    /**
     * get filter value
     * @param string $name
     * @return array|null 
     */
    protected function getFilters(string $name)
    {
        $filters = $this->get('session')->get($name, null);
        if (!is_array($filters)) {
            
            return null;
        }
        
        foreach($filters as $k => $v)  {
            if (!is_object ($v)) {
                continue;
            }

            $manager = $this->getDoctrine()->getManager();
            if (!$manager->getMetadataFactory()->isTransient(get_class($v)) or $v instanceof Proxy) {
                $filters[$k] = $manager->persist($v);
            }
        }
        
        return $filters;
    }
}
