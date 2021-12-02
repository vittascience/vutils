<?php

namespace Utils;

require_once(__DIR__ . "/../vendor/autoload.php");

use Database\DataBaseManager;
use DAO\UserDAO;

class ConnectionManager
{
    private static $sharedInstance;
    const CONNECTION_TIMEOUT = 7200;

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
        $regular = DatabaseManager::getSharedInstance()
            ->get("SELECT * FROM user_regulars WHERE (id = ? OR email = ?)", [$identifier, $identifier]);

        if ($regular['is_active'] == 0) {
            return ["success" => false, "error" => "user_not_active"];
        }

        $user = DatabaseManager::getSharedInstance()
            ->get("SELECT * FROM users WHERE id = ?  ", [$regular["id"]]);

        if (empty($user)) {
            return ["success" => false, "error" => "user_not_found"];
        }

        if (password_verify($password, $user["password"])) {
            $token = $this->createToken($user["id"]);
            if ($token !== false)
                return [$user["id"], $token];
        }
        return ["success" => false, "error" => "wrong_credentials"];
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
}
