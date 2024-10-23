<?php

namespace Utils\Repository;

use User\Entity\User;
use Doctrine\ORM\EntityRepository;
use Utils\Entity\GenerativeAssets;

class GenerativeAssetsRepository extends EntityRepository
{
    public function getAssetsIfDuplicateExists(String $prompt, ?String $negativePrompt, $width, $height, $scale, $modelName)
    {
        $isDuplicate = $this->getEntityManager()->getRepository(GenerativeAssets::class)
                ->createQueryBuilder('g.promp, g.negativePrompt, g.width, g.height, g.cfgScale, g.modelName, g.creationSteps')
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
                ->getOneOrNullResult();
        
        return $isDuplicate;
    }
}
