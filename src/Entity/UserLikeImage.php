<?php

namespace Utils\Entity;


use User\Entity\User;
use Doctrine\ORM\Mapping as ORM;


/**
 * @ORM\Entity(repositoryClass="Utils\Repository\UserImgRepository")
 * @ORM\Table(name="user_images")
 */
class UserLikeImage
{

    /** 
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="User\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     * @var User
     */
    private $user;

    /**
     * @ORM\ManyToOne(targetEntity="User\Entity\GenerativeAssets")
     * @ORM\JoinColumn(name="image_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     * @var GenerativeAssets
     */
    private $generativeAssets;

    /**
     * @ORM\Column(type="datetime", name="liked_at")
     * @var \DateTime
     */
    private $likedAt;


    /**
     * @return Integer
     */
    public function getId(): ?int
    {
        return $this->id;
    }

     /**
     * @return User
     */
    public function getUser(): ?User
    {
        return $this->user;
    }

    /**
     * @param User $user
     * @return UserLikeImage
     */
    public function setUser(User $user): UserLikeImage
    {
        $this->user = $user;
        return $this;
    }

    /**
     * @return GenerativeAssets
     */
    public function getImg(): ?GenerativeAssets
    {
        return $this->generativeAssets;
    }

    /**
     * @param GenerativeAssets $generativeImg
     * @return UserLikeImage
     */
    public function setImg(GenerativeAssets $img): UserLikeImage
    {
        $this->generativeAssets = $img;
        return $this;
    }
    
    public function jsonSerialize()
    {
        return [
            'id' => $this->getId(),
            'user' => $this->getUser(),
            'generativeImg' => $this->getImg()
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
