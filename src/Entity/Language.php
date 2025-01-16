<?php

namespace Utils\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: "Utils\Repository\LanguageRepository")]
#[ORM\Table(name: "lang")]
class Language
{
    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    #[ORM\GeneratedValue]
    private $id;

    #[ORM\Column(name: "name", type: "string", length: 250, nullable: false)]
    private string $name;

    #[ORM\Column(name: "langcode", type: "string", length: 2, nullable: false)]
    private string $langCode;

    #[ORM\Column(name: "path", type: "string", length: 250, nullable: false)]
    private string $path;

    #[ORM\Column(name: "available", type: "integer", length: 1, nullable: false)]
    private int $available;

    public function getId()
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getLangCode(): string
    {
        return $this->langCode;
    }

    public function setLangCode(string $langCode): self
    {
        $this->langCode = $langCode;
        return $this;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function setPath(string $path): self
    {
        $this->path = $path;
        return $this;
    }

    public function getAvailable(): int
    {
        return $this->available;
    }

    public function setAvailable(int $available): self
    {
        $this->available = $available;
        return $this;
    }

    public function jsonSerialize()
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
