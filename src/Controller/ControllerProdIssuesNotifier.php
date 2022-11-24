<?php

namespace Utils\Controller;

use Utils\Mailer;
use Dotenv\Dotenv;

class ControllerProdIssuesNotifier
{
    protected $actions = [];
    protected $entityManager;
    protected $user;

    public function __construct($entityManager, $user)
    {
        $dotenv = Dotenv::createImmutable(__DIR__."/../");
        $dotenv->safeLoad();
        $this->envVariables = $_ENV;
        $this->entityManager = $entityManager;
        $this->user = $user;
    }

    public function notifySlack()
    {
        $decodedData = json_decode($_POST['errorReport']);
        $routingUrlQueryString = explode('?', $decodedData->context->routingUrl)[1];
        $queryParts = explode('&', $routingUrlQueryString);
        $controller = str_replace('controller=', '', $queryParts[0]);
        $action = str_replace('action=', '', $queryParts[1]);

        $emailReceiver = "notif-prod-controller-aaaaibwqgmrmcydttw65fn4glq@vittascience.slack.com";
        $subject = "Erreur sur controller $controller / action $action";

        $emailTtemplateBody = "fr_devMailerTemplate";

        $body = "
            <p>
                Un utilisateur a rencontré une erreur de type <code> $decodedData->errorMessage </code> sur une requête ajax concernant le controller <code>$controller</code> et l'action <code>$action</code>.
            </p>
        ";

        if ($decodedData->responseText === '') {
            $body .= "<p>La réponse du serveur est une string vide. Piste éventuelle: doctrine a pu rencontrer une erreur. Voir les fichiers de logs dans le container docker_web_1";
        }

        $originalString = json_encode($decodedData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $stringTest = str_replace('\\\\n', '\\n', $originalString);        // $stringWithoutDoubleBackslashes = str_replace('\\\\', 'vittapattern', $originalString);
        // $stringWithoutSingleBackslashe = str_replace('\\', '', $stringWithoutDoubleBackslashes);
        // $finalStringToDisplay = str_replace('vittapattern', '\\', $stringWithoutSingleBackslashe);

        $body .= "
            <hr>
            <p>Détail de l'erreur de la requête:</p>
            <pre style='background: black;color: white;overflow: scroll;padding: 0.5em 1em;border-radius: 0.75em;font-size: 1.1em;user-select:all;'>$stringTest</pre>";

        // send email
        Mailer::sendMail($emailReceiver,  $subject, $body, strip_tags($body), $emailTtemplateBody);
        return;
    }
}
