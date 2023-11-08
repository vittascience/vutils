<?php

namespace Utils\Controller;

use Aws\S3\Exception\S3Exception;
use User\Entity\User;
use GuzzleHttp\Client;
use OpenStack\OpenStack;
use Utils\Entity\UserAssets;
use OpenStack\Identity\v3\Api;
use OpenStack\Identity\v3\Models\Token;
use Aws\S3\S3Client;


class ControllerUserAssets 
{
    protected $client;
    protected $token;
    protected $openstack;
    protected $target;
    protected $actions;
    protected $entityManager;
    protected $user;
    protected $whiteList;
    protected $clientS3;

    public function __construct($entityManager, $user)
    {
        $this->client = new Client(['base_uri' => 'https://auth.cloud.ovh.net/v3/']);
        $this->token = new Token($this->client, new Api());
        $this->createTokenAndOpenStack($_ENV['VS_CLOUD_NAME'], $_ENV['VS_CLOUD_PASS'], $_ENV['VS_CLOUD_TENANT']);
        $this->target = empty($_GET["target"]) ? null : htmlspecialchars($_GET["target"]);
        $this->entityManager = $entityManager;
        $this->user = $user;
        $this->whiteList = ["adacraft", "ai-get", "ai-get-imgs"];
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
    }

    public function action($action, $data = [])
    {

        if (empty($this->user) && !in_array($action, $this->whiteList)) {
            return [
                "error" => "You must be logged in to access to this.",
            ];
        }
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
                $files_array = $_FILES;
                $isPublic = true;
                $files_name = [];
                $randomKey = md5(uniqid(rand(), true));
                foreach ($files_array as $file) {
                    $name = !empty($key) ? $key . '-' . $file['name'] : $randomKey . '-' . $file['name'];
                    $files_name[] = $name;
                    $content = $file['tmp_name'];
                    $options = [
                        'name'    => $name,
                        'content' => file_get_contents($content),
                    ];

                    //if (!empty($key)) {
                    //    $this->openstack->objectStoreV1()->getContainer('ai-assets')->getObject($name)->delete();
                    //}

                    $this->openstack->objectStoreV1()->getContainer('ai-assets')->createObject($options);
                    $this->linkAssetToUser($this->user['id'], $name, $isPublic);

                }
                return [
                    "name" => $files_name,
                    "success" => true
                ];   
            },
            "ai-put-meta" => function () {
                if ($_SERVER['REQUEST_METHOD'] == "PUT") {
                    $content = file_get_contents('php://input');
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

                    $options = [
                        'name' => $name,
                        'content' => file_get_contents($content),
                    ];
                    
                    $this->openstack->objectStoreV1()->getContainer('ai-assets')->createObject($options);
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
            "ai-get" => function () {
                if ($_SERVER['REQUEST_METHOD'] == "GET") {
                    $key = $_GET["key"];
                    $Files = [];

                    $filesNames = [
                        "meta" => "$key-metadata.json",
                        "json" => "$key-model.json",
                        "bin" => "$key-model.weights.bin",
                    ];

                    foreach ($filesNames as $fileName) {

                        $isPublic = $this->isTheAssetPublic($fileName);
                        $assetOwner = false;
                        if (!empty($this->user)) {
                            $assetOwner = $this->isUserLinkedToAsset($this->user['id'], $fileName);
                        }

                        if ($isPublic || $assetOwner) {
                            $objExist = $this->openstack->objectStoreV1()->getContainer('ai-assets')->objectExists($fileName);
                            if ($objExist) {
                                $objectUp = $this->openstack->objectStoreV1()->getContainer('ai-assets')->getObject($fileName);
                                $dataType = $this->dataTypeFromExtension($fileName);
                                if (!$dataType) {
                                    return [
                                        "success" => false,
                                        "message" => "File type not supported.",
                                    ];
                                }
                                $base64 = 'data:' . $dataType . ';base64,' . base64_encode($objectUp->download()->getContents());
                                $Files[$fileName] = $base64;
                            }
                        } 
                    }

                    return [
                        "success" => true,
                        "files" => $Files,
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

                    $imagesToDelete = [];

                    // get all linked image with the user who start by the key
                    $user = $this->entityManager->getRepository(User::class)->findOneBy(['id' => $_SESSION['id']]);
                    $existingImages = $this->entityManager->getRepository(UserAssets::class)->getUserAssetsQueryBuilderWithPrefixedKey($key, $user);
                    $imagesUrl = [];

                    $toDelete = true;
                    foreach ($existingImages as $existingImage) {
                        foreach ($images as $image) {
                            if ($existingImage->getLink() == $image['id']) {
                                $toDelete = false; 
                            }
                        }
                        if (!$toDelete) {
                            $imagesToDelete[] = $existingImage->getLink();
                        }
                    }


                    // Delete all image that are not in the new list
                    if (!empty($imagesToDelete)) {
                        $this->deleteMultipleAssetsS3($imagesToDelete, 'vittai-assets');
                    }
                    foreach ($imagesToDelete as $imageToDelete) {
                        $this->deleteUserLinkAsset($this->user['id'], $imageToDelete);
                    }

                    
                    foreach ($images as $image) {
                        $name = $key . '-' . $image['id'] . '.png';
                        $imagesUrl[] = $this->getUrlUpload($name, 'vittai-assets', 'image/png');
                        $this->linkAssetToUser($this->user['id'], $name, true);
                    }

                    return [
                        "success" => true,
                        "urls" => $imagesUrl,
                        "key" => $key,
                    ];

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
                    $existingImagesFromS3 = $this->listObjectsFromBucket('vittai-assets', $key);
                    foreach ($existingImagesFromS3['Contents'] as $image) {
                        $cmd = $this->clientS3->getCommand('GetObject', [
                            'Bucket' => 'vittai-assets',
                            'Key' => $image['Key']
                        ]);
                        $request = $this->clientS3->createPresignedRequest($cmd, '+2 minutes');

                        $imagesToGet[] = [
                            "key" => $image['Key'],
                            "url" => (string) $request->getUri(),
                        ];
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

                    $soundsToDelete = [];
                    $soundsLinks = [];

                    // get all linked sound with the user who start by the key
                    $user = $this->entityManager->getRepository(User::class)->findOneBy(['id' => $_SESSION['id']]);
                    $existingSounds = $this->entityManager->getRepository(UserAssets::class)->getUserAssetsQueryBuilderWithPrefixedKey($key, $user);

                    $toDelete = true;
                    foreach ($existingSounds as $existingSound) {
                        foreach ($sounds as $sound) {
                            if ($existingSound->getLink() == $sound['id']) {
                                $toDelete = false; 
                            }
                        }
                        if (!$toDelete) {
                            $soundsToDelete[] = $existingSound->getLink();
                        }
                    }

                    // Delete all sounds that are not in the new list
                    foreach ($soundsToDelete as $soundToDelete) {
                        $this->openstack->objectStoreV1()->getContainer('ai-assets')->getObject($soundToDelete)->delete();
                        $this->deleteUserLinkAsset($this->user['id'], $soundToDelete);
                    }

                    foreach ($sounds as $sound) {

                        $name = $key . '-' . $sound['id'];
                        //check if the sound already exists
                        $objExist = $this->openstack->objectStoreV1()->getContainer('ai-assets')->objectExists($name);
                        if ($sound['content'] != 'false' && $objExist) {
                            $this->openstack->objectStoreV1()->getContainer('ai-assets')->getObject($name)->delete();
                        } else if ($sound['content'] == 'false') {
                            continue;
                        }

                        $content = $sound['content'];
                        $options = [
                            'name'    => $name,
                            'content' => file_get_contents($content),
                        ];

                        $this->openstack->objectStoreV1()->getContainer('ai-assets')->createObject($options);
                        $this->linkAssetToUser($this->user['id'], $name, true);
                        $soundsLinks[] = $name;
                    }

                    return [
                        "success" => true,
                        "sounds" => $soundsLinks,
                        "key" => $key,
                    ];

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
                    $existingSounds = $this->entityManager->getRepository(UserAssets::class)->getPublicAssetsQueryBuilderWithPrefixedKey($key);
                    foreach ($existingSounds as $sound) {
                        $objExist = $this->openstack->objectStoreV1()->getContainer('ai-assets')->objectExists($sound->getLink());
                        if ($objExist) {
                            $objectUp = $this->openstack->objectStoreV1()->getContainer('ai-assets')->getObject($sound->getLink());
                            $dataType = $this->dataTypeFromExtension($sound->getLink());
                            $base64 = 'data:application/json' . ';base64,' . base64_encode($objectUp->download()->getContents());
                            $soundsToGet[] = [
                                "id" => $sound->getLink(),
                                "content" => $base64,
                            ];   
                        }
                    }

                    return [
                        "success" => true,
                        "images" => $soundsToGet,
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
                    // get all linked image with the user who start by the key
                    foreach ($keys as $key) {
                        $existingAssets = $this->entityManager->getRepository(UserAssets::class)->getUserAssetsQueryBuilderWithPrefixedKey($key, $user);
                        foreach ($existingAssets as $asset) {
                            $objExist = $this->openstack->objectStoreV1()->getContainer('ai-assets')->objectExists($asset->getLink());
                            if ($objExist) {
                                $this->openstack->objectStoreV1()->getContainer('ai-assets')->getObject($asset->getLink())->delete();
                                $this->deleteUserLinkAsset($this->user['id'], $asset->getLink());  
                                $assetsDeleted[] = $asset->getLink();
                            }
                        }
                    }

                    return [
                        "success" => true,
                        "assets" => $assetsDeleted,
                    ];

                } else {
                    return [
                        "success" => false,
                        "error" => "Method not allowed",
                    ];
                }
            },
            "duplicate-assets" => function () {
                if ($_SERVER['REQUEST_METHOD'] == "POST") {
                    $keys = !empty($_POST['keys']) ? $_POST['keys'] : null;
                    $user = $this->entityManager->getRepository(User::class)->findOneBy(['id' => $_SESSION['id']]);

                    foreach ($keys as $key) {
                        $check = $this->checkKeyAndUser($key, $user);
                        if ($check['success'] == false) {
                            return $check;
                        }
                    }

                    $duplicatedKey = $key = md5(uniqid(rand(), true));
                    $assetsDuplicated = [];
                    // get all linked image with the user who start by the key
                    foreach ($keys as $key) {
                        $existingAssets = $this->entityManager->getRepository(UserAssets::class)->getPublicAssetsQueryBuilderWithPrefixedKey($key);
                        foreach ($existingAssets as $asset) {
                            $objExist = $this->openstack->objectStoreV1()->getContainer('ai-assets')->objectExists($asset->getLink());
                            if ($objExist) {

                                $objectUp = $this->openstack->objectStoreV1()->getContainer('ai-assets')->getObject($asset->getLink());
                                $dataType = $this->dataTypeFromExtension($asset->getLink());
                                $newAssetLink = str_replace($key, $duplicatedKey, $asset->getLink());
                                $options = [
                                    'name'    => $newAssetLink,
                                    'content' => $objectUp->download()->getContents(),
                                 ];
                                $this->openstack->objectStoreV1()->getContainer('ai-assets')->createObject($options);
                                $this->linkAssetToUser($this->user['id'], $newAssetLink, true);  
                                $assetsDuplicated[] = ['from' => $asset->getLink(), 'to' => $newAssetLink];
                            }
                        }
                    }

                    return [
                        "success" => true,
                        "assets" => $assetsDuplicated,
                    ];

                } else {
                    return [
                        "success" => false,
                        "error" => "Method not allowed",
                    ];
                }
            },
            "test_method" => function () {
                if ($_SERVER['REQUEST_METHOD'] == "POST") {
                    $key = !empty($_POST['key']) ? $_POST['key'] : null;
                    $imagesUrl = [];
                    try {
                        $cmd = $this->clientS3->getCommand('GetObject', [
                            'Bucket' => 'vittai-assets',
                            'Key' => 'imtb7c.png'
                        ]);
                        
                        $request = $this->clientS3->createPresignedRequest($cmd, '+2 minutes');

                        return [
                            "success" => true,
                            "url" => (string) $request->getUri(),
                        ];

                    } catch (S3Exception $th) {
                        return [
                            "success" => false,
                            "error" => $th,
                        ];
                    }
                } else {
                    return [
                        "success" => false,
                        "error" => "Method not allowed",
                    ];
                }
            },
            "test_method_post" => function () {
                if ($_SERVER['REQUEST_METHOD'] == "POST") {
                    $key = !empty($_POST['key']) ? $_POST['key'] : null;
                    $imagesUrl = [];

                    try {
                        $cmd = $this->clientS3->getCommand('PutObject', [
                            'Bucket' => 'vittai-assets',
                            'Key' => "$key.png",
                            'contentType' => 'image/png',
                        ]);

                        $request = $this->clientS3->createPresignedRequest($cmd, '+2 minutes');

                        return [
                            "success" => true,
                            "url" => (string) $request->getUri(),
                        ];
                    } catch (S3Exception $e) {
                        return [
                            "success" => false,
                            "error" => $e->getMessage(),
                        ];
                    }

                    return [
                        "success" => true,
                        "images" => $imagesUrl,
                    ];

                } else {
                    return [
                        "success" => false,
                        "error" => "Method not allowed",
                    ];
                }
            }
        );

        return call_user_func($this->actions[$action], $data);
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

    private function deleteUserLinkAssetsS3(String $key, User $user)
    {
        $UserLinkAssets = $this->entityManager->getRepository(UserAssets::class)->getAllAssetsByPrefixKey($key, $user);
        foreach ($UserLinkAssets as $UserLinkAsset) {
            $this->entityManager->remove($UserLinkAsset);
        }
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

    private function deleteMultipleAssetsS3($objects, $bucket) {
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

    private function getUrlUpload($object, $bucket, $contentType) {
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

    private function listObjectsFromBucket($bucket, $prefix = null) {
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

    private function dataTypeFromExtension(String $fileName):? String {
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

    private function createTokenAndOpenStack(String $name, String $password, String $tenant) {
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
}
