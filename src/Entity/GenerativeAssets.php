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
     * @ORM\Column(name="negative_prompt", type="string", length=255, nullable=false)
     * @var string
     */
    private $negativePrompt;

    /**
     * @ORM\Column(name="lang", type="string", length=2, nullable=true)
     * @var string
     */
    private $lang;

    /**
     * @ORM\Column(name="witdh", type="integer", length=4, nullable=true)
     * @var string
     */
    private $width;

    /**
     * @ORM\Column(name="height", type="integer", length=4, nullable=true)
     * @var integer
     */
    private $height;

    /**
     * @ORM\Column(name="cfg_scale", type="integer", nullable=true)
     * @var float
     */
    private $cfgScale;

    /**
     * @ORM\Column(name="likes", type="integer", nullable=true)
     * @var integer
     */
    private $likes;


    /**
     * @ORM\Column(name="is_public", type="boolean", nullable=true)
     * @var boolean
     */
    private $isPublic;

    /**
     * @ORM\Column(name="model_name", type="string", length=255, nullable=false)
     * @var string
     */
    private $modelName;

    /**
     * @ORM\Column(name="admin_review", type="boolean", nullable=true)
     * @var boolean
     */
    private $adminReview;

    /**
     * @ORM\Column(name="creation_steps", type="string", length=1000, nullable=true)
     * @var string
     */
    private $creationSteps;

    /**
     * @ORM\Column(name="is_competition", type="boolean", nullable=true)
     * @var boolean
     */
    private $isCompetition;

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
     * @return User
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
     * @return string
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

    public function setIpAddress(?string $ipAddress): self
    {
        $this->ipAddress = $ipAddress;
        return $this;
    }

    public function getNegativePrompt(): ?string
    {
        return $this->negativePrompt;
    }

    public function setNegativePrompt(string $negativePrompt): self
    {
        $this->negativePrompt = $negativePrompt;
        return $this;
    }

    public function getLang(): ?string
    {
        return $this->lang;
    }

    public function setLang(string $lang): self
    {
        $this->lang = $lang;
        return $this;
    }

    public function getWidth(): ?string
    {
        return $this->width;
    }

    public function setWidth(string $width): self
    {
        $this->width = $width;
        return $this;
    }

    public function getHeight(): ?int
    {
        return $this->height;
    }

    public function setHeight(int $height): self
    {
        $this->height = $height;
        return $this;
    }

    public function getCfgScale(): ?float
    {
        return $this->cfgScale;
    }

    public function setCfgScale(float $cfgScale): self
    {
        $this->cfgScale = $cfgScale;
        return $this;
    }

    public function getLikes(): ?int
    {
        return $this->likes;
    }

    public function setLikes(int $likes): self
    {
        $this->likes = $likes;
        return $this;
    }

    public function getIsPublic(): ?bool
    {
        return $this->isPublic;
    }

    public function setIsPublic(bool $isPublic): self
    {
        $this->isPublic = $isPublic;
        return $this;
    }

    public function getModelName(): ?string
    {
        return $this->modelName;
    }

    public function setModelName(string $modelName): self
    {
        $this->modelName = $modelName;
        return $this;
    }

    public function getAdminReview(): ?bool
    {
        return $this->adminReview;
    }

    public function setAdminReview(bool $adminReview): self
    {
        $this->adminReview = $adminReview;
        return $this;
    }

    public function getCreationSteps(): ?string
    {
        return $this->creationSteps;
    }

    public function setCreationSteps(string $creationSteps): self
    {
        $this->creationSteps = $creationSteps;
        return $this;
    }

    public function getIsCompetition(): ?bool
    {
        return $this->isCompetition;
    }

    public function setIsCompetition(bool $isCompetition): self
    {
        $this->isCompetition = $isCompetition;
        return $this;
    }
    
    public function jsonSerialize()
    {
        return [
            'id' => $this->id,
            'user' => $this->user,
            'imageName' => $this->name,
            'createdAt' => $this->createdAt,
            'prompt' => $this->prompt,
            'ipAddress' => $this->ipAddress,
            'negativePrompt' => $this->negativePrompt,
            'lang' => $this->lang,
            'width' => $this->width,
            'height' => $this->height,
            'cfgScale' => $this->cfgScale,
            'likes' => $this->likes,
            'isPublic' => $this->isPublic,
            'modelName' => $this->modelName,
            'adminReview' => $this->adminReview,
            'creationSteps' => $this->creationSteps,
            'isCompetition' => $this->isCompetition,
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
