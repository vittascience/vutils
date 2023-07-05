<?php

namespace Utils\Controller;

use Dotenv\Dotenv;
use Utils\Entity\Language;

class ControllerLanguage{
    protected $actions = [];
    protected $entityManager;
    protected $user;
    protected $envVariables;

    public function __construct($entityManager, $user)
    {
        $dotenv = Dotenv::createImmutable(__DIR__."/../");
        $dotenv->safeLoad();
        $this->envVariables = $_ENV;
        $this->entityManager = $entityManager;
        $this->user = $user;
    }

    public function getAvailableLanguages()
    {
         // accept only POST request
         if ($_SERVER['REQUEST_METHOD'] !== 'POST') return ["error" => "Method not Allowed"];

         $availableLanguages = $this->entityManager->getRepository(Language::class)->getAvailableLanguages();
        return array('results'=> $availableLanguages);
    }
}