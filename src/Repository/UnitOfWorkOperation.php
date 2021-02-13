<?php

/**
 * This file is part of the base-controller-bundle.
 */

namespace Kematjaya\BaseControllerBundle\Repository;

/**
 * @package Kematjaya\BaseControllerBundle\Repository
 * @license https://opensource.org/licenses/MIT MIT
 * @author  Nur Hidayatullah <kematjaya0@gmail.com>
 */
trait UnitOfWorkOperation 
{
    
    /**
     * 
     * @param entity $object
     * @return void
     * @throws \Exception
     */
    protected function create($object): void
    {
        $uow = $this->_em->getUnitOfWork();
        try{
            $this->_em->persist($object);
            $classMetadata      = $this->_em->getClassMetadata(get_class($object));
            $uow->computeChangeSet($classMetadata, $object);
        } catch (\Exception $ex) {
            throw $ex;
        }   
    }
    
    /**
     * 
     * @param entity $object
     * @return void
     * @throws \Exception
     */
    protected function doPersist($object): void
    {
        if (!$object->getId()) {
            $this->create($object);
            
            return;
        } 
        
        $uow = $this->_em->getUnitOfWork();
        try {
            $entityChangeSet    = $uow->getEntityChangeSet($object);
            $classMetadata      = $this->_em->getClassMetadata(get_class($object));
            $uow->computeChangeSet($classMetadata, $object);
            $entityChangeSetNew = array_merge($entityChangeSet, $uow->getEntityChangeSet($object));
            if (!empty($entityChangeSetNew)) {
                foreach ($entityChangeSetNew as $key => $value) {
                    $entityChangeSet[$key] = $value;
                }

                $uow->clearEntityChangeSet(spl_object_hash($object));
            }

            foreach ($entityChangeSet as $attribute => $value) {
                $uow->propertyChanged($object, $attribute, $value[0], $value[1]);
            }

            $this->_em->persist($object);
        } catch (\Exception $ex) {
            
            throw $ex;
        }
    }
}
