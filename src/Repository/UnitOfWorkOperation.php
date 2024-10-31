<?php

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
        $uow = $this->getEntityManager()->getUnitOfWork();
        try {
            $this->getEntityManager()->persist($object);
            $classMetadata = $this->getEntityManager()->getClassMetadata(get_class($object));
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
        $uow = $this->getEntityManager()->getUnitOfWork();
        if (null == $uow->getSingleIdentifierValue($object) and !$uow->isScheduledForInsert($object)) {
            $this->create($object);

            return;
        }

        try {
            $entityChangeSet = $uow->getEntityChangeSet($object);
            $classMetadata = $this->getEntityManager()->getClassMetadata(get_class($object));

            $uow->recomputeSingleEntityChangeSet($classMetadata, $object);
            $entityChangeSetNew = array_merge($entityChangeSet, $uow->getEntityChangeSet($object));

            if (!empty($entityChangeSetNew)) {
                foreach ($classMetadata->reflFields as $key => $v) {
                    if (!isset($entityChangeSetNew[$key])) {
                        continue;
                    }

                    $entityChangeSet[$key] = $entityChangeSetNew[$key];
                }

//                $uow->clearEntityChangeSet(spl_object_hash($object));
            }

            foreach ($entityChangeSet as $attribute => $value) {
                $uow->propertyChanged($object, $attribute, $value[0], $value[1]);
            }

            $this->getEntityManager()->persist($object);
        } catch (\Exception $ex) {

            throw $ex;
        }
    }
}
