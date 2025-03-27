<?php

namespace Utils\Repository;

use Doctrine\ORM\EntityRepository;
use Utils\Entity\Competitions;
use Utils\Entity\GenerativeAssets;

class CompetitionsRepository extends EntityRepository
{
    public function getAllCompetitions() {
        $dateNow = new \DateTime('now', new \DateTimeZone('Europe/Paris'));

        return $this->getEntityManager()->createQueryBuilder()
            ->select('c.name, c.start_competition, c.end_competition,  c.id')
            ->from(Competitions::class, 'c')
            ->where('c.start_competition < :dateNow') // Utilisation d'un paramètre nommé
            ->setParameter('dateNow', $dateNow) // Liaison du paramètre nommé à $dateNow
            ->orderBy('c.start_competition', 'DESC')
            ->getQuery()
            ->getResult();
    }


    public function getTotalOfTheWeekCompetition($start, $end) {
        return $this->getEntityManager()->createQueryBuilder()
        ->select('COUNT(g.id)')
        ->from(GenerativeAssets::class, 'g')
        ->where('g.createdAt >= :start')
        ->andWhere('g.createdAt <= :end')
        ->andWhere('g.isCompetition = 1')
        ->andWhere('g.isPublic = 1')
        ->setParameter('start', $start)
        ->setParameter('end', $end)
        ->getQuery()
        ->getSingleScalarResult();
    }

}
