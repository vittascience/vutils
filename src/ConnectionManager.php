<?php

namespace Utils;

require_once(__DIR__ . "/../vendor/autoload.php");

use Database\DataBaseManager;
use DAO\UserDAO;

class ConnectionManager
{
    private static $sharedInstance;
    const CONNECTION_TIMEOUT = 86400;
    private $errorResponse = [];

    public static function getSharedInstance()
    {
        if (!isset(self::$sharedInstance)) {
            self::$sharedInstance = new ConnectionManager();
        }
        return self::$sharedInstance;
    }

    private function __construct()
    {
    }

    private function checkToken($id, $token)
    {
        $token = DatabaseManager::getSharedInstance()
            ->get(
                "SELECT * FROM connection_tokens WHERE user_ref = ? AND token = ?",
                [$id, $token]
            );
        if (empty($token))
            return false;
        if ($token["is_expired"] == 1)
            return false;
        $now = time();
        $lastTimeActive = strtotime($token["last_time_active"]);
        if (($now - $lastTimeActive) > self::CONNECTION_TIMEOUT) {
            $this->deleteToken($token["user_ref"], $token["token"]);
            return false;
        }
        $res = DatabaseManager::getSharedInstance()
            ->exec(
                "UPDATE connection_tokens SET last_time_active = CURRENT_TIMESTAMP WHERE user_ref = ? AND token = ?",
                [$token["user_ref"], $token["token"]]
            );
        return $res;
    }

    public function checkLogin($identifier, $password)
    {
        // initialise error array with default value
        $this->errorResponse = ["success" => false];

        $regular = DatabaseManager::getSharedInstance()
            ->get("SELECT * FROM user_regulars WHERE email = ?", [$identifier]);

        if ($regular) {
            
            if ($regular['is_active'] == 0) {
                $this->errorResponse['error'] = "user_not_active";
                $this->checkForFailedLoginAttempts();
                return $this->errorResponse;
            }

            $user = DatabaseManager::getSharedInstance()->get("SELECT * FROM users WHERE id = ?  ", [$regular["id"]]);
    
            if (empty($user)) {
                $this->errorResponse['error'] = "user_not_found";
                $this->checkForFailedLoginAttempts();
                return $this->errorResponse;
            }

            if (password_verify($password, $user["password"])) {
                $token = $this->createToken($user["id"]);
                if ($token !== false)
                    return [$user["id"], $token];
            }

            $this->errorResponse['error'] = "wrong_credentials" ;
            $this->checkForFailedLoginAttempts();
            
            return $this->errorResponse;
            // return ["success" => false, "error" => "wrong_credentials"];
        } else {
            $this->errorResponse['error'] =  "user_not_found";
            $this->checkForFailedLoginAttempts();

            return $this->errorResponse;        
        }
    }

    private function createToken($id)
    {
        try {
            $token = bin2hex(random_bytes(32));
        } catch (\Exception $e) {
            return false;
        }
        $res = DatabaseManager::getSharedInstance()
            ->exec("INSERT INTO connection_tokens (token,user_ref) VALUES (?, ?)", [$token, $id]);
        if ($res)
            return $token;
        return false;
    }

    public function deleteToken($id, $token)
    {
        unset($_SESSION["id"]);
        return DatabaseManager::getSharedInstance()
            ->exec(
                "UPDATE connection_tokens SET is_expired = 1 WHERE user_ref = ? AND token = ?",
                [$id, $token]
            );
    }

    public function checkConnected()
    {
        if (isset($_SESSION["id"]) /* && isset($_SESSION["token"]) */) {
            /* if ($this
                ->checkToken($_SESSION["id"], $_SESSION["token"])
            ) */ {
                return UserDAO::getSharedInstance()->getUser($_SESSION["id"]);
            }
        }
        return false;
    }
    
    public function checkForFailedLoginAttempts(){
        $failedLoginAttempts = !empty($_SESSION['failedLoginAttempts']) 
                                ? intval(++$_SESSION['failedLoginAttempts']) 
                                :1;        
        
        $this->errorResponse['failedLoginAttempts'] = $failedLoginAttempts < 5 ? $failedLoginAttempts : 5 ;
        $_SESSION['failedLoginAttempts'] = $failedLoginAttempts;
        if($failedLoginAttempts == 5 ){
            $_SESSION['canNotLoginBefore'] = time()+60;
        }
        if(!empty($_SESSION['canNotLoginBefore'])){
            if(time() >= intval($_SESSION['canNotLoginBefore'])){
                unset($_SESSION['canNotLoginBefore']);
                $_SESSION['failedLoginAttempts'] = 1;
                $this->errorResponse['failedLoginAttempts'] = 1;
            }
            else
            {
                $this->errorResponse['canNotLoginBefore'] = $_SESSION['canNotLoginBefore'];
            }
        }
    }
}
