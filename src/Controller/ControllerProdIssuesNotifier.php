<?php

namespace Utils\Controller;

use Utils\Mailer;
use Dotenv\Dotenv;
use User\Entity\User;

class ControllerProdIssuesNotifier
{
    protected $actions = [];
    protected $entityManager;
    protected $user;

    public function __construct($entityManager, $user)
    {
        $dir  = is_file('/run/secrets/app_env') ? '/run/secrets' : __DIR__ . '/../';
        $file = is_file('/run/secrets/app_env') ? 'app_env'      : '.env';
        Dotenv::createImmutable($dir, $file)->safeLoad();
        $this->envVariables = $_ENV;
        $this->entityManager = $entityManager;
        $this->user = $user;
    }

    public function notifySlack()
    {
        // bind and parse incoming data
        $decodedData = json_decode($_POST['errorReport']);
        $routingUrlQueryString = explode('?', $decodedData->context->routingUrl)[1];
        $queryParts = explode('&', $routingUrlQueryString);
        $controller = str_replace('controller=', '', $queryParts[0]);
        $action = str_replace('action=', '', $queryParts[1]);

        // set variables for email to be send
        $emailReceiver = "notif-prod-controller-aaaaibwqgmrmcydttw65fn4glq@vittascience.slack.com";
        $subject = "Erreur sur controller $controller / action $action";
        $emailTtemplateBody = "fr_devMailerTemplate";
        $body = "
             <p>
                 Un utilisateur a rencontré une erreur de type <code> {$decodedData->errorMessage} </code> sur une requête ajax concernant le controller <code>$controller</code> et l'action <code>$action</code>.
             </p>
         ";

        if ($decodedData->responseText === '') {
            $body .= "<p>La réponse du serveur est une string vide. Piste éventuelle: doctrine a pu rencontrer une erreur. Voir les fichiers de logs dans le container docker_web_1";
        }

        // filter string for better formatting
        $originalString = json_encode($decodedData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $stringTest = str_replace('\\\\n', '\\n', $originalString);
        
        $userId = intval($_SESSION['id']) ?? null;
        $userStringified = "user not connected";
        if ($userId) {
            $user = $this->entityManager->getRepository(User::class)->find($userId);
            $userStringified = json_encode($user->jsonSerialize(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            str_replace('\\\\n', '\\n', $userStringified);
        }
        $body .= "
             <hr>
             <p>Détail de l'erreur de la requête:</p>
             user : 
             <pre style='background: black;color: white;overflow: scroll;padding: 0.5em 1em;border-radius: 0.75em;font-size: 1.1em;user-select:all;'>$userStringified</pre>
             <pre style='background: black;color: white;overflow: scroll;padding: 0.5em 1em;border-radius: 0.75em;font-size: 1.1em;user-select:all;'>$stringTest</pre>";

        // send email
        Mailer::sendMail($emailReceiver,  $subject, $body, strip_tags($body), $emailTtemplateBody, null, null, "logs@vittascience.com");
        return;
    }

    public function notifySlackAboutGar()
    {
        // // set variables for email to be send
        $emailReceiver = "notif-prod-controller-aaaaibwqgmrmcydttw65fn4glq@vittascience.slack.com";
        $subject = $_POST['issue_subject'];
        $emailTtemplateBody = "fr_devMailerTemplate";
      
        $body = "
            <p>{$_POST['issue_text']}</p>
        ";
        // send email
        Mailer::sendMail($emailReceiver,  $subject, $body, strip_tags($body), $emailTtemplateBody,null,null,"logs@vittascience.com");
        return;
    }
}
