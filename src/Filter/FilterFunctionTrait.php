<?php

/**
 * This file is part of the base-controller-bundle.
 */

namespace Kematjaya\BaseControllerBundle\Filter;

use Doctrine\ORM\Query\Expr;
use Spiriit\Bundle\FormFilterBundle\Filter\Query\QueryInterface;

/**
 * @package Kematjaya\BaseControllerBundle\Filter
 * @license https://opensource.org/licenses/MIT MIT
 * @author  Nur Hidayatullah <kematjaya0@gmail.com>
 */
trait FilterFunctionTrait
{
    /**
     * Resolves the Doctrine expression builder from the filter query.
     *
     * Spiriit 10/11 expose getExpr() on QueryInterface, while Spiriit 12 moved
     * it to the ORM implementation only. Both are handled here so the same
     * closures work across every supported release.
     */
    protected function getFilterExpr(QueryInterface $filterQuery): Expr
    {
        if (method_exists($filterQuery, 'getExpr')) {
            return $filterQuery->getExpr();
        }

        return $filterQuery->getQueryBuilder()->expr();
    }

    protected function JSONQuery(): \Closure
    {
        return function (QueryInterface $filterQuery, string $field, array $values): mixed {
            if (empty($values['value'])) {

                return null;
            }

            $expr = $this->getFilterExpr($filterQuery);
            $expression = $expr->like('TEXT(' . $field . ')', $expr->literal("%" . $values['value'] . "%"));

            return $filterQuery->createCondition($expression);
        };
    }

    protected function floatRangeQuery(): \Closure
    {
        return function (QueryInterface $filterQuery, string $field, array $values): mixed {
            if (!$values['value']) {
                return null;
            }

            if (is_null($values['value']['from']) && is_null($values['value']['to'])) {
                return null;
            }

            $from = null;
            $expr = $this->getFilterExpr($filterQuery);
            $condition = [];
            if (isset($values['value']['from']) && $values['value']['from']) {
                $fromVal = (float) str_replace(",", "", str_replace("Rp.", "", $values['value']['from']));
                if ($fromVal) {
                    $from = $expr->gte($field, $fromVal);
                    $condition[] = $from;
                }

            }

            $to = null;
            if (isset($values['value']['to']) && $values['value']['to']) {
                $toVal = (float) str_replace(",", "", str_replace("Rp.", "", $values['value']['to']));
                if ($toVal > 0) {
                    $to = $expr->lte($field, $toVal);
                    $condition[] = $to;
                }

            }

            if (!empty($condition)) {
                $condition = implode(" AND ", $condition);

                return $filterQuery->createCondition($condition);
            }

            return null;
        };
    }

    protected function dateRangeQuery(): \Closure
    {
        return function (QueryInterface $filterQuery, string $field, array $values): mixed {
            if (!$values['value']) {

                return null;
            }

            if (is_null($values['value']['from']) && is_null($values['value']['to'])) {

                return null;
            }

            $expr = $this->getFilterExpr($filterQuery);
            $condition = [];
            if (isset($values['value']['from']) && $values['value']['from']) {
                $condition[] = $expr->gte($field, $expr->literal($values['value']['from']->format("Y-m-d H:i:s")));
            }

            if (isset($values['value']['to']) && $values['value']['to']) {
                // Clone before normalising: setTime() mutates the object, and the
                // DateTime instance is shared with the form/filter values.
                $to = clone $values['value']['to'];
                $to->setTime(23, 59, 59);
                $condition[] = $expr->lte($field, $expr->literal($to->format("Y-m-d H:i:s")));
            }

            if (empty($condition)) {
                return null;
            }

            $condition = implode(" AND ", $condition);

            return $filterQuery->createCondition($condition);
        };
    }
}
