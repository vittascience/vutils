<?php

namespace Utils\Controller;

use Exception;
use Aws\S3\S3Client;
use User\Entity\User;
use GuzzleHttp\Client;
use OpenStack\OpenStack;
use User\Entity\Regular;
use Utils\Entity\UserAssets;
use OpenStack\Identity\v3\Api;
use Aws\S3\Exception\S3Exception;
use Utils\Entity\GenerativeAssets;
use Utils\Traits\UtilsAssetsTrait;
use OpenStack\Identity\v3\Models\Token;
use Utils\Entity\GenerativeAssetsDefault;

class ControllerUserAssets
{
    use UtilsAssetsTrait;
    protected $client;
    protected $token;
    protected $openstack;
    protected $target;
    protected $actions;
    protected $entityManager;
    protected $user;
    protected $whiteList;
    protected $clientS3;
    protected $bucket;
    protected $bucketGenerativeAssets;
    protected $bucketGenerativeAssetsEndpoint;

    public function __construct($entityManager, $user)
    {
        $this->client = new Client(['base_uri' => 'https://auth.cloud.ovh.net/v3/']);
        $this->token = new Token($this->client, new Api());
        $this->createTokenAndOpenStack($_ENV['VS_CLOUD_NAME'], $_ENV['VS_CLOUD_PASS'], $_ENV['VS_CLOUD_TENANT']);
        $this->target = empty($_GET["target"]) ? null : htmlspecialchars($_GET["target"]);
        $this->entityManager = $entityManager;
        $this->user = $user;
        $this->bucketGenerativeAssetsEndpoint = "https://vittai-generative-assets.s3.fr-par.scw.cloud/";
        $this->bucketGenerativeAssets = "vittai-generative-assets";
        $this->whiteList = ["adacraft", "ai-get", "ai-get-imgs", "ai-get-sounds", "get_one_generative_assets", "get_list_default_generative_assets", "get_my_generative_assets", "get_one_default_generative_assets", "get_public_generative_assets_per_page"];
        $this->clientS3 = new S3Client([
            'credentials' => [
                'key' => $_ENV['VS_S3_KEY'],
                'secret' => $_ENV['VS_S3_SECRET']
            ],
            'region' => 'fr-par',
            'version' => 'latest',
            'endpoint' => 'https://s3.fr-par.scw.cloud',
            'signature_version' => 'v4'
        ]);
        $this->bucket = "vittai-assets";
        if (isset($_ENV['VS_S3_BUCKET'])) {
            if (!empty($_ENV['VS_S3_BUCKET'])) {
                $this->bucket = $_ENV['VS_S3_BUCKET'];
            }
        }
    }

