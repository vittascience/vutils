<?php

namespace Utils\Entity;

use User\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use Utils\Entity\GenerativeAssets;

#[ORM\Entity(repositoryClass: "Utils\Repository\UserLikeImageRepository")]
#[ORM\Table(name: "generative_assets_like")]
class UserLikeImage
{
    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    #[ORM\GeneratedValue]
    private $id;

    #[ORM\ManyToOne(targetEntity: "User\Entity\User")]
    #[ORM\JoinColumn(name: "user_id", referencedColumnName: "id", onDelete: "CASCADE", nullable: false)]
    private $user;

    #[ORM\ManyToOne(targetEntity: "Utils\Entity\GenerativeAssets")]
    #[ORM\JoinColumn(name: "image_id", referencedColumnName: "id", onDelete: "CASCADE", nullable: false)]
    private $generativeAssets;

    #[ORM\Column(type: "datetime", name: "liked_at")]
    private $likedAt;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): UserLikeImage
    {
        $this->user = $user;
        return $this;
    }

    public function getImg(): ?GenerativeAssets
    {
        return $this->generativeAssets;
    }

    public function setImg(GenerativeAssets $img): UserLikeImage
    {
        $this->generativeAssets = $img;
        return $this;
    }

    public function getLikedAt(): ?\DateTime
    {
        return $this->likedAt;
    }

    public function setLikedAt(\DateTime $likedAt): UserLikeImage
    {
        $this->likedAt = $likedAt;
        return $this;
    }

    public function jsonSerialize()
    {
        return [
            'id' => $this->getId(),
            'user' => $this->getUser(),
            'generativeImg' => $this->getImg(),
            'likedAt' => $this->getLikedAt()
        ];
    }

    public static function jsonDeserialize($jsonDecoded)
    {
        $classInstance = new self();
        foreach ($jsonDecoded as $attributeName => $attributeValue) {
            $classInstance->{$attributeName} = $attributeValue;
        }
        return $classInstance;
    }
}
