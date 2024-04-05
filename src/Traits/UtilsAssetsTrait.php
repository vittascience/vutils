<?php
namespace Utils\Traits;

use Aws\S3\S3Client;
use User\Entity\User;
use Utils\Entity\UserAssets;
use Aws\S3\Exception\S3Exception;

trait UtilsAssetsTrait {
    public static function duplicateAssets($entityManager) {
        if ($_SERVER['REQUEST_METHOD'] == "POST") {
            $keys = !empty($_POST['keys']) ? $_POST['keys'] : null;
            $user = $entityManager->getRepository(User::class)->findOneBy(['id' => $_SESSION['id']]);

            foreach ($keys as $key) {
                $check = self::checkKeyAndUser($key, $user);
                if ($check['success'] == false) {
                    return $check;
                }
            }

            $duplicatedKey = $key = md5(uniqid(rand(), true));
            $assetsDuplicated = [];

            try {

                $clientS3 = self::returnS3Client();
                $bucket = self::returnBucket();
                // get all linked image with the user who start by the key
                foreach ($keys as $key) {
                    $existingAssets = $entityManager->getRepository(UserAssets::class)->getPublicAssetsQueryBuilderWithPrefixedKey($key);
                    foreach ($existingAssets as $asset) {
                        $newAssetLink = str_replace($key, $duplicatedKey, $asset->getLink());
                        $result = $clientS3->copyObject([
                            'Bucket'     => $bucket,
                            'Key'        => $newAssetLink,
                            'CopySource' => "{$bucket}/{$asset->getLink()}"
                        ]);
                        if ($result["@metadata"]["statusCode"] == 200) {
                            self::linkAssetToUser($user->getId(), $newAssetLink, true, $entityManager);
                            $assetsDuplicated[] = ['from' => $asset->getLink(), 'to' => $newAssetLink];
                        } else {
                            return [
                                "success" => false,
                                "error" => "An error occured while duplicating the assets",
                            ];
                        }
                    }
                }

                return [
                    "success" => true,
                    "assets" => $assetsDuplicated,
                ];

            } catch (S3Exception $e) {
                return [
                    "success" => false,
                    "error" => $e->getMessage(),
                ];
            }
        } else {
            return [
                "success" => false,
                "error" => "Method not allowed",
            ];
        }
    }

    public static function returnS3Client() {
        return new S3Client([
            'credentials' => [
                'key' => $_ENV['VS_S3_KEY'],
                'secret' => $_ENV['VS_S3_SECRET']
            ],
            'region' => 'fr-par',
            'version' => 'latest',
            'endpoint' => 'https://s3.fr-par.scw.cloud',
            'signature_version' => 'v4'
        ]);
        
    }

    public static function returnBucket() {
        return "vittai-assets";
    }

    public static function checkKeyAndUser($key, $user)
    {
        if (!$user) {
            return [
                "success" => false,
                "message" => "User not found",
            ];
        }

        if (!$key) {
            return [
                "success" => false,
                "message" => "No key provided",
            ];
        }

        return [
            "success" => true
        ];
    }

    public static function linkAssetToUser(String $userId, String $link, Bool $isPublic, $entityManager)
    {
        $user = $entityManager->getRepository(User::class)->findOneBy(['id' => $userId]);
        $test = new UserAssets();
        $test->setUser($user);
        $test->setLink($link);
        $test->setIsPublic($isPublic);
        $entityManager->persist($test);
        $entityManager->flush();
    }
}

