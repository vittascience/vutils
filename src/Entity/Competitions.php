<?php

namespace Utils\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="Utils\Repository\CompetitionsRepository")
 * @ORM\Table(name="generative_assets_competitions")
 */
class Competitions
{
   /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private int $id;

    /** 
     * @ORM\Column(type="string")
     * @ORM\GeneratedValue
     */
    private $name;

    /**
     * @return Id
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @ORM\Column(type="datetime", name="monday")
     * @var \DateTime
     */
    private $monday;

     /**
     * @return Name
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @return \DateTime
     */
    public function getMonday(): ?\DateTime
    {
        return $this->monday;
    }
    
    public function jsonSerialize()
    {
        return [
            'name' => $this->getName(),
            'monday' => $this->getMonday()
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
