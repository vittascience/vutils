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
            ->setParameter('name', $prefixWithPercent)
            ->orderBy('g.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        return $qb->getQuery()->getResult();
    }
}
