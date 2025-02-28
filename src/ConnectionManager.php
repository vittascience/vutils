<?php

namespace Utils;

require_once(__DIR__ . "/../vendor/autoload.php");

use DateTime;
use DAO\UserDAO;
use Dotenv\Dotenv;
use DAO\UserLoginAttemptDAO;
use Database\DataBaseManager;
use Otp\Otp;
use Otp\GoogleAuthenticator;
use ParagonIE\ConstantTime\Encoding;

class ConnectionManager
{
    private static $sharedInstance;
    const CONNECTION_TIMEOUT = 86400;
    private $errorResponse = [];
    protected $envVariables;
    protected $failedLoginMaxTries;
    protected $waitingTimeBeforeNewLogin;

    public static function getSharedInstance()
    {
        if (!isset(self::$sharedInstance)) {
            self::$sharedInstance = new ConnectionManager();
        }
        return self::$sharedInstance;
    }

    private function __construct()
    {
        $dotenv = Dotenv::createImmutable(__DIR__ . "/../../../../");
        $dotenv->load();
        $this->envVariables = $_ENV;
        $this->failedLoginMaxTries = !empty($_ENV['VS_FAILED_LOGIN_MAX_TRIES'])
            ? intval($_ENV['VS_FAILED_LOGIN_MAX_TRIES'])
            : 5;
        $this->waitingTimeBeforeNewLogin = !empty($_ENV['VS_WAITING_TIME_BEFORE_NEW_LOGIN'])
            ? intval($_ENV['VS_WAITING_TIME_BEFORE_NEW_LOGIN'])
            : 120;
    }

    private function checkToken($userId, $token)
    {
        $connectionToken = DataBaseManager::getSharedInstance()
            ->get("SELECT * FROM connection_tokens WHERE user_ref = ? AND token = ? AND is_expired = 0", [$userId, $token]);

        if ($connectionToken) {
            return true;
        }
        return false;
    }

    public function getToken($userId)
    {
        return DataBaseManager::getSharedInstance()
            ->get("SELECT * FROM connection_tokens WHERE user_ref = ?", [$userId]);
    }

    public function checkLogin($identifier, $password, $totp_code = false)
    {
        // initialise error array with default value
        $this->errorResponse = ["success" => false];

        $regular = DataBaseManager::getSharedInstance()
            ->get("SELECT * FROM user_regulars WHERE email = ?", [$identifier]);

        if ($regular) {

            // get the failed attempts data if any
            $failedAttempts = UserLoginAttemptDAO::getSharedInstance()
                ->getLoginAttemptsDataByEmail($regular['email']);

            // the regular user reached the max amount of tries
            if ($failedAttempts && $failedAttempts['count'] >= $this->failedLoginMaxTries) {

                // the waiting time before a new try still runs
                if (time() < $failedAttempts['can_not_login_before']) {

                    // create and send the response back
                    $this->errorResponse['error'] = "wrong_credentials";
                    $this->errorResponse['failedLoginAttempts'] = $failedAttempts['count'];
                    $this->errorResponse['canNotLoginBefore'] = $failedAttempts['can_not_login_before'];
                    return $this->errorResponse;
                }

                // the waiting time is over, remove login attempts form users_login_attempts table
                UserLoginAttemptDAO::getSharedInstance()->resetLoginAttemptsByEmail($regular['email']);
            }



            // get the user from users table
            $user = DataBaseManager::getSharedInstance()
                ->get("SELECT * FROM users WHERE id = ?  ", [$regular["id"]]);

            // no user found, return an error
            if (empty($user)) {
                $this->errorResponse['error'] = "user_not_found";
                return $this->errorResponse;
            }

            if (password_verify($password, $user["password"])) {

                // the regular user account is not activated yet
                if ($regular['is_active'] == 0) {

                    // save a failed login attempt in db, create and send back the response
                    $this->saveFailedLoginAttempt($regular['email']);
                    $this->errorResponse['error'] = "user_not_active";
                    return $this->errorResponse;
                }

                //check if the user has 2FA enabled
                if ($user["totp_secret"] != null) {
                    if (empty($totp_code)) {
                        $this->errorResponse['error'] = "totp_code_required";
                        return $this->errorResponse;
                    }
                    $otp = new Otp();
                    if (!$otp->checkTotp(Encoding::base32DecodeUpper($user["totp_secret"]), $totp_code)) {
                        $this->errorResponse['error'] = "wrong_totp_code";
                        return $this->errorResponse;
                    }
                }
                // password verified, create the token
                $token = $this->createToken($user["id"]);
                if ($token !== false) {
                    // remove failed login attemps if any to clean to users_login_attempts table and send data back
                    UserLoginAttemptDAO::getSharedInstance()->resetLoginAttemptsByEmail($regular['email']);
                    return [$user["id"], $token];
                }
            }

            // password has not been verified, return an error
            $this->saveFailedLoginAttempt($regular['email']);
            $this->errorResponse['error'] = "wrong_credentials";
            return $this->errorResponse;
        } else {
            // no regular user found, return an error
            $this->errorResponse['error'] =  "user_not_found";
            return $this->errorResponse;
        }
    }

    private function createToken($id)
    {
        try {
            $successDeletion = DataBaseManager::getSharedInstance()->exec("DELETE FROM connection_tokens WHERE user_ref = ?", [$id]);
            if (!$successDeletion) {
                return false;
            }

            $token = bin2hex(random_bytes(32));
            $res = DataBaseManager::getSharedInstance()->exec("INSERT INTO connection_tokens (token,user_ref) VALUES (?, ?)", [$token, $id]);
            if ($res) {
                return $token;
            }
        } catch (\Exception $e) {
            return false;
        }

        return false;
    }

    public function deleteToken($id, $token)
    {
        unset($_SESSION["id"]);
        return DataBaseManager::getSharedInstance()
            ->exec(
                "UPDATE connection_tokens SET is_expired = 1 WHERE user_ref = ? AND token = ?",
                [$id, $token]
            );
    }

    public function checkConnected()
    {
        if (isset($_SESSION["id"])) {
            return UserDAO::getSharedInstance()->getUser($_SESSION["id"]);
        }
        return false;
    }

    public function saveFailedLoginAttempt($email)
    {
        $registrationTime = time();
        $canNotLoginBefore = $registrationTime + $this->waitingTimeBeforeNewLogin;
        $userLoginAttempt = new \stdClass();
        $userLoginAttempt->email = $email;
        $userLoginAttempt->registrationTime = $registrationTime;
        $userLoginAttempt->canNotLoginBefore = $canNotLoginBefore;

        UserLoginAttemptDAO::getSharedInstance()->insert($userLoginAttempt);
    }
}
