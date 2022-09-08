<?php

namespace Utils\Repository;

use User\Entity\User;
use Doctrine\ORM\EntityRepository;

class UserAssetsRepository extends EntityRepository
{
    public function getUserAssetsQueryBuilderWithPrefixedKey(String $key, User $user)
    {
        return $this->getEntityManager()->createQueryBuilder()->select('ua')
            ->from("Utils\Entity\UserAssets", 'ua')
            ->where('ua.user = :user')
            ->andWhere('ua.link LIKE :key')
            ->setParameters(['user' => $user, 'key' => $key . '%'])
            ->getQuery()
            ->getResult();
    }

    public function getPublicAssetsQueryBuilderWithPrefixedKey(String $key)
    {
        return $this->getEntityManager()->createQueryBuilder()->select('ua')
            ->from("Utils\Entity\UserAssets", 'ua')
            ->where('ua.user = :user')
            ->andWhere('ua.link LIKE :key')
            ->andWhere('ua.isPublic = 1')
            ->setParameters(['key' => $key . '%'])
            ->getQuery()
            ->getResult();
    }
}
