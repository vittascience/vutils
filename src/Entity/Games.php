<?php

namespace Utils\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="Utils\Repository\GamesRepository")
 * @ORM\Table(name="generative_assets_game")
 */
class Games
{
   /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private int $id;
    /**
     * @return Id
     */
    public function getId(): int
    {
        return $this->id;
    }

    /** 
     * @ORM\Column(type="string")
     * @ORM\GeneratedValue
     */
    private $prompt;

    /**
    * @return Prompt
    */
   public function getPrompt(): ?string
   {
       return $this->prompt;
   }

       /** 
     * @ORM\Column(type="string")
     * @ORM\GeneratedValue
     */
    private $image;

    /**
    * @return Image
    */
   public function getImage(): ?string
   {
       return $this->image;
   }
   
    /**
     * @ORM\Column(type="datetime", name="start_game")
     * @var \DateTime
     */
    private $start_game;


    /**
     * @return \DateTime
     */
    public function get_start_game(): ?\DateTime
    {
        return $this->start_game;
    }
        /**
     * @ORM\Column(type="datetime", name="end_game")
     * @var \DateTime
     */
    private $end_game;


    /**
     * @return \DateTime
     */
    public function get_end_game(): ?\DateTime
    {
        return $this->end_game;
    }


    
    public function jsonSerialize()
    {
        return [
            'id'=> $this->getId(),
            'prompt' => $this->getPrompt(),
            'image' => $this->getImage(),
            'start_game' => $this->get_start_game(),
            'end_game' => $this->get_end_game()
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
