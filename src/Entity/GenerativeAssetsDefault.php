<?php

namespace Utils\Entity;


use Doctrine\ORM\Mapping as ORM;


/**
 * @ORM\Entity(repositoryClass="Utils\Repository\GenerativeAssetsDefaultRepository")
 * @ORM\Table(name="generative_assets_default")
 */
class GenerativeAssetsDefault implements \JsonSerializable
{

    /** 
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @ORM\Column(name="name", type="string", length=1000, nullable=false)
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
     * @ORM\Column(name="width", type="integer", length=4, nullable=true)
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
     * @ORM\Column(name="model_name", type="string", length=255, nullable=false)
     * @var string
     */
    private $modelName;

    /**
     * @return Integer
     */
    public function getId(): ?int
    {
        return $this->id;
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
     * @return self
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

    public function getModelName(): ?string
    {
        return $this->modelName;
    }

    public function setModelName(string $modelName): self
    {
        $this->modelName = $modelName;
        return $this;
    }

    
    public function jsonSerialize()
    {
        return [
            'id' => $this->id,
            'imageName' => $this->name,
            'createdAt' => $this->createdAt,
            'prompt' => $this->prompt,
            'negativePrompt' => $this->negativePrompt,
            'lang' => $this->lang,
            'width' => $this->width,
            'height' => $this->height,
            'cfgScale' => $this->cfgScale,
            'modelName' => $this->modelName
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
