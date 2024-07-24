<?php

namespace Utils\Entity;


use User\Entity\User;
use Doctrine\ORM\Mapping as ORM;


/**
 * @ORM\Entity(repositoryClass="Utils\Repository\GenerativeAssetsRepository")
 * @ORM\Table(name="generative_assets")
 */
class UserAssets
{

    /** 
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="User\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE", nullable=true)
     * @var User
     */
    private $user;

    /**
     * @ORM\Column(name="name", type="string", length=255, nullable=false)
     * @var string
     */
    private $name;

    /**
     * @ORM\Column(name="created_at", type="datetime", nullable=true)
     * @var datetime
     */
    private $createdAt;


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
    public function setUser(?User $user): self
    {
        $this->user = $user;
        return $this;
    }

    /**
     * @return string
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return UsersLinkApplications
     */
    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }


    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTime $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }


    
    public function jsonSerialize()
    {
        return [
            'id' => $this->id,
            'user' => $this->user,
            'name' => $this->name,
            'createdAt' => $this->createdAt,
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
