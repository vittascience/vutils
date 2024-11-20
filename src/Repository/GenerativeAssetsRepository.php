<?php

namespace Utils\Repository;

use User\Entity\User;
use Doctrine\ORM\EntityRepository;
use Utils\Entity\GenerativeAssets;

class GenerativeAssetsRepository extends EntityRepository
{
    public function getAssetsIfDuplicateExists(String $prompt, ?String $negativePrompt, $width, $height, $scale, $modelName)
    {
        $isDuplicate = $this->getEntityManager()->createQueryBuilder()
                ->select('g')
                ->from(GenerativeAssets::class, 'g')
                ->where('g.prompt = :prompt')
                ->andWhere('g.negativePrompt = :negativePrompt')
                ->andWhere('g.width = :width')
                ->andWhere('g.height = :height')
                ->andWhere('g.cfgScale = :scale')
                ->andWhere('g.modelName = :modelName')
                ->andWhere('g.creationSteps IS NOT NULL') // Ajout de la condition IS NOT NULL
                ->setParameter('prompt', $prompt)
                ->setParameter('negativePrompt', $negativePrompt)
                ->setParameter('width', $width)
                ->setParameter('height', $height)
                ->setParameter('scale', $scale)
                ->setParameter('modelName', $modelName)
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();
        
        return $isDuplicate;
    }

    
    public function getAllAssetsWithPrefix(String $prefix) {
        $prefixWithPercent = '%' . $prefix . '%';
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('g')
            ->from(GenerativeAssets::class, 'g')
            ->andWhere('g.name LIKE :name')
            ->andWhere('g.user IS NOT NULL')
            ->setParameter('name', $prefixWithPercent)
            ->orderBy('g.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        return $qb->getQuery()->getResult();
    }

    public function getAllMostPopularByCreatedAt($since, $limit, $offset) {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder();
        $queryBuilder->select('asset')
            ->from(GenerativeAssets::class, 'asset')
            ->where('asset.isPublic = :isPublic')
            ->andWhere('asset.createdAt >= :oneWeekAgo')
            ->orderBy('asset.likes', 'DESC')
            ->setParameter('isPublic', true)
            ->setParameter('oneWeekAgo', $since)
            ->setMaxResults($limit)
            ->setFirstResult($offset);

        return $publicGenerativeAssets = $queryBuilder->getQuery()->getResult();   
    }

    public function getCountAllMostPopularByCreatedAt($since, $mine = false, $user = null) {
        $queryBuilder = $this->createQueryBuilder('asset')
            ->select('COUNT(asset.id)')
            ->where('asset.isPublic = :isPublic')
            ->andWhere('asset.createdAt >= :oneWeekAgo')
            ->setParameter('isPublic', true)
            ->setParameter('oneWeekAgo', $oneWeekAgo);

        if ($mine && $user) {
            $queryBuilder->andWhere('asset.user = :user')
                         ->setParameter('user', $user);
        }

        return $queryBuilder->getQuery()->getSingleScalarResult();
    }

    public function getCountAll($asc = false, $mostLiked = false, $mine = false, $user = null) {
        $queryBuilder = $this->createQueryBuilder('asset')
            ->select('COUNT(asset.id)');

        if ($asc) {
            $queryBuilder->orderBy('asset.createdAt', 'ASC');
        } else if ($mostLiked) {
            $queryBuilder->orderBy('asset.likes', 'DESC');
        } else {
            $queryBuilder->orderBy('asset.createdAt', 'DESC');
        }

        if ($mine && $user) {
            $queryBuilder->andWhere('asset.user = :user')
                         ->setParameter('user', $user);
        }

        return $queryBuilder->getQuery()->getSingleScalarResult();
    }

    public function findByArrayOfIds(array $ids) {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('g')
            ->from(GenerativeAssets::class, 'g')
            ->where($qb->expr()->in('g.id', $ids))
            ->getQuery()
            ->getResult();

        return $qb->getQuery()->getResult();
    }
}
