<?php

namespace Utils\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="Utils\Repository\LanguageRepository")
 * @ORM\Table(name="lang")
 */
class Language{

    /** 
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @ORM\Column(name="name", type="string", length=250, nullable=false)
     * @var string
     */
    private $name;

    /**
     * @ORM\Column(name="langcode", type="string", length=2, nullable=false)
     */
    private $langCode;

    /**
     * @ORM\Column(name="path", type="string", length=250, nullable=false)
     */
    private $path;

    /**
     * @ORM\Column(name="available", type="integer", length=1, nullable=false)
     */
    private $available;


    /**
     * Get the value of id
     */ 
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get the value of name
     *
     * @return  string
     */ 
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set the value of name
     *
     * @return  self
     */ 
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get the value of langCode
     */ 
    public function getLangCode()
    {
        return $this->langCode;
    }

    /**
     * Set the value of langCode
     *
     * @return  self
     */ 
    public function setLangCode($langCode)
    {
        $this->langCode = $langCode;

        return $this;
    }

    /**
     * Get the value of path
     */ 
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Set the value of path
     *
     * @return  self
     */ 
    public function setPath($path)
    {
        $this->path = $path;

        return $this;
    }

    /**
     * Get the value of available
     */ 
    public function getAvailable()
    {
        return $this->available;
    }

    /**
     * Set the value of available
     *
     * @return  self
     */ 
    public function setAvailable($available)
    {
        $this->available = $available;

        return $this;
    }

    public function jsonSerialize(): mixed
    {
        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'langCode' => $this->getLangCode(),
            'path' => $this->getPath(),
            'available' => $this->getAvailable()
        ];
    }
}