<?php

namespace Utils\Repository;

use Doctrine\ORM\EntityRepository;
use Utils\Entity\Competitions;

class CompetitionsRepository extends EntityRepository
{
    public function getAllCompetitions() {
        $dateNow = new \DateTime('now', new \DateTimeZone('Europe/Paris'));

        return $this->getEntityManager()->createQueryBuilder()
            ->select('c.name, c.monday, c.id')
            ->from(Competitions::class, 'c')
            ->where('c.monday < :dateNow') // Utilisation d'un paramètre nommé
            ->setParameter('dateNow', $dateNow) // Liaison du paramètre nommé à $dateNow
            ->orderBy('c.monday', 'DESC')
            ->getQuery()
            ->getResult();
    }

}
