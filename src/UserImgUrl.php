<?php

namespace Utils;

class UserImgUrl
{
    public static function resolve(?string $picture): ?string
    {
        if (empty($picture)) {
            return null;
        }

        if (empty($_ENV['VS_S3_KEY']) && class_exists(\Dotenv\Dotenv::class)) {
            $appRoot = dirname(__DIR__, 4);
            $dir  = is_file('/run/secrets/app_env') ? '/run/secrets' : $appRoot;
            $file = is_file('/run/secrets/app_env') ? 'app_env'      : '.env';
            try {
                \Dotenv\Dotenv::createImmutable($dir, $file)->safeLoad();
            } catch (\Throwable $e) {
            }
        }

        if (empty($_ENV['VS_S3_ONLY_USER_IMG'])) {
            return '/public/content/user_data/user_img/' . $picture;
        }

        $base = !empty($_ENV['VS_S3_USER_PUBLIC_BASE_URL'])
            ? rtrim($_ENV['VS_S3_USER_PUBLIC_BASE_URL'], '/')
            : 'https://vittai-user-assets-dev.s3.fr-par.scw.cloud';

        return $base . '/user_data/user_img/' . $picture;
    }
}