    public function action($action, $data = [])
    {
        $this->actions = array(
            'adacraft' => function () {
                if ($_SERVER['REQUEST_METHOD'] == "PUT" || $_SERVER['REQUEST_METHOD'] == "POST") {
                    $data = file_get_contents('php://input');
                    $name = $_GET["name"];

                    if (md5($data) == pathinfo($name)["filename"]) {
                        $options = [
                            'name'    => $name,
                            'content' => $data,
                        ];
                        /** @var \OpenStack\ObjectStore\v1\Models\StorageObject $object */
                        $objectUp = $this->openstack->objectStoreV1()
                            ->getContainer('scratch-assets')
                            ->createObject($options);
                    } else {
                        echo "Le fichier n'est pas valide.";
                    }
                } else if ($_SERVER['REQUEST_METHOD'] == "GET") {

                    $name = $_GET["name"];

                    /** @var \OpenStack\ObjectStore\v1\Models\StorageObject $object */
                    $object = $this->openstack->objectStoreV1()
                        ->getContainer('scratch-assets')
                        ->getObject($name)
                        ->download();

                    echo $object->getContents();
                }
            },
            "get" => function () {
                if ($_SERVER['REQUEST_METHOD'] == "GET") {
                    $name = $_GET["name"];
                    $isPublic = $this->isTheAssetPublic($name);
                    $assetOwner = $this->isUserLinkedToAsset($this->user['id'], $name);
                    if ($isPublic || $assetOwner) {
                        $objExist = $this->openstack->objectStoreV1()->getContainer($this->target)->objectExists($name);
                        if ($objExist) {
                            $objectUp = $this->openstack->objectStoreV1()->getContainer($this->target)->getObject($name);
                            $dataType = $this->dataTypeFromExtension($_GET["name"]);
                            if (!$dataType) {
                                return [
                                    "success" => false,
                                    "message" => "File type not supported.",
                                ];
                            }
                            $base64 = 'data:' . $dataType . ';base64,' . base64_encode($objectUp->download()->getContents());
                            return [
                                "success" => true,
                                "content" => $base64,
                            ];
                        }
                        return ["success" => false];
                    } else {
                        return [
                            "success" => false,
                            "message" => "You don't have access to this file.",
                        ];
                    }
                } else {
                    return [
                        "error" => "Method not allowed",
                    ];
                }
            },
            "put" => function () {
                if ($_SERVER['REQUEST_METHOD'] == "PUT") {
                    $content = file_get_contents('php://input');
                    $randomKey = md5(uniqid(rand(), true));
                    $name = $randomKey . '-' . $_GET["name"];
                    $isPublic = $_GET["isPublic"] == "true" ? true : false;
                    $dataType = $this->dataTypeFromExtension($_GET["name"]);
                    if (!$dataType) {
                        return [
                            "success" => false,
                            "message" => "File type not supported.",
                        ];
                    }
                    $options = [
                        'name'    => $name,
                        'content' => file_get_contents($content),
                    ];

                    $this->openstack->objectStoreV1()->getContainer($this->target)->createObject($options);
                    $this->linkAssetToUser($this->user['id'], $name, $isPublic);
                    return [
                        "name" => $name,
                        "success" => true
                    ];
                } else {
                    return [
                        "error" => "Method not allowed",
                    ];
                }
            },
            "delete" => function () {
                if ($_SERVER['REQUEST_METHOD'] == "DELETE") {
                    $name = $_GET["name"];
                    $authorization = $this->isUserLinkedToAsset($this->user['id'], $name);
                    if ($authorization) {
                        $objExist = $this->openstack->objectStoreV1()->getContainer($this->target)->objectExists($name);
                        if ($objExist) {
                            $this->openstack->objectStoreV1()->getContainer($this->target)->getObject($name)->delete();
                            $this->deleteUserLinkAsset($this->user['id'], $name);
                            return [
                                "name" => $name,
                                "success" => true
                            ];
                        } else {
                            return [
                                "message" => "Object not found",
                                "success" => false
                            ];
                        }
                    } else {
                        return [
                            "success" => false,
                            "message" => "You are not authorized to delete this asset.",
                        ];
                    }
                } else {
                    return [
                        "error" => "Method not allowed",
                    ];
                }
            },
            "update" => function () {
                if ($_SERVER['REQUEST_METHOD'] == "PUT") {
                    $name = $_GET["name"];
                    $authorization = $this->isUserLinkedToAsset($this->user['id'], $name);
                    if ($authorization) {
                        $objExist = $this->openstack->objectStoreV1()->getContainer($this->target)->objectExists($name);
                        if ($objExist) {
                            $data = file_get_contents('php://input');
                            $options = [
                                'name'    => $name,
                                'content' => file_get_contents($data),
                            ];

                            $dataType = $this->dataTypeFromExtension($_GET["name"]);
                            if (!$dataType) {
                                return [
                                    "success" => false,
                                    "message" => "File type not supported.",
                                ];
                            }

                            //$this->openstack->objectStoreV1()->getContainer($this->target)->getObject($name)->delete();
                            $this->openstack->objectStoreV1()->getContainer($this->target)->createObject($options);

                            return [
                                "name" => $name,
                                "success" => true
                            ];
                        } else {
                            return [
                                "message" => "Object not found",
                                "success" => false
                            ];
                        }
                    } else {
                        return [
                            "message" => "You are not allowed to update this asset",
                            "success" => false
                        ];
                    }
                } else {
                    return [
                        "error" => "Method not allowed",
                    ];
                }
            },
            "ai-put" => function () {
                $key = empty($_GET["key"]) ? null : $_GET["key"];
                $isPublic = true;
                $randomKey = md5(uniqid(rand(), true));
                $urlToReturn = [];
                $key = !empty($key) ? $key : $randomKey;

                $fileNames = [
                    "bin" => "$key-model.weights.bin",
                    "json" => "$key-model.json",
                ];

                foreach ($fileNames as $k => $file) {
                    if ($k == "json") {
                        $extension = 'text/json';
                    } else {
                        $extension = 'application/octet-stream';
                    }
                    $urlToReturn[] = $this->getUrlUpload($file, $this->bucket, $extension);
                    $this->linkAssetToUser($this->user['id'], $file, $isPublic);
                }

                return [
                    "success" => true,
                    "urls" => $urlToReturn,
                    "key" => $key,
                ];
            },
            "ai-put-meta" => function () {
                if ($_SERVER['REQUEST_METHOD'] == "PUT") {
                    $key = $_GET["key"];
                    $name = $key . '-' . $_GET["name"];
                    $isPublic = $_GET["isPublic"] == "true" ? true : false;
                    $dataType = $this->dataTypeFromExtension($_GET["name"]);
                    if (!$dataType) {
                        return [
                            "success" => false,
                            "message" => "File type not supported.",
                        ];
                    }

                    $metaUrl = $this->getUrlUpload($name, $this->bucket, 'text/json');
                    $this->linkAssetToUser($this->user['id'], $name, $isPublic);

                    return [
                        "success" => true,
                        "metaUrl" => $metaUrl,
                        "key" => $key,
                    ];
                } else {
                    return [
                        "error" => "Method not allowed",
                    ];
                }
            },
            "ai-get" => function () {
                if ($_SERVER['REQUEST_METHOD'] == "GET") {
                    $key = $_GET["key"];
                    $filesNames = [
                        "meta" => "$key-metadata.json",
                        "json" => "$key-model.json",
                        "bin" => "$key-model.weights.bin",
                    ];
                    $urlsToGet = [];
                    foreach ($filesNames as $fileName) {

                        $isPublic = $this->isTheAssetPublic($fileName);
                        $assetOwner = false;
                        if (!empty($this->user)) {
                            $assetOwner = $this->isUserLinkedToAsset($this->user['id'], $fileName);
                        }

                        if ($isPublic || $assetOwner) {
                            $cmd = $this->clientS3->getCommand('GetObject', [
                                'Bucket' => $this->bucket,
                                'Key' => $fileName
                            ]);
                            $request = $this->clientS3->createPresignedRequest($cmd, '+2 minutes');


                            $urlsToGet[] = [
                                "key" => $fileName,
                                "url" => (string) $request->getUri(),
                            ];
                        }
                    }

                    return [
                        "success" => true,
                        "urls" => $urlsToGet
                    ];
                } else {
                    return [
                        "error" => "Method not allowed",
                    ];
                }
            },
            "ai-delete" => function () {
                if ($_SERVER['REQUEST_METHOD'] == "DELETE") {
                    $key = $_GET["key"];
                    $filesNames = [
                        "meta" => "$key-metadata.json",
                        "json" => "$key-model.json",
                        "bin" => "$key-model.weights.bin",
                    ];

                    foreach ($filesNames as $fileName) {
                        $isPublic = $this->isTheAssetPublic($fileName);
                        $assetOwner = $this->isUserLinkedToAsset($this->user['id'], $fileName);
                        if ($isPublic || $assetOwner) {
                            $objExist = $this->openstack->objectStoreV1()->getContainer('ai-assets')->objectExists($fileName);
                            if ($objExist) {
                                $this->openstack->objectStoreV1()->getContainer('ai-assets')->getObject($fileName)->delete();
                                $this->deleteUserLinkAsset($this->user['id'], $fileName);
                            }
                        }
                    }

                    return [
                        "success" => true,
                    ];
                } else {
                    return [
                        "error" => "Method not allowed",
                    ];
                }
            },
            "ai-upload-imgs" => function () {
                if ($_SERVER['REQUEST_METHOD'] == "POST") {
                    $request = !empty($_POST['data']) ? $_POST['data'] : null;
                    $key = array_key_exists('key', $request) ? $request['key'] : null;
                    $images = $request['images'];

                    if (!$key) {
                        $key = md5(uniqid(rand(), true));
                    }

                    try {
                        $imagesToDelete = [];
                        $linkToDelete = [];
                        // get all linked image with the user who start by the key
                        $user = $this->entityManager->getRepository(User::class)->findOneBy(['id' => $_SESSION['id']]);
                        $existingImages = $this->entityManager->getRepository(UserAssets::class)->getUserAssetsQueryBuilderWithPrefixedKey($key, $user);
                        $imagesUrl = [];

                        $toDelete = true;
                        foreach ($existingImages as $existingImage) {
                            foreach ($images as $image) {
                                if (str_contains($existingImage->getLink(), $image['id'])) {
                                    $toDelete = false;
                                }
                            }
                            if ($toDelete) {
                                $imagesToDelete[] = ['Key' => $existingImage->getLink()];
                                $linkToDelete[] = $existingImage;
                            }
                        }

                        // Delete all image that are not in the new list
                        if (!empty($imagesToDelete)) {
                            $this->deleteMultipleAssetsS3($imagesToDelete, $this->bucket);
                        }

                        if (!empty($linkToDelete)) {
                            foreach ($linkToDelete as $link) {
                                $this->entityManager->remove($link);
                            }
                            $this->entityManager->flush();
                        }

                        foreach ($images as $image) {
                            $imageExist = false;
                            foreach ($existingImages as $existingImage) {
                                if ($existingImage->getLink() == $key . '-' . $image['id'] . '.png') {
                                    $imageExist = true;
                                }
                            }
                            if ($image['update'] == 'false' || $image['update'] == 'true' && !$imageExist) {
                                $name = $key . '-' . $image['id'] . '.png';
                                $imagesUrl[] = $this->getUrlUpload($name, $this->bucket, 'image/png');
                                $this->linkAssetToUser($this->user['id'], $name, true);
                            } else if ($image['update'] == 'true') {
                                $imagesUrl[] = false;
                            }
                        }

                        return [
                            "success" => true,
                            "urls" => $imagesUrl,
                            "key" => $key,
                        ];
                    } catch (Exception $e) {
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
            },
            "ai-get-imgs" => function () {
                if ($_SERVER['REQUEST_METHOD'] == "POST") {
                    $key = !empty($_POST['key']) ? $_POST['key'] : null;

                    if (!$key) {
                        return [
                            "success" => false,
                            "message" => "No key provided",
                        ];
                    }

                    $imagesToGet = [];
                    // get all linked image with the user who start by the key
                    $existingImagesFromS3 = $this->listObjectsFromBucket($this->bucket, $key);

                    if ($existingImagesFromS3 && !empty($existingImagesFromS3['Contents'])) {
                        foreach ($existingImagesFromS3['Contents'] as $image) {
                            $cmd = $this->clientS3->getCommand('GetObject', [
                                'Bucket' => $this->bucket,
                                'Key' => $image['Key']
                            ]);
                            $request = $this->clientS3->createPresignedRequest($cmd, '+2 minutes');

                            $imagesToGet[] = [
                                "key" => $image['Key'],
                                "url" => (string) $request->getUri(),
                            ];
                        }
                    }
                    return [
                        "success" => true,
                        "images" => $imagesToGet,
                    ];
                } else {
                    return [
                        "success" => false,
                        "error" => "Method not allowed",
                    ];
                }
            },
            "ai-upload-sounds" => function () {
                if ($_SERVER['REQUEST_METHOD'] == "POST") {
                    $request = !empty($_POST['data']) ? $_POST['data'] : null;
                    $key = array_key_exists('key', $request) ? $request['key'] : null;
                    $sounds = $request['sounds'];

                    if (!$key) {
                        $key = md5(uniqid(rand(), true));
                    }

                    try {
                        $soundsToDelete = [];
                        $linkToDelete = [];
                        $soundUrl = [];
                        // get all linked image with the user who start by the key
                        $user = $this->entityManager->getRepository(User::class)->findOneBy(['id' => $_SESSION['id']]);
                        $existingSounds = $this->entityManager->getRepository(UserAssets::class)->getUserAssetsQueryBuilderWithPrefixedKey($key, $user);

                        $toDelete = true;
                        foreach ($existingSounds as $existingSound) {
                            foreach ($sounds as $sound) {
                                if (str_contains($existingSound->getLink(), $sound['id'])) {
                                    $toDelete = false;
                                }
                            }
                            if ($toDelete) {
                                $soundsToDelete[] = ['Key' => $existingSound->getLink()];
                                $linkToDelete[] = $existingSound;
                            }
                        }

                        // Delete all image that are not in the new list
                        if (!empty($soundsToDelete)) {
                            $this->deleteMultipleAssetsS3($soundsToDelete, $this->bucket);
                        }

                        if (!empty($linkToDelete)) {
                            foreach ($linkToDelete as $link) {
                                $this->entityManager->remove($link);
                            }
                            $this->entityManager->flush();
                        }

                        foreach ($sounds as $sound) {
                            $existingSoundBool = false;
                            foreach ($existingSounds as $existingSound) {
                                if ($existingSound->getLink() == $key . '-' . $sound['id'] . '.json') {
                                    $existingSoundBool = true;
                                }
                            }
                            if ($sound['update'] == 'false' || $sound['update'] == 'true' && !$existingSoundBool) {
                                $name = $key . '-' . $sound['id'] . '.json';
                                $soundUrl[] = $this->getUrlUpload($name, $this->bucket, 'application/json');
                                $this->linkAssetToUser($this->user['id'], $name, true);
                            } else if ($sound['update'] == 'true') {
                                $soundUrl[] = false;
                            }
                        }

                        return [
                            "success" => true,
                            "urls" => $soundUrl,
                            "key" => $key,
                        ];
                    } catch (Exception $e) {
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
            },
            "ai-get-sounds" => function () {
                if ($_SERVER['REQUEST_METHOD'] == "POST") {
                    $key = !empty($_POST['key']) ? $_POST['key'] : null;

                    if (!$key) {
                        return [
                            "success" => false,
                            "message" => "No key provided",
                        ];
                    }

                    $soundsToGet = [];
                    // get all linked image with the user who start by the key
                    $existingSounds = $this->listObjectsFromBucket($this->bucket, $key);

                    if ($existingSounds && !empty($existingSounds['Contents'])) {
                        foreach ($existingSounds['Contents'] as $sound) {
                            $cmd = $this->clientS3->getCommand('GetObject', [
                                'Bucket' => $this->bucket,
                                'Key' => $sound['Key']
                            ]);
                            $request = $this->clientS3->createPresignedRequest($cmd, '+2 minutes');

                            $soundsToGet[] = [
                                "key" => $sound['Key'],
                                "url" => (string) $request->getUri(),
                            ];
                        }
                    }
                    return [
                        "success" => true,
                        "sounds" => $soundsToGet,
                    ];
                } else {
                    return [
                        "success" => false,
                        "error" => "Method not allowed",
                    ];
                }
            },
            "delete-assets" => function () {
                if ($_SERVER['REQUEST_METHOD'] == "POST") {
                    $keys = !empty($_POST['keys']) ? $_POST['keys'] : null;
                    $user = $this->entityManager->getRepository(User::class)->findOneBy(['id' => $_SESSION['id']]);

                    foreach ($keys as $key) {
                        $check = $this->checkKeyAndUser($key, $user);
                        if ($check['success'] == false) {
                            return $check;
                        }
                    }

                    $assetsDeleted = [];
                    $assetsS3Deleted = [];
                    $linkToDelete = [];
                    // get all linked image with the user who start by the key
                    foreach ($keys as $key) {
                        $existingAssets = $this->entityManager->getRepository(UserAssets::class)->getUserAssetsQueryBuilderWithPrefixedKey($key, $user);
                        foreach ($existingAssets as $asset) {
                            $assetsS3Deleted[] = ['Key' => $asset->getLink()];
                            $linkToDelete[] = $asset;
                        }
                    }
                    if (!empty($linkToDelete)) {
                        foreach ($linkToDelete as $link) {
                            $this->entityManager->remove($link);
                        }
                        $this->entityManager->flush();
                    }

                    if (!empty($assetsS3Deleted)) {
                        $this->deleteMultipleAssetsS3($assetsS3Deleted, $this->bucket);
                    }

                    return [
                        "success" => true,
                        "assets" => $assetsDeleted,
                        "assetsS3" => $assetsS3Deleted,
                    ];
                } else {
                    return [
                        "success" => false,
                        "error" => "Method not allowed",
                    ];
                }
            },
            "duplicate-assets" => function () {
                UtilsAssetsTrait::duplicateAssets($this->entityManager, []);
            },
            "generative_assets" => function () {
                try {
                    $name = array_key_exists('name', $_POST) ? htmlspecialchars($_POST['name']) : null;
                    $user = array_key_exists('user', $_POST) ? (int)htmlspecialchars($_POST['user']) : null; 
                    $prompt = array_key_exists('prompt', $_POST) ? htmlspecialchars($_POST['prompt']) : null;
                    $negativePrompt = array_key_exists('negativePrompt', $_POST) ? htmlspecialchars($_POST['negativePrompt']) : null;
                    $ipAddress = array_key_exists('ipAddress', $_POST) ? htmlspecialchars($_POST['ipAddress']) : null;
                    $width = array_key_exists('width', $_POST) ? htmlspecialchars($_POST['width']) : null;
                    $height = array_key_exists('height', $_POST) ? htmlspecialchars($_POST['height']) : null;
                    $cfgScale = array_key_exists('cfgScale', $_POST) ? htmlspecialchars($_POST['cfgScale']) : null;
                    $modelName = array_key_exists('modelName', $_POST) ? htmlspecialchars($_POST['modelName']) : null;
    
                    $lng = $_COOKIE['lang'] ?? 'en';
                    if (!$name) {
                        return [
                            "success" => false,
                            "message" => "No name provided",
                        ];
                    }
    
                    $dateNow = new \DateTime();
                    
                    $userCheck = null;
                    if (!empty($user)) {
                        $userBase = $this->entityManager->getRepository(User::class)->findOneBy(['id' => $user]);
                        if ($userBase) {
                            $userCheck = $userBase;
                        } else {
                            $RegularUser = $this->entityManager->getRepository(User::class)->findOneBy(['id' => $user]);
                            if ($RegularUser) {
                                $userCheck = $RegularUser;
                            }
                        }
                    }
    
                    $generativeAsset = new GenerativeAssets();
                    $generativeAsset->setName($name);
                    $generativeAsset->setUser($userCheck);
                    $generativeAsset->setCreatedAt($dateNow);
                    $generativeAsset->setPrompt($prompt);
                    $generativeAsset->setIpAddress($ipAddress);
                    $generativeAsset->setIsPublic(true);
                    $generativeAsset->setNegativePrompt($negativePrompt);
                    $generativeAsset->setLang($lng);
                    $generativeAsset->setWidth($width);
                    $generativeAsset->setHeight($height);
                    $generativeAsset->setCfgScale($cfgScale);
                    $generativeAsset->setLikes(0);
                    $generativeAsset->setModelName($modelName);
                    $generativeAsset->setAdminReview(true);
    
                    
                    $this->entityManager->persist($generativeAsset);
                    $this->entityManager->flush();

                    return [
                        "success" => true,
                        "message" => "generative_asset_created",
                    ];
                } catch (Exception $e) {
                    return [
                        "success" => false,
                        "message" => $e->getMessage(),
                    ];
                }

            },
            "get_one_generative_assets" => function () {
                try {
                    $id = array_key_exists('id', $_POST) ? htmlspecialchars($_POST['id']) : null;
                    $assets = $this->entityManager->getRepository(GenerativeAssetsDefault::class)->findOneBy(['id' => $id]);
                    if ($assets->getIspublic() == false || $assets->getUser() != null && $assets->getUser()->getId() != $_SESSION['id']) {
                        return [
                            "success" => false,
                            "message" => "not_allowed",
                        ];
                    }
    
                    $assetsData = [
                        "id" => $assets->getId(),
                        "url" => $this->bucketGenerativeAssetsEndpoint.$assets->getName(),
                        "prompt" => $assets->getPrompt(),
                        "negativePrompt" => $assets->getNegativePrompt(),
                        "width" => $assets->getWidth(),
                        "height" => $assets->getHeight(),
                        "cfgScale" => $assets->getCfgScale(),
                        "modelName" => $assets->getModelName(),
                    ];
                    return [
                        "success" => true,
                        "assets" => $assetsData,
                    ];
                } catch (Exception $e) {
                    return [
                        "success" => false,
                        "message" => $e->getMessage(),
                    ];
                }
            },
            "get_list_default_generative_assets" => function () {
                try {
                    $defaultGenerativeAssets = $this->entityManager->getRepository(GenerativeAssetsDefault::class)->findAll();
                    $projects = [];
                    foreach ($defaultGenerativeAssets as $asset) {
                        $imgUrls = [];
                        $arrayUrl = json_decode($asset->getName());
                        foreach ($arrayUrl as $url) {
                            $imgUrls[] = $url;
                        }
                        $projects[] = [
                            "id" => $asset->getId(),
                            "prompt" => $asset->getPrompt(),
                            "negativePrompt" => $asset->getNegativePrompt(),
                            "width" => $asset->getWidth(),
                            "height" => $asset->getHeight(),
                            "cfgScale" => $asset->getCfgScale(),
                            "modelName" => $asset->getModelName(),
                            "urls" => $imgUrls,
                        ];
                    }
    
                    return [
                        "success" => true,
                        "projects" => $projects,
                    ];
                } catch (Exception $e) {
                    return [
                        "success" => false,
                        "message" => $e->getMessage(),
                    ];
                }
            },
            "get_my_generative_assets" => function () {
                try {
                    $user = $this->entityManager->getRepository(User::class)->findOneBy(['id' => $_SESSION['id']]);
                    $myGenerativeAssets = $this->entityManager->getRepository(GenerativeAssets::class)->findBy(['user' => $user]);
                    $assetsUrls = [];
                    foreach ($myGenerativeAssets as $asset) {
                        $assetsUrls[] = [   
                            "id" => $asset->getId(), 
                            "url" => $this->bucketGenerativeAssetsEndpoint.$asset->getName(), 
                            "likes" => $asset->getLikes(),
                            "createdAt" => $asset->getCreatedAt()->format('Y-m-d H:i:s'),
                            "prompt" => $asset->getPrompt(),
                            "negativePrompt" => $asset->getNegativePrompt(),
                            "width" => $asset->getWidth(),
                            "height" => $asset->getHeight(),
                            "cfgScale" => $asset->getCfgScale(),
                            "modelName" => $asset->getModelName(),
                        ];
                    }
                    return [
                        "success" => true,
                        "assets" => $assetsUrls,
                    ];
                } catch (Exception $e) {
                    return [
                        "success" => false,
                        "message" => $e->getMessage(),
                    ];
                }
            },
            "get_one_default_generative_assets" => function () {
                try {
                    $id = array_key_exists('id', $_POST) ? htmlspecialchars($_POST['id']) : null;
                    $defaultGenerativeAsset = $this->entityManager->getRepository(GenerativeAssetsDefault::class)->findOneBy(['id' => $id]);
                    
                    if (!$defaultGenerativeAsset) {
                        return [
                            "success" => false,
                            "message" => "not_found",
                        ];
                    }

                    $imgUrls = [];
                    $arrayUrl = json_decode($defaultGenerativeAsset->getName());
                    foreach ($arrayUrl as $url) {
                        $imgUrls[] = $this->bucketGenerativeAssetsEndpoint.$url;
                    }
                    return [
                        "success" => true,
                        "assets" => [
                            "id" => $defaultGenerativeAsset->getId(),
                            "urls" => $imgUrls,
                            "prompt" => $defaultGenerativeAsset->getPrompt(),
                            "negativePrompt" => $defaultGenerativeAsset->getNegativePrompt(),
                            "width" => $defaultGenerativeAsset->getWidth(),
                            "height" => $defaultGenerativeAsset->getHeight(),
                            "cfgScale" => $defaultGenerativeAsset->getCfgScale(),
                            "modelName" => $defaultGenerativeAsset->getModelName(),
                        ],
                    ];
                } catch (Exception $e) {
                    return [
                        "success" => false,
                        "message" => $e->getMessage(),
                    ];
                }
            },
            "get_one_creator_generative_assets" => function () {
                try {
                    $id = array_key_exists('id', $_POST) ? htmlspecialchars($_POST['id']) : null;

                    $user = $this->entityManager->getRepository(User::class)->findOneBy(['id' => $id]);
                    if (!$user) {
                        return [
                            "success" => false,
                            "message" => "user_not_found",
                        ];
                    }


                    $creatorAssets = $this->entityManager->getRepository(GenerativeAssets::class)->findBy(['user' => $user, 'isPublic' => true]);

                    if (!$creatorAssets) {
                        return [
                            "success" => false,
                            "message" => "not_found",
                        ];
                    }

                    $assetsUrls = [];
                    foreach ($creatorAssets as $asset) {
                        $assetsUrls[] = [   
                            "id" => $asset->getId(), 
                            "url" => $this->bucketGenerativeAssetsEndpoint.$asset->getName(), 
                            "likes" => $asset->getLikes(),
                            "createdAt" => $asset->getCreatedAt()->format('Y-m-d H:i:s'),
                            "prompt" => $asset->getPrompt(),
                            "negativePrompt" => $asset->getNegativePrompt(),
                            "width" => (int)$asset->getWidth(),
                            "height" => (int)$asset->getHeight(),
                            "cfgScale" => $asset->getCfgScale(),
                            "modelName" => $asset->getModelName(),
                        ];
                    }

                    return [
                        "success" => true,
                        "assets" => $assetsUrls,
                    ];
                } catch (Exception $e) {
                    return [
                        "success" => false,
                        "message" => $e->getMessage(),
                    ];
                }
            },
            "get_public_generative_assets_per_page" => function () {
                try {
                    $page = array_key_exists('page', $_POST) ? htmlspecialchars($_POST['page']) : null;
                    $limit = 20;
                    $offset = ($page - 1) * $limit;
                    $publicGenerativeAssets = $this->entityManager->getRepository(GenerativeAssets::class)->findBy(['isPublic' => true], ['createdAt' => 'DESC'], $limit, $offset);
                    $assetsUrls = $this->manageGenerativeAssets($publicGenerativeAssets, false);
                    return [
                        "success" => true,
                        "assets" => $assetsUrls,
                    ];
                } catch (Exception $e) {
                    return [
                        "success" => false,
                        "message" => $e->getMessage(),
                    ];
                }
            },
            "increment_like_generative_assets" => function () {
                $id = array_key_exists('id', $_POST) ? htmlspecialchars($_POST['id']) : null;
                $generativeAsset = $this->entityManager->getRepository(GenerativeAssets::class)->findOneBy(['id' => $id]);
                $likes = $generativeAsset->getLikes();
                $generativeAsset->setLikes($likes + 1);
                $this->entityManager->persist($generativeAsset);
                $this->entityManager->flush();
                return [
                    "success" => true,
                    "likes" => $generativeAsset->getLikes(),
                ];
            },
            "decrement_like_generative_assets" => function () {
                $id = array_key_exists('id', $_POST) ? htmlspecialchars($_POST['id']) : null;
                $generativeAsset = $this->entityManager->getRepository(GenerativeAssets::class)->findOneBy(['id' => $id]);

                $likes = $generativeAsset->getLikes();
                if ($likes == 0) {
                    return [
                        "success" => false,
                        "message" => "You can't have negative likes.",
                    ];
                }

                $generativeAsset->setLikes($likes - 1);
                $this->entityManager->persist($generativeAsset);
                $this->entityManager->flush();
                return [
                    "success" => true,
                    "likes" => $generativeAsset->getLikes(),
                ];
            },
            "get_list_of_non_reviewed_generative_assets" => function() {
                // get fetch data 
                $content = file_get_contents("php://input");
                $content = json_decode($content, true);
                $page = array_key_exists('page', $content) ? htmlspecialchars($content['page']) : null;
                $limit = 20;
                $offset = ($page - 1) * $limit;
                try {
                    $generativeAssets = $this->entityManager->getRepository(GenerativeAssets::class)->findBy(['adminReview' => false], ['createdAt' => 'DESC'], $limit, $offset);
                    $assetsUrls = $this->manageGenerativeAssets($generativeAssets, true);
                    return [
                        "success" => true,
                        "assets" => $assetsUrls,
                    ];
                } catch (Exception $e) {
                    return [
                        "success" => false,
                        "message" => $e->getMessage(),
                    ];
                }
            },
            "get_total_page_of_non_reviewed_generative_assets" => function() {
                try {
                    $generativeAssets = $this->entityManager->getRepository(GenerativeAssets::class)->findBy(['adminReview' => false]);
                    return [
                        "success" => true,
                        "totalPages" => count($generativeAssets),
                    ];
                } catch (Exception $e) {
                    return [
                        "success" => false,
                        "message" => $e->getMessage(),
                    ];
                }
            },
            "get_list_of_all_generative_assets" => function() {
                $content = file_get_contents("php://input");
                $content = json_decode($content, true);
                $page = array_key_exists('page', $content) ? htmlspecialchars($content['page']) : null;
                $isPublic = array_key_exists('isPublic', $content) ? htmlspecialchars($content['isPublic']) : false;
                $limit = 20;
                $offset = ($page - 1) * $limit;
                $boolIsPublic = $isPublic == 1 ? true : false;

                try {
                    $generativeAssets = $this->entityManager->getRepository(GenerativeAssets::class)->findBy(['adminReview' => true, 'isPublic' => $boolIsPublic], ['createdAt' => 'DESC'], $limit, $offset);
                    $assetsUrls = $this->manageGenerativeAssets($generativeAssets, true);
                    return [
                        "success" => true,
                        "assets" => $assetsUrls,
                    ];
                } catch (Exception $e) {
                    return [
                        "success" => false,
                        "message" => $e->getMessage(),
                    ];
                }
            },
            "get_generative_assets_length" => function () {
                try {
                    $publicGenerativeAssets = $this->entityManager->getRepository(GenerativeAssets::class)->findBy(['isPublic' => true]);
                    return [
                        "success" => true,
                        "length" => count($publicGenerativeAssets)
                    ];
                } catch (Exception $e) {
                    return [
                        "success" => false,
                        "message" => $e->getMessage(),
                    ];
                }
            },
            "update_validation_for_generative_asset" => function () {
                $Autorisation = $this->entityManager->getRepository(Regular::class)->findOneBy(['user' => htmlspecialchars($_SESSION['id'])]);
                if ($Autorisation->isAdmin() == 0) {
                    return [
                        "success" => false,
                        "message" => "not_allowed",
                    ];
                }
                $content = file_get_contents("php://input");
                $content = json_decode($content, true);
                $id = array_key_exists('id', $content) ? htmlspecialchars($content['id']) : null;
                $isPublic = array_key_exists('is_public', $content) ? htmlspecialchars($content['is_public']) : false;
                $is_validated = array_key_exists('is_validated', $content) ? htmlspecialchars($content['is_validated']) : null;
                $boolIsPublic = $isPublic == 1 ? true : false;
                try {
                    $generativeAsset = $this->entityManager->getRepository(GenerativeAssets::class)->findOneBy(['id' => $id]);
                    $generativeAsset->setAdminReview($is_validated);
                    $generativeAsset->setIsPublic($boolIsPublic);
                    $this->entityManager->persist($generativeAsset);
                    $this->entityManager->flush();
                    return [
                        "success" => true,
                        "message" => "updated",
                    ];
                } catch (Exception $e) {
                    return [
                        "success" => false,
                        "message" => $e->getMessage(),
                    ];
                }
            },
            "get_reviewed_generative_assets_length" => function () {
                $content = file_get_contents("php://input");
                $content = json_decode($content, true);
                $isPublic = array_key_exists('is_public', $content) ? htmlspecialchars($content['is_public']) : false;
                $boolIsPublic = $isPublic == 1 ? true : false;
                try {
                    $publicGenerativeAssets = $this->entityManager->getRepository(GenerativeAssets::class)->findBy(['adminReview' => true, 'isPublic' => $boolIsPublic]);
                    return [
                        "success" => true,
                        "length" => count($publicGenerativeAssets)
                    ];
                } catch (Exception $e) {
                    return [
                        "success" => false,
                        "message" => $e->getMessage(),
                    ];
                }
            },
        );

        return call_user_func($this->actions[$action], $data);
    }

    function getGenerativeAssetsFromScaleway(string $key) {
        // get all linked image with the user who start by the key
        $existingImagesFromS3 = $this->listObjectsFromBucket($this->bucketGenerativeAssets, $key);

        if ($existingImagesFromS3 && !empty($existingImagesFromS3['Contents'])) {
            foreach ($existingImagesFromS3['Contents'] as $image) {
                $imagesToGet[] = $this->bucketGenerativeAssetsEndpoint . '/' . $image['Key'];
            }
        }
        return $imagesToGet;
    }

    function manageGenerativeAssets(array $generativeAssets = [], bool $includeMine = false) {
        foreach ($generativeAssets as $asset) {
            if ($asset->getIsPublic() == false && $includeMine == false) {
                continue;
            }
            $creator = [];
            if ($asset->getUser() != null) {
                $creator['id'] = $asset->getUser()->getId();
                $creator['firstname'] = $asset->getUser()->getFirstName();
                $creator['surname'] = $asset->getUser()->getSurname();
                $creator['picture'] = $asset->getUser()->getPicture();

            } else {
                $creator['id'] = null;
                $creator['firstname'] = "Anonymous";
                $creator['surname'] = "Anonymous";
            }

            $assetsUrls[] = [   
                "id" => $asset->getId(), 
                "url" => $this->bucketGenerativeAssetsEndpoint.$asset->getName(), 
                "likes" => $asset->getLikes(),
                "createdAt" => $asset->getCreatedAt()->format('Y-m-d H:i:s'),
                "prompt" => $asset->getPrompt(),
                "negativePrompt" => $asset->getNegativePrompt(),
                "width" => (int)$asset->getWidth(),
                "height" => (int)$asset->getHeight(),
                "cfgScale" => $asset->getCfgScale(),
                "modelName" => $asset->getModelName(),
                "creator" => $creator,
            ];
        }
        return $assetsUrls;
    }

    private function checkKeyAndUser($key, $user)
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

    private function linkAssetToUser(String $userId, String $link, Bool $isPublic)
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['id' => $userId]);
        $test = new UserAssets();
        $test->setUser($user);
        $test->setLink($link);
        $test->setIsPublic($isPublic);
        $this->entityManager->persist($test);
        $this->entityManager->flush();
    }

    private function deleteUserLinkAsset(String $userId, String $link)
    {
        $test = $this->entityManager->getRepository(UserAssets::class)->findOneBy(['user' => $userId, 'link' => $link]);
        $this->entityManager->remove($test);
        $this->entityManager->flush();
    }

    private function isUserLinkedToAsset(String $userId, String $link): bool
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['id' => $userId]);
        $test = $this->entityManager->getRepository(UserAssets::class)->findOneBy(['user' => $user, 'link' => $link]);
        if ($test) {
            return true;
        }
        return false;
    }

