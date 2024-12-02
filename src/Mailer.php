<?php
namespace Utils;
require($_SERVER["DOCUMENT_ROOT"]."/vendor/autoload.php");

use Dotenv\Dotenv;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Mailer
{
    /**
     * Send an email
     * @param string $recipient
     * @param string $subject
     * @param string $body
     * @param string $altBody
     * @param string $templateBody
     * @param string $replyToMail
     * @param string $replyToName
     * @param string|array $additionalAddress
     * @param string|array $additionalCC
     * @return bool
     */
    public static function sendMail($recipient, $subject, $body, $altBody, $templateBody = 'fr_default', $replyToMail = null, $replyToName = null, $additionalAddress=null, $additionalCC=null)
    {
       
        $mail = new PHPMailer(true);
        try {
            $dotenv = Dotenv::createImmutable(__DIR__ . "/../../../../");
            $dotenv->load();
           
            // set values
            $replyToMail = isset($replyToMail) ? $replyToMail : $_ENV['VS_REPLY_TO_MAIL'];
            $replyToName = isset($replyToName) ? $replyToName : $_ENV['VS_REPLY_TO_NAME'];


            $mail->isSMTP(); 
            $mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );
            $mail->Host = $_ENV['VS_MAIL_SERVER'];
            $mail->SMTPAuth = true;
            $mail->Username = $_ENV['VS_MAIL_ADDRESS'];
            $mail->Password = $_ENV['VS_MAIL_PASSWORD'];
            $mail->SMTPSecure = $_ENV['VS_MAIL_TYPE'];
            $mail->Port = $_ENV['VS_MAIL_PORT'];
            
            $mail->setFrom($_ENV['VS_MAIL_ADDRESS'], $_ENV['VS_SET_FROM']);
            $mail->addAddress($recipient); 


            /**
             * Add additional(s) Copy
             */
            if ($additionalCC) {
                if (is_array($additionalCC)) {
                    foreach ($additionalCC as $cc) {
                        $mail->AddCC($cc);
                    }
                } else {
                    $mail->AddCC($additionalCC);
                }
            }
            
            /**
             * Add additional(s) address
             */
            if($additionalAddress != null) {
                if (is_array($additionalAddress)) {
                    foreach ($additionalAddress as $address) {
                        $mail->addAddress($address); 
                    }
                } else {
                    $mail->addAddress($additionalAddress); 
                }
            }  
                
            // Name is optional
            $mail->addReplyTo($replyToMail, $replyToName);
            $templateBody = self::loadTemplateBody($templateBody,$body);
           
            $mail->isHTML(true);
            $mail->CharSet        = "UTF-8";
            $mail->Subject = $subject;
            $mail->Body    = $templateBody;
            $mail->AltBody = $altBody;
            if ($mail->send()) {
                return true;
            }
            return false;
        } catch (Exception $e) {
            
		echo $e->getMessage();
            return false;
        }
    }
    public static function loadTemplateBody($templateBody,$body)
    {
        // get the emailTemplates dir at the root of the project
        $emailTemplateDir = __DIR__.'/../../../../emailTemplates';

        // the emailmTemplates dir does not exists at the root level
        if(!is_dir($emailTemplateDir)) {
            // get the content from self::loadDefaultTemplateBody and return it
            $content = self::loadDefaultTemplateBody($templateBody,$body);
            return $content;
        }
        
         // emailTempltes found, get the emailTemplates subfolders
         $langFolders = array_diff(scandir("$emailTemplateDir"), array('..', '.'));

         // parse $templateBody to find the language
         $fileParts = explode('_',$templateBody);
         $langFolder = ucfirst($fileParts[0]);
       
         // no $langFolder match with one of the emailTemplates subfolders
         if(!in_array($langFolder, $langFolders)) {
             // get the content from self::loadDefaultTemplateBody and return it
             $content = self::loadDefaultTemplateBody($templateBody,$body);
             return $content;
         }
        
         
         // langFolder found, but the requested file file does not exists inside
         if(!file_exists("$emailTemplateDir/$langFolder/$templateBody.php")) {
              // get the content from self::loadDefaultTemplateBody and return it
             $content = self::loadDefaultTemplateBody($templateBody,$body);
             return $content;
         }

         // langFolder found and the requested file exists
         if(file_exists("$emailTemplateDir/$langFolder/$templateBody.php")){
             // start the buffer, get data, clean the buffer and return data
            ob_start();
            $body ;
            include_once "$emailTemplateDir/$langFolder/$templateBody.php";
            $content = ob_get_contents();
            ob_end_clean();
           
            return $content;
        }
    }

    public static function loadDefaultTemplateBody($templateBody,$body){

       
        $emailTemplateDir = __DIR__.'/emailTemplates';
        if(!is_dir($emailTemplateDir))
        {
            // no directory found, throw an error
            throw  new \Exception("Directory $emailTemplateDir does not exists in vendor/vittascience/vutils/");
            exit;
        }

        // parse $templateBody to find the language
        $fileParts = explode('_',$templateBody);
        $langFolder = ucfirst($fileParts[0]);

        // langFolder not found
        if(!is_dir("$emailTemplateDir/$langFolder")){
            throw  new \Exception("Directory $langFolder does not exists in vendor/vittascience/vutils/EmailTemplates");
            exit;
        }

        // $langFolder exists but the requested file does not exists
        if(!file_exists("$emailTemplateDir/$langFolder/$templateBody.php")){
            // no lang directory found, throw an error
            throw  new \Exception("File $templateBody does not exists in vendor/vittascience/vutils/EmailTemplates/$langFolder");
            exit;
        }

        // langFolder found and the requested file exists
        if(file_exists("$emailTemplateDir/$langFolder/$templateBody.php")){
            // start the buffer, get data, clean the buffer and return data
            ob_start();
            $body ;
            include_once "$emailTemplateDir/$langFolder/$templateBody.php";
            $content = ob_get_contents();
            ob_end_clean();
           
            return $content;
        }
       
    }
}
