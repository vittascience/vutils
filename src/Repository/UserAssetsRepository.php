<?php

namespace Utils\Repository;

use Doctrine\ORM\EntityRepository;

class UserAssetsRepository extends EntityRepository
{
    public function getUserAssetsQueryBuilderWithPrefixedKey(String $key, User $user)
    {
        return $this->getEntityManager()->createQueryBuilder()->select('ua')
            ->from(UserAsset::class, 'ua')
            ->where('ua.user = :user')
            ->andWhere('ua.key LIKE :key')
            ->setParameters(['user' => $user, 'key' => $key . '%'])
            ->getQuery()
            ->getResult();
    }
}
