<?php

namespace Utils\Repository;

use Doctrine\ORM\EntityRepository;

class LanguageRepository extends EntityRepository
{
    public function getAvailableLanguages()
    {
        return $this->getEntityManager()->createQueryBuilder()->select('l.name,l.langCode')
            ->from("Utils\Entity\Language", 'l')
            ->andWhere('l.available=1')
            ->getQuery()
            ->getResult();
    }
}
