<?php

namespace Kematjaya\BaseControllerBundle\Filter;

use Spiriit\Bundle\FormFilterBundle\Filter\Query\QueryInterface;

/**
 * @package Kematjaya\BaseControllerBundle\Filter
 * @license https://opensource.org/licenses/MIT MIT
 * @author  Nur Hidayatullah <kematjaya0@gmail.com>
 */
trait FilterFunctionTrait 
{
    protected function JSONQuery()
    {
        return function(QueryInterface $filterQuery, $field, $values) {
            if (empty($values['value'])) {
                
                return null;
            }

            $expr = $filterQuery->getExpr();
            $expression = $expr->like('TEXT('.$field.')', $expr->literal("%" . $values['value'] . "%"));
            
            return $filterQuery->createCondition($expression);
        };
    }
    
    protected function floatRangeQuery()
    {
        return function(QueryInterface $filterQuery, $field, $values) {
            if (!$values['value']) {
                return false;
            }

            if (is_null($values['value']['from']) && is_null($values['value']['to'])) {
                return false;
            }

            $from = null;
            $expr = $filterQuery->getQueryBuilder()->expr();
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
            
            return '';
        };
    }
    
    protected function dateRangeQuery() 
    {
        return function (QueryInterface $filterQuery, $field, $values) {
            if (!$values['value']) {
                
                return false;
            }

            if (is_null($values['value']['from']) && is_null($values['value']['to'])) {
                
                return false;
            }

            $from = null;
            $expr = $filterQuery->getQueryBuilder()->expr();
            $condition = [];
            if (isset($values['value']['from']) && $values['value']['from']) {
                $from = $expr->gte($field, $filterQuery->getExpr()->literal($values['value']['from']->format("Y-m-d H:i:s")));
                $condition[] = $from;
            }

            $to = null;
            if (isset($values['value']['to']) && $values['value']['to']) {
                $values['value']['to']->setTime(23, 59, 59);
                $to = $expr->lte($field, $filterQuery->getExpr()->literal($values['value']['to']->format("Y-m-d H:i:s")));
                $condition[] = $to;
            }

            $condition = implode(" AND ", $condition);
            
            return $filterQuery->createCondition($condition);
        };
    }
}
