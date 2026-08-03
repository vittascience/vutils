<?php

namespace Utils;

require_once(__DIR__ . "/../vendor/autoload.php");

use Dotenv\Dotenv;
use Database\DataBaseManager;

/**
 * Mobile app only — issues and validates long-lived, per-device tokens
 * (mobile_device_tokens) so the app can silently re-authenticate after its
 * PHP session dies, through arbitrarily long offline stretches, without
 * invalidating any other device's token.
 *
 * Deliberately independent from ConnectionManager/connection_tokens: that
 * table already carries session bookkeeping, an account-deletion CSRF
 * secret, and an unrelated partner (Campus Numeria) bearer credential, and
 * is one row per user rather than per device. This class and its table
 * share no code and no rows with that mechanism.
 */
class DeviceTokenManager
{
    private static $sharedInstance;
    protected $envVariables;

    public static function getSharedInstance()
    {
        if (!isset(self::$sharedInstance)) {
            self::$sharedInstance = new DeviceTokenManager();
        }
        return self::$sharedInstance;
    }

    private function __construct()
    {
        $dir  = is_file('/run/secrets/app_env') ? '/run/secrets' : __DIR__ . '/../../../../';
        $file = is_file('/run/secrets/app_env') ? 'app_env'      : '.env';
        Dotenv::createImmutable($dir, $file)->safeLoad();
        $this->envVariables = $_ENV;
    }

    /**
     * Upserts by (user_ref, device_id) — a new login on a given device
     * replaces only that device's row, never another device's. Returns the
     * new token string, or false on failure.
     */
    public function issueToken($userId, $deviceId, $deviceName = null, $platform = null)
    {
        try {
            $token = bin2hex(random_bytes(32));
            $res = DataBaseManager::getSharedInstance()->exec(
                "INSERT INTO mobile_device_tokens (user_ref, token, device_id, device_name, platform, date_inserted, last_used_at, revoked_at)
                 VALUES (?, ?, ?, ?, ?, NOW(), NOW(), NULL)
                 ON DUPLICATE KEY UPDATE token = VALUES(token), device_name = VALUES(device_name), platform = VALUES(platform), last_used_at = NOW(), revoked_at = NULL",
                [$userId, $token, $deviceId, $deviceName, $platform]
            );
            if ($res) {
                return $token;
            }
        } catch (\Exception $e) {
            return false;
        }

        return false;
    }

    /**
     * Returns the token's owning user id (as the row itself defines it,
     * not as a caller-supplied user_id would claim), or null if the token
     * is unknown or revoked. Bumps last_used_at (informational only — this
     * mechanism has no inactivity-based expiry, see issueToken()'s header).
     */
    public function validateToken($token)
    {
        $row = DataBaseManager::getSharedInstance()
            ->get("SELECT * FROM mobile_device_tokens WHERE token = ? AND revoked_at IS NULL", [$token]);

        if (!$row) {
            return null;
        }

        DataBaseManager::getSharedInstance()
            ->exec("UPDATE mobile_device_tokens SET last_used_at = NOW() WHERE id = ?", [$row['id']]);

        return intval($row['user_ref']);
    }

    public function revokeToken($userId, $token)
    {
        return DataBaseManager::getSharedInstance()
            ->exec(
                "UPDATE mobile_device_tokens SET revoked_at = NOW() WHERE user_ref = ? AND token = ? AND revoked_at IS NULL",
                [$userId, $token]
            );
    }

    /**
     * Called on password change, email change, and (indirectly, via the
     * table's own FK ON DELETE CASCADE) account deletion.
     */
    public function revokeAllForUser($userId)
    {
        return DataBaseManager::getSharedInstance()
            ->exec(
                "UPDATE mobile_device_tokens SET revoked_at = NOW() WHERE user_ref = ? AND revoked_at IS NULL",
                [$userId]
            );
    }
}
