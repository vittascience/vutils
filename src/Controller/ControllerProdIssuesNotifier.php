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

   public function notifySlack(){

    $emailReceiver = "notif-prod-controller-aaaaibwqgmrmcydttw65fn4glq@vittascience.slack.com";
    $subject = "TEST PROD ISSUES NOTIF";
    
    $emailTtemplateBody ="fr_defaultMailerTemplate" ;

    $body = "
        <br>
        <p>from : NAS</p>
        <p>UN MESSAGE PAR HASARD</p>
        <br>
    ";

    // send email
    $emailSent = Mailer::sendMail($emailReceiver,  $subject, $body, strip_tags($body), $emailTtemplateBody, $replyToMail=null, $replyToName=null);
    /////////////////////////////////////

    return array(
        "emailSent" => $emailSent
    );
     return array('msg'=> 'oki nas prod notified');
   }
}