    private function isTheAssetPublic(String $link): bool
    {
        $test = $this->entityManager->getRepository(UserAssets::class)->findOneBy(['link' => $link]);
        if ($test) {
            return $test->getIsPublic();
        }
        return false;
    }

    private function deleteMultipleAssetsS3($objects, $bucket)
    {
        try {
            $this->clientS3->deleteObjects([
                'Bucket' => $bucket,
                'Delete' => [
                    'Objects' => $objects,
                ],
            ]);
        } catch (S3Exception $e) {
            return [
                "success" => false,
                "error" => $e->getMessage(),
            ];
        }
    }

    private function getUrlUpload($object, $bucket, $contentType)
    {
        try {
            $cmd = $this->clientS3->getCommand('PutObject', [
                'Bucket' => $bucket,
                'Key' => "$object",
                'contentType' => $contentType,
            ]);
            $request = $this->clientS3->createPresignedRequest($cmd, '+2 minutes');
            return ['key' => $object, 'url' => (string) $request->getUri()];
        } catch (S3Exception $e) {
            return [
                "success" => false,
                "error" => $e->getMessage(),
            ];
        }
    }

    private function listObjectsFromBucket($bucket, $prefix = null)
    {
        try {
            $results = $this->clientS3->listObjectsV2([
                'Bucket' => $bucket,
                'Prefix' => $prefix,
            ]);
            return $results;
        } catch (S3Exception $e) {
            return [
                "success" => false,
                "error" => $e->getMessage(),
            ];
        }
    }

