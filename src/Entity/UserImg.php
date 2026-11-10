<?php

namespace Utils\Entity;


use User\Entity\User;
use Doctrine\ORM\Mapping as ORM;


#[ORM\Table(name: 'user_images')]
#[ORM\Entity(repositoryClass: \Utils\Repository\UserImgRepository::class)]
class UserImg
{

    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    private $id;

    /**
     * @var User
     */
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', onDelete: 'CASCADE', nullable: false)]
    #[ORM\ManyToOne(targetEntity: \User\Entity\User::class)]
    private $user;

    /**
     * @var string
     */
    #[ORM\Column(name: 'img', type: 'string', length: 255, nullable: false)]
    private $img;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'is_public', type: 'boolean', nullable: true)]
    private $isPublic;


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
     * @return UsersLinkApplications
     */
    public function setUser(User $user): self
    {
        $this->user = $user;
        return $this;
    }

    /**
     * @return string
     */
    public function getImg(): ?string
    {
        return $this->img;
    }

    /**
     * @param string $link
     * @return UserImg
     */
    public function setImg(string $img): self
    {
        $this->img = $img;
        return $this;
    }

    /**
     * @return bool
     */
    public function getIsPublic(): ?bool
    {
        return $this->isPublic;
    }

    /**
     * @param bool $isPublic
     * @return UserImg
     */
    public function setIsPublic(bool $isPublic): self
    {
        $this->isPublic = $isPublic;
        return $this;
    }


    
    public function jsonSerialize()
    {
        return [
            'id' => $this->getId(),
            'user' => $this->getUser(),
            'img' => $this->getImg(),
            'isPublic' => $this->getIsPublic()
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
