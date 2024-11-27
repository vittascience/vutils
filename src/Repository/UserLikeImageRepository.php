<?php

namespace Utils\Repository;

use Doctrine\ORM\EntityRepository;
use Utils\Entity\UserLikeImage;

class UserLikeImageRepository extends EntityRepository
{
    public function getCountOfMyFavoriteSince($user = null, $since = null) {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('COUNT(g)')
            ->from(UserLikeImage::class, 'g')
            ->andWhere('g.user = :user')
            ->setParameter('user', $user);
        
        if ($since !== null) {
            $qb->andWhere('g.likedAt >= :since')
                ->setParameter('since', $since);
        }
        
        return $qb->getQuery()->getSingleScalarResult();
    }


    public function getMyFavoriteSince($user = null, $since = null, $limit = null, $offset = null) {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('g')
            ->from(UserLikeImage::class, 'g')
            ->andWhere('g.user = :user')
            ->setParameter('user', $user);
        
        if ($since !== null) {
            $qb->andWhere('g.likedAt >= :since')
                ->setParameter('since', $since);
        }
    
        // Gestion de limit
        if ($limit !== null) {
            $qb->setMaxResults($limit);
        }
    
        // Gestion de offset
        if ($offset !== null) {
            $qb->setFirstResult($offset);
        }
    
        return $qb->getQuery()->getResult();
    }

    public function getIdsMylatestFavorite($user = null, $page, $limit) {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('g')
            ->from(UserLikeImage::class, 'g')
            ->andWhere('g.user = :user')
            ->setParameter('user', $user)
            ->orderBy('g.likedAt', 'DESC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);
        
        return $qb->getQuery()->getResult();	
    }

    public function getIdsImagesOfMyFavorite($user = null) {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('g')
            ->from(UserLikeImage::class, 'g')
            ->andWhere('g.user = :user')
            ->setParameter('user', $user);
        
        return $qb->getQuery()->getResult();
    }

    public function getIdsOfMyLikedAssets($user = null) {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('ga.id') // Suppose que tu veux les IDs des assets
            ->from(UserLikeImage::class, 'g')
            ->join('g.generativeAssets', 'ga') // Joindre la relation
            ->andWhere('g.user = :user')
            ->setParameter('user', $user);
        
        return $qb->getQuery()->getResult();
    }
}