    private function dataTypeFromExtension(String $fileName): ?String
    {
        $path = $fileName;
        $type = pathinfo($path, PATHINFO_EXTENSION);
        $dataType = "";
        $audio = ["mp3", "wav", "ogg", "flac", "aac", "m4a", "wma", "aiff", "ape", "mid", "mka", "mp2", "mp3", "mp4", "m4a", "m4b", "m4p", "m4r", "m4v", "mpa", "mpc", "mpp", "oga", "ogg", "opus", "ra", "rm", "rmi", "snd", "wav", "wma", "wv", "webm", "aac", "ac3", "aif", "aiff", "amr", "au", "caf", "dts", "flac", "m4a", "m4b", "m4p", "m4r", "m4v", "mp3", "mpc", "mpga", "oga", "ogg", "opus", "ra", "rm", "rmi", "snd", "wav", "wma", "wv", "webm"];
        $image = ["bmp", "gif", "ico", "jpeg", "jpg", "png", "psd", "svg", "tif", "tiff"];
        $video = ["3g2", "3gp", "3gp2", "3gpp", "asf", "avi", "flv", "m4v", "mov", "mp4", "mpg", "mpeg", "mpg4", "mpe", "mpv", "ogv", "qt", "swf", "vob", "wmv"];
        $text = ["csv", "doc", "docx", "html", "json", "log", "odp", "ods", "odt", "pdf", "ppt", "pptx", "rtf", "tex", "txt", "xls", "xlsx", "xml", "yaml", "yml"];
        $octet = ["bin"];

        if (in_array($type, $audio)) {
            $dataType = "audio/$type";
        } else if (in_array($type, $image)) {
            $dataType = "image/$type";
        } else if (in_array($type, $video)) {
            $dataType = "video/$type";
        } else if (in_array($type, $text)) {
            $dataType = "text/$type";
        } else if (in_array($type, $octet)) {
            $dataType = "application/octet-stream";
        } else {
            $dataType = false;
        }
        return $dataType;
    }

    private function createTokenAndOpenStack(String $name, String $password, String $tenant)
    {
        $this->token->create([
            'user' => [
                'name' => $name,
                'domain' => [
                    'name' => 'Default',
                ],
                'password' => $password,
            ],
        ]);

        $this->openstack = new OpenStack([
            'authUrl' => 'https://auth.cloud.ovh.net/v3/',
            'region' => 'GRA',
            'tenantName' => $tenant,
            'cachedToken' => $this->token->export(),
        ]);
    }

    private function isLogged()
    {
        if (empty($_SESSION['id'])) {
            return [
                "success" => false,
                "message" => "You must be logged in to access this feature.",
            ];
        }
    }
}
