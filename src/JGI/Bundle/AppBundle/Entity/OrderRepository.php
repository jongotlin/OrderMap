<?php

namespace JGI\Bundle\AppBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NoResultException;

class OrderRepository extends EntityRepository
{
    public function getForGeoLookup($limit)
    {
        return $this->createQueryBuilder('i')
            ->where('i.googleStatus is null')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;
    }

    public function getForMap()
    {
        return $this->createQueryBuilder('i')
            ->where("i.googleStatus = 'OK'")
            ->getQuery()
            ->getResult()
        ;
    }

    public function getNewest()
    {
        try {
            return $this->createQueryBuilder('i')
                ->setMaxResults(1)
                ->orderBy('i.orderDate', 'DESC')
                ->getQuery()
                ->getSingleResult()
            ;
        } catch (NoResultException $e) {
            return false;
        }
    }
}
