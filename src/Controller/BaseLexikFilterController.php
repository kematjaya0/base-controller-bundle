<?php

namespace Kematjaya\BaseControllerBundle\Controller;

use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\Proxy;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Spiriit\Bundle\FormFilterBundle\Filter\FilterBuilderUpdaterInterface;


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
        $this->getSession()->set($form->getName(), null);
    }
    
    /**
     * Set filter value
     * @param Request $request
     * @param FormInterface $form
     */
    protected function setFilters(Request $request, FormInterface &$form)
    {
        $session = $this->getSession();
        if (Request::METHOD_GET === $request->getMethod()) {
            if ($request->query->get('_reset')) {
                $session->set($this->name, 1); // reset pagination
                
                $formType = $form->getConfig()->getType();
                // getInnerType() only exists on compound types. A filter type that
                // is not a DataClassType has no inner type to rebuild from, so the
                // stored filters are simply discarded instead of raising a fatal
                // "call to undefined method".
                $innerType = method_exists($formType, 'getInnerType') ? $formType->getInnerType() : null;
                if (null === $innerType) {
                    $this->resetFilters($form);
                    
                    return $form;
                }
                
                $type = get_class($innerType);
                $options = $form->getConfig()->getOptions();
                $options['data'] = null;
                $form = parent::createForm($type, null, $options);
                $this->resetFilters($form);

                return $form;
            }

            return $form;
        }

        $session->set($this->name, 1); // reset pagination
        $filters = $request->get($form->getName());
        if ($filters) {
            $form->submit($filters);
            $session->set($form->getName(), $form->getData());
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
        $filters = $this->getSession()->get($name, null);
        if (!is_array($filters)) {
            
            return null;
        }
        
        $manager = $this->getDoctrine()->getManager();
        foreach($filters as $k => $v)  {
            if (!is_object ($v)) {
                continue;
            }

            // Filters are read back from the session as detached instances, so
            // they are re-fetched to become managed again. Doctrine proxies
            // report the proxy class from get_class(), which the metadata
            // factory does not know about, so unwrap to the entity class first.
            $className = $v instanceof Proxy ? get_parent_class($v) : get_class($v);
            if (!$className || $manager->getMetadataFactory()->isTransient($className)) {
                continue;
            }
            
            $filters[$k] = $manager->getRepository($className)->find($v->getId());
        }
        
        return $filters;
    }
}
