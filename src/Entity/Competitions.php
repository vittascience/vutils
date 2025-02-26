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
    * @return Name
    */
   public function getName(): ?string
   {
       return $this->name;
   }

    /**
     * @ORM\Column(type="datetime", name="start_competition")
     * @var \DateTime
     */
    private $start_competition;


    /**
     * @return \DateTime
     */
    public function get_start_competition(): ?\DateTime
    {
        return $this->start_competition;
    }
    
    public function jsonSerialize()
    {
        return [
            'name' => $this->getName(),
            'start_competition' => $this->get_start_competition(),
             'end_competition' => $this->get_end_competition()
        ];
    }

      /**
     * @ORM\Column(type="datetime", name="end_competition")
     * @var \DateTime
     */
    private $end_competition;


    /**
     * @return \DateTime
     */
    public function get_end_competition(): ?\DateTime
    {
        return $this->end_competition;
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
