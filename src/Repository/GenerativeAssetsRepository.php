<?php

namespace Utils\Repository;

use User\Entity\User;
use Doctrine\ORM\EntityRepository;
use Utils\Entity\GenerativeAssets;

class GenerativeAssetsRepository extends EntityRepository
{
    public function getAssetsIfDuplicateExists(String $prompt, ?String $negativePrompt, $width, $height, $scale, $modelName)
    {
        $response = $this->getEntityManager()->createQueryBuilder()
                ->select('g')
                ->from(GenerativeAssets::class, 'g')
                ->where('g.prompt = :prompt')
                ->andWhere('g.negativePrompt = :negativePrompt')
                ->andWhere('g.width = :width')
                ->andWhere('g.height = :height')
                ->andWhere('g.cfgScale = :scale')
                ->andWhere('g.modelName = :modelName')
                ->andWhere('g.creationSteps IS NOT NULL')
                ->setParameter('prompt', $prompt)
                ->setParameter('negativePrompt', $negativePrompt)
                ->setParameter('width', $width)
                ->setParameter('height', $height)
                ->setParameter('scale', $scale)
                ->setParameter('modelName', $modelName)
                ->setMaxResults(10)
                ->getQuery()
                ->getResult();

        return $response;
    }


    public function getAllAssetsIfDuplicateExists(String $prompt, ?String $negativePrompt, $width, $height, $scale, $modelName)
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
                ->getQuery()
                ->getResult();

        return $isDuplicate;
    }
    
    public function getAllAssetsWithPrefix(String $prefix, User $user = null) {
        $prefixWithPercent = '%' . $prefix . '%';
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('g')
            ->from(GenerativeAssets::class, 'g')
            ->andWhere('g.name LIKE :name')
            ->setParameter('name', $prefixWithPercent)
            ->orderBy('g.createdAt', 'DESC');

        if ($user !== null) {
            $qb->andWhere('g.user = :user')
               ->setParameter('user', $user);
        }
        
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

    public function getMyMostPopularByCreatedAt($since, $limit, $offset, User $user) {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder();
        $queryBuilder->select('asset')
            ->from(GenerativeAssets::class, 'asset')
            ->where('asset.user = :user')
            ->andWhere('asset.createdAt >= :oneWeekAgo')
            ->orderBy('asset.likes', 'DESC')
            ->setParameter('user', $user)
            ->setParameter('oneWeekAgo', $since)
            ->setMaxResults($limit)
            ->setFirstResult($offset);

        return $publicGenerativeAssets = $queryBuilder->getQuery()->getResult();
    }

    public function getCountAllMostPopularByCreatedAt($since, $mine = false, $user = null, $isPublic = null) {
        $queryBuilder = $this->createQueryBuilder('asset')
            ->select('COUNT(asset.id)')
            ->where('asset.createdAt >= :oneWeekAgo')
            ->setParameter('oneWeekAgo', $since);

        if ($mine && $user) {
            $queryBuilder->andWhere('asset.user = :user')
                         ->setParameter('user', $user);
        }

        if ($isPublic !== null) {
            $queryBuilder->andWhere('asset.isPublic = :isPublic')
                         ->setParameter('isPublic', $isPublic);
        }

        return $queryBuilder->getQuery()->getSingleScalarResult();
    }

    public function getCountAll($asc = false, $mostLiked = false, $mine = false, $user = null, $isPublic = null) {
        $queryBuilder = $this->createQueryBuilder('asset')
            ->select('COUNT(asset.id)');

        if ($mine && $user) {
            $queryBuilder->andWhere('asset.user = :user')
                         ->setParameter('user', $user);
        }

        if ($isPublic !== null) {
            $queryBuilder->andWhere('asset.isPublic = :isPublic')
                         ->setParameter('isPublic', $isPublic);
        }

        if ($mostLiked) {
            $queryBuilder->orderBy('asset.likes', 'DESC');
        } else {
            $queryBuilder->orderBy('asset.createdAt', $asc ? 'ASC' : 'DESC');
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

    public function getCountOfNonReviewedAssets() {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('count(g)')
            ->from(GenerativeAssets::class, 'g')
            ->andWhere('g.adminReview = 0')
            ->getQuery()
            ->getResult();

        return $qb->getQuery()->getSingleScalarResult();
    }

    public function getAllMostPopularFromArray($limit, $offset, array $ids) {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder();
        $queryBuilder->select('asset')
            ->from(GenerativeAssets::class, 'asset')
            ->andWhere($queryBuilder->expr()->in('asset.id', $ids))
            ->orderBy('asset.likes', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset);

        return $queryBuilder->getQuery()->getResult();   
    }

    public function getAllMostPopularSinceFromArray($limit, $offset, array $ids, $since = null) {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder();
        $queryBuilder->select('asset')
            ->from(GenerativeAssets::class, 'asset')
            ->andWhere($queryBuilder->expr()->in('asset.id', $ids))
            ->setMaxResults($limit)
            ->setFirstResult($offset);
        
        if ($since !== null) {
            $queryBuilder->andWhere('asset.createdAt >= :since')
                         ->setParameter('since', $since)
                         ->orderBy('asset.likes', 'DESC');
        } else {
            $queryBuilder->orderBy('asset.createdAt', 'DESC');
        }

        return $queryBuilder->getQuery()->getResult();   
    }

    public function findAssetsByWeek(\DateTime $startOfWeek, \DateTime $endOfWeek, bool $isPublic, $limit, $offset){
        $queryBuilder = $this->getEntityManager()->createQueryBuilder();
        $queryBuilder->select('asset')
        ->from(GenerativeAssets::class, 'asset')
        ->where('asset.isPublic = :isPublic')
        ->andWhere('asset.user IS NOT NULL')
        ->andWhere('asset.createdAt BETWEEN :start AND :end')
        ->andWhere('asset.isCompetition = 1')
        ->orderBy('asset.likes', 'DESC')
        ->setParameter('isPublic', $isPublic)
        ->setParameter('start', $startOfWeek)
        ->setParameter('end', $endOfWeek)
        ->setMaxResults($limit)
        ->setFirstResult($offset);
        
        return $queryBuilder->getQuery()->getResult();
    }
    
    function getCountOfAnormalAssets()
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "SELECT COUNT(*) as total 
                FROM generative_assets 
                WHERE CAST((LENGTH(creation_steps) - LENGTH(REPLACE(creation_steps, '.png', ''))) / 4 AS UNSIGNED) <> 6;";
        $result = $conn->executeQuery($sql);
        return $result->fetchOne();
    }

    function getAnormalAssets($limit, $offset)
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "SELECT *
                FROM generative_assets
                WHERE (LENGTH(creation_steps) - LENGTH(REPLACE(creation_steps, '.png', ''))) != 24
                ORDER BY created_at DESC
                LIMIT :limit OFFSET :offset";
    
        $stmt = $conn->executeQuery(
            $sql,
            ['limit' => $limit, 'offset' => $offset],
            ['limit' => \PDO::PARAM_INT, 'offset' => \PDO::PARAM_INT]
        );
    
        return $stmt->fetchAllAssociative();
    }
}
