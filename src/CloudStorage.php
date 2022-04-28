<?php

namespace Utils;

use GuzzleHttp\Client;
use OpenStack\Identity\v3\Models\Token;
use OpenStack\Identity\v3\Api;
use OpenStack\OpenStack;

class CloudStorage
{
    protected $client;
    protected $token;
    protected $openstack;
    protected $target;
    protected $actions;
    protected $entityManager;
    protected $user;

    public function __construct($entityManager, $user)
    {
        $this->client = new Client(['base_uri' => 'https://auth.cloud.ovh.net/v3/']);
        $this->token = new Token($this->client, new Api());
        $this->createTokenAndOpenStack($_ENV['VS_CLOUD_NAME'], $_ENV['VS_CLOUD_PASS'], $_ENV['VS_CLOUD_TENANT']);
        $this->target = htmlspecialchars($_GET["target"]);
        $this->entityManager = $entityManager;
        $this->user = $user;

        if (!$this->user) {
            return [
                "error" => "You must be logged in to access to this.",
            ];
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
                        "error" => "Method not allowed",
                    ];
                }
            },
            "put" => function () {
                if ($_SERVER['REQUEST_METHOD'] == "PUT") {
                    $content = file_get_contents('php://input');
                    $randomKey = md5(uniqid(rand(), true));
                    $name = $randomKey . '-' . $_GET["name"];
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
                    $objExist = $this->openstack->objectStoreV1()->getContainer($this->target)->objectExists($name);
                    if ($objExist) {
                        // get obj
                        $objectUp = $this->openstack->objectStoreV1()->getContainer($this->target)->getObject($name);
                        $objectUp->delete();
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
                    return false;
                } else {
                    return [
                        "error" => "Method not allowed",
                    ];
                }

            },
            "update" => function () {
                if ($_SERVER['REQUEST_METHOD'] == "PUT") {
                    $name = $_GET["name"];
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
                        
                        $objectUp = $this->openstack->objectStoreV1()->getContainer($this->target)->getObject($name);
                        $objectUp->delete();

                        $this->openstack->objectStoreV1()->getContainer($this->target)->createObject($options);

                        return true;
                    } else {
                        return [
                            "message" => "Object not found",
                            "success" => false
                        ];
                    }
                } else {
                    return [
                        "error" => "Method not allowed",
                    ];
                }
            },
        );

        return call_user_func($this->actions[$action], $data);
    }

    private function dataTypeFromExtension(String $fileName):? String {
        $path = $fileName;
        $type = pathinfo($path, PATHINFO_EXTENSION);
        $dataType = "";
        $audio = ["mp3", "wav", "ogg", "flac", "aac", "m4a", "wma", "aiff", "ape", "mid", "mka", "mp2", "mp3", "mp4", "m4a", "m4b", "m4p", "m4r", "m4v", "mpa", "mpc", "mpp", "oga", "ogg", "opus", "ra", "rm", "rmi", "snd", "wav", "wma", "wv", "webm", "aac", "ac3", "aif", "aiff", "amr", "au", "caf", "dts", "flac", "m4a", "m4b", "m4p", "m4r", "m4v", "mp3", "mpc", "mpga", "oga", "ogg", "opus", "ra", "rm", "rmi", "snd", "wav", "wma", "wv", "webm"];
        $image = ["bmp", "gif", "ico", "jpeg", "jpg", "png", "psd", "svg", "tif", "tiff"];
        $video = ["3g2", "3gp", "3gp2", "3gpp", "asf", "avi", "flv", "m4v", "mov", "mp4", "mpg", "mpeg", "mpg4", "mpe", "mpv", "ogv", "qt", "swf", "vob", "wmv"];
        $text = ["csv", "doc", "docx", "html", "json", "log", "odp", "ods", "odt", "pdf", "ppt", "pptx", "rtf", "tex", "txt", "xls", "xlsx", "xml", "yaml", "yml"];
        if (in_array($type, $audio)) {
            $dataType = "audio/$type";
        } else if (in_array($type, $image)) {
            $dataType = "image/$type";
        } else if (in_array($type, $video)) {
            $dataType = "video/$type";
        } else if (in_array($type, $text)) {
            $dataType = "text/$type";
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
