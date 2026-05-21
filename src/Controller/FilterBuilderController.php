<?php

namespace Kematjaya\BaseControllerBundle\Controller;

use Doctrine\Common\Proxy\Proxy;
use Doctrine\ORM\QueryBuilder;
use Spiriit\Bundle\FormFilterBundle\Filter\FilterBuilderUpdaterInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * @package Kematjaya\BaseControllerBundle\Controller
 *
 * @license https://opensource.org/licenses/MIT MIT
 * @author  Nur Hidayatullah <kematjaya0@gmail.com>
 */
abstract class FilterBuilderController extends BasePaginationController implements LexikFilterControllerInterface
{
    protected FilterBuilderUpdaterInterface $filterBuilderUpdater;

    public function setFilterBuilderUpdater(FilterBuilderUpdaterInterface $filterBuilderUpdater): void
    {
        $this->filterBuilderUpdater = $filterBuilderUpdater;
    }

    public function getFilterBuilderUpdater(): FilterBuilderUpdaterInterface
    {
        return $this->filterBuilderUpdater;
    }

    /**
     * Process form with QueryBuilder object.
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
     * Creating filter form object.
     */
    protected function createFormFilter(string $type, array $options = []): FormInterface
    {
        $reflection = new \ReflectionClass($type);
        $name = \sprintf('%s', strtolower($reflection->getShortName()));
        $data = $this->getFilters($name);
        $form = parent::createForm($type, $data, $options);

        return $form;
    }

    /**
     * Reset filter value.
     */
    protected function resetFilters(FormInterface $form): void
    {
        $this->getSession()->set($form->getName(), null);
    }

    /**
     * Set filter value.
     */
    protected function setFilters(Request $request, FormInterface &$form)
    {
        if (Request::METHOD_GET === $request->getMethod()) {
            if ($request->query->get('_reset')) {
                $this->getSession()->set($this->name, 1); // reset pagination
                $type = \get_class($form->getConfig()->getType()->getInnerType());
                $options = $form->getConfig()->getOptions();
                $options['data'] = null;
                $form = parent::createForm($type, null, $options);
                $this->resetFilters($form);

                return $form;
            }

            return $form;
        }

        $this->getSession()->set($this->name, 1); // reset pagination
        $filters = $request->request->all()[$form->getName()];
        if ($filters) {
            $form->submit($filters);
            $this->getSession()->set($form->getName(), $form->getData());
        }

        return $form;
    }

    protected function updateFilter(Request $request, FormInterface $form): ?array
    {
        $this->setFilters($request, $form);

        return $this->getFilters($form->getName());
    }

    /**
     * get filter value.
     *
     * @return array|null
     */
    protected function getFilters(string $name)
    {
        $filters = $this->getSession()->get($name, null);
        if (!\is_array($filters)) {

            return null;
        }

        foreach ($filters as $k => $v) {
            if (!\is_object($v)) {
                continue;
            }

            $manager = $this->getDoctrine()->getManager();
            if (!$manager->getMetadataFactory()->isTransient($v::class) || $v instanceof Proxy) {
                $filters[$k] = $manager->getRepository($v::class)->find($v->getId());
            }
        }

        return $filters;
    }
}
