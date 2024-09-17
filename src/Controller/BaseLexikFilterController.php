<?php

namespace Kematjaya\BaseControllerBundle\Controller;

use Doctrine\ORM\QueryBuilder;
use Doctrine\Common\Proxy\Proxy;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Lexik\Bundle\FormFilterBundle\Filter\FilterBuilderUpdaterInterface;


/**
 * @package Kematjaya\BaseControllerBundle\Controller
 * @license https://opensource.org/licenses/MIT MIT
 * @author  Nur Hidayatullah <kematjaya0@gmail.com>
 */
abstract class BaseLexikFilterController extends BasePaginationController implements LexikFilterControllerInterface
{
    
    /**
     * 
     * @var FilterBuilderUpdaterInterface
     */
    protected $filterBuilderUpdater;
    
    public function setFilterBuilderUpdater(FilterBuilderUpdaterInterface $filterBuilderUpdater):void
    {
        $this->filterBuilderUpdater = $filterBuilderUpdater;
    }
    
    public function getFilterBuilderUpdater():FilterBuilderUpdaterInterface
    {
        return $this->filterBuilderUpdater;
    }
    
    
    /**
     * Process form with QueryBuilder object
     * @param Request $request
     * @param FormInterface $form
     * @param QueryBuilder $queryBuilder
     * @return QueryBuilder
     */
    protected function buildFilter(Request $request, FormInterface &$form, QueryBuilder $queryBuilder): QueryBuilder 
    {
        $this->setFilters($request, $form);
        if (null === $form->getData()) {

            return $queryBuilder;
        }

        return $this->getFilterBuilderUpdater()->addFilterConditions($form, $queryBuilder);
    }
    
    /**
     * Creating filter form object
     * @param string $type
     * @param object $data
     * @param array $options
     * @return FormInterface
     */
    protected function createFormFilter(string $type, array $options = array()): FormInterface 
    {
        $reflection = new \ReflectionClass($type);
        $name = sprintf("%s", strtolower($reflection->getShortName()));
        $data = $this->getFilters($name);
        $form = parent::createForm($type, $data, $options);
        
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
     */
    protected function setFilters(Request $request, FormInterface &$form)
    {
        $this->get('session')->set($this->name, 1);
        if (Request::METHOD_GET === $request->getMethod()) {
            if ($request->query->get('_reset')) {
                $type = get_class($form->getConfig()->getType()->getInnerType());
                $options = $form->getConfig()->getOptions();
                $options['data'] = null;
                $form = parent::createForm($type, null, $options);
                $this->resetFilters($form);

                return $form;
            }

            return $form;
        }
        
        $filters = $request->get($form->getName());
        if ($filters) {
            $form->submit($filters);
            $this->get('session')->set($form->getName(), $form->getData());
        }
        
        return $form;
    }
    
    
    protected function updateFilter(Request $request, FormInterface $form):?array
    {
        $this->setFilters($request, $form);
        
        return $this->getFilters($form->getName());
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
                $filters[$k] = $manager->getRepository(get_class($v))->find($v->getId());
            }
        }
        
        return $filters;
    }
}
