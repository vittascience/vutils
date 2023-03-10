<?php

namespace Utils\Repository;

use User\Entity\User;
use Doctrine\ORM\EntityRepository;

class UserImgRepository extends EntityRepository
{
    public function getUserImg(User $user)
    {
        return $this->getEntityManager()->createQueryBuilder()->select('ui')
            ->from("Utils\Entity\UserImg", 'ui')
            ->where('ui.user = :user')
            ->setParameters(['user' => $user])
            ->getQuery()
            ->getResult();
    }

    public function getPublicAssets()
    {
        return $this->getEntityManager()->createQueryBuilder()->select('ui')
            ->from("Utils\Entity\UserImg", 'ui')
            ->where('ui.isPublic = 1')
            ->getQuery()
            ->getResult();
    }
}
