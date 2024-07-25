<?php

namespace Utils\Entity;


use User\Entity\User;
use Doctrine\ORM\Mapping as ORM;


/**
 * @ORM\Entity(repositoryClass="Utils\Repository\GenerativeAssetsRepository")
 * @ORM\Table(name="generative_assets")
 */
class GenerativeAssets implements \JsonSerializable
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
     * @ORM\Column(name="prompt", type="string", length=255, nullable=false)
     * @var string
     */
    private $prompt;


    /**
     * @ORM\Column(name="ip_address", type="string", length=255, nullable=true)
     * @var string
     */
    private $ipAddress;


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

    public function getPrompt(): ?string
    {
        return $this->prompt;
    }

    public function setPrompt(string $prompt): self
    {
        $this->prompt = $prompt;
        return $this;
    }

    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    public function setIpAddress(string $ipAddress): self
    {
        $this->ipAddress = $ipAddress;
        return $this;
    }


    
    public function jsonSerialize()
    {
        return [
            'id' => $this->id,
            'user' => $this->user,
            'name' => $this->name,
            'createdAt' => $this->createdAt,
            'prompt' => $this->prompt,
            'ipAddress' => $this->ipAddress,
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
