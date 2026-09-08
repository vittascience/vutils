<?php

namespace Utils;

class UserDataUrl
{
    public static function isS3Only(): bool
    {
        self::loadEnvIfNeeded();
        return ($_ENV['VS_STORAGE_MODE'] ?? 'volume') === 's3';
    }

    public static function resolve(?string $relativePath): ?string
    {
        if (empty($relativePath)) {
            return null;
        }

        return self::base() . '/' . $relativePath;
    }

    public static function base(): string
    {
        if (!self::isS3Only()) {
            return '/public/content/user_data';
        }

        $base = !empty($_ENV['VS_S3_USER_PUBLIC_BASE_URL'])
            ? rtrim($_ENV['VS_S3_USER_PUBLIC_BASE_URL'], '/')
            : 'https://vittai-user-assets-dev.s3.fr-par.scw.cloud';

        return $base . '/user_data';
    }

    private static function loadEnvIfNeeded(): void
    {
        if (!empty($_ENV['VS_S3_KEY'])) {
            return;
        }
        if (!class_exists(\Dotenv\Dotenv::class)) {
            return;
        }

        $appRoot = dirname(__DIR__, 4);
        $dir  = is_file('/run/secrets/app_env') ? '/run/secrets' : $appRoot;
        $file = is_file('/run/secrets/app_env') ? 'app_env'      : '.env';

        try {
            \Dotenv\Dotenv::createImmutable($dir, $file)->safeLoad();
        } catch (\Throwable $e) {
        }
    }
}
