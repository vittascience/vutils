<?php

namespace Utils;

require_once(__DIR__ . "/../vendor/autoload.php");

use Dotenv\Dotenv;

class CheckCaptcha
{
    static public function checkCaptcha($userCode, $userIP)
    {
        $curl = curl_init();
        $dir  = is_file('/run/secrets/app_env') ? '/run/secrets' : __DIR__ . '/../';
        $file = is_file('/run/secrets/app_env') ? 'app_env'      : '.env';
        Dotenv::createImmutable($dir, $file)->safeLoad();
        $params = "secret=" . $_ENV['VS_CAPTCHA_SECRET'] . "&response=" . $userCode . "&remoteip=" . $userIP;
        curl_setopt($curl, CURLOPT_URL, "https://www.google.com/recaptcha/api/siteverify");
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $answer = curl_exec($curl);
        $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        if ($code != 200)
            return false;
        curl_close($curl);
        if (!$answer)
            return $answer;
        $json = json_decode($answer, true);
        return $json["success"];
    }
}
