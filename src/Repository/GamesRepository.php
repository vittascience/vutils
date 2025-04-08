<?php

namespace Utils\Repository;

use Doctrine\ORM\EntityRepository;
use Utils\Entity\Games;
use Utils\Entity\GenerativeAssets;

class GamesRepository extends EntityRepository
{
    public function getAllGames() {
        $dateNow = new \DateTime('now', new \DateTimeZone('Europe/Paris'));

        return $this->getEntityManager()->createQueryBuilder()
            ->select('c.prompt, c.image, c.start_game, c.end_game,  c.id')
            ->from(Games::class, 'c')
            ->where('c.start_game < :dateNow') // Utilisation d'un paramètre nommé
            ->setParameter('dateNow', $dateNow) // Liaison du paramètre nommé à $dateNow
            ->orderBy('c.start_game', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function getCurrentGame() {
        $dateNow = new \DateTime('now', new \DateTimeZone('Europe/Paris'));

        return $this->getEntityManager()->createQueryBuilder()
            ->select('c.prompt, c.image, c.start_game, c.end_game, c.id')
            ->from(Games::class, 'c')
            ->where('c.start_game <= :dateNow') // Utilisation d'un paramètre nommé
            ->andWhere('c.end_game > :dateNow') // Utilisation d'un paramètre nommé
            ->setParameter('dateNow', $dateNow) // Liaison du paramètre nommé à $dateNow
            ->orderBy('c.start_game', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function getTotalOfTheGame($start, $end) {

        return $this->getEntityManager()->createQueryBuilder()
        ->select('COUNT(g.id)')
        ->from(GenerativeAssets::class, 'g')
        ->where('g.createdAt BETWEEN :start AND :end')
        ->andWhere('g.score IS NOT NULL')
        ->setParameter('start', $start)
        ->setParameter('end', $end)
        ->getQuery()
        ->getSingleScalarResult();
    }



}