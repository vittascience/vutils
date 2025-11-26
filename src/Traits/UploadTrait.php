<?php

namespace Utils\Traits;

use User\Entity\User;
use Utils\Entity\UserImg;
use Aws\S3\S3Client;
use Aws\Exception\AwsException;

trait UploadTrait
{
    protected $actions = [];
    protected $entityManager;
    protected $user;
    protected $s3Client;
    protected $s3Bucket;
    protected $s3PublicBaseUrl;

    public function __construct($entityManager, $user)
    {
        $this->entityManager = $entityManager;
        $this->user = $user;


        $this->s3PublicBaseUrl = rtrim($_ENV['VS_S3_USER_PUBLIC_BASE_URL'] ?? '', '/');
        $this->s3Client = new S3Client([
            'credentials' => [
                'key' => $_ENV['VS_S3_KEY'],
                'secret' => $_ENV['VS_S3_SECRET']
            ],
            'region' => 'fr-par',
            'version' => 'latest',
            'endpoint' => 'https://s3.fr-par.scw.cloud',
            'signature_version' => 'v4'
        ]);

        $this->s3Bucket = "user-assets-dev";
        if (isset($_ENV['VS_S3_BUCKET_USER'])) {
            if (!empty($_ENV['VS_S3_BUCKET_USER'])) {
                $this->s3Bucket = $_ENV['VS_S3_BUCKET_USER'];
            }
        }
    }

    protected function buildPublicUrl(string $key): string | false
    {
        if (!empty($this->s3PublicBaseUrl)) {
            return $this->s3PublicBaseUrl . '/' . ltrim($key, '/');
        }
        return false;
    }

    public function uploadImgFromTextEditor()
    {
        $title = !empty($_POST['title']) ? $_POST['title'] : null;

        $result = $this->handleUploadToS3([
            'fieldName' => 'image',
            'allowedExtensions' => ['jpg', 'jpeg', 'png', 'svg', 'webp', 'gif', 'apng'],
            'maxSize' => 3_000_000,
            'subDir' => 'user_data/resources',
            'customBaseName' => $title,
            'defaultContentType' => 'application/octet-stream',
            'requireAuth' => true,
        ]);

        if (!empty($result['errors'])) {
            return $result;
        }

        $user = $this->entityManager->getRepository(User::class)->find($this->user["id"]);
        $userImg = new UserImg();
        $userImg->setUser($user);
        $userImg->setImg($result['key']);
        $userImg->setIsPublic(0);

        $this->entityManager->persist($userImg);
        $this->entityManager->flush();

        return [
            'filename' => $result['filename'],
            'src' => $result['src'],
        ];
    }

    public function getAllMyImages()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return ["error" => "Method not Allowed"];
        if (empty($_SESSION['id'])) return ["errorType" => "uploadFileFromTextEditorNotAuthenticated"];

        $user = $this->entityManager->getRepository(User::class)->find($this->user["id"]);
        $userImgs = $this->entityManager->getRepository(UserImg::class)->findBy(["user" => $user]);
        $userFiles = [];

        foreach ($userImgs as $userImg) {
            $key = $userImg->getImg();
            $userFiles[] = [
                "id" => $userImg->getId(),
                "filename" => basename($key),
                "src" => $this->buildPublicUrl($key),
                "isPublic" => $userImg->getIsPublic(),
            ];
        }

        return $userFiles;
    }

    public function deleteImage()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return ["error" => "Method not Allowed"];
        if (empty($_SESSION['id'])) return ["errorType" => "uploadFileFromTextEditorNotAuthenticated"];

        $imageId = !empty($_POST['id']) ? intval($_POST['id']) : 0;
        if (empty($imageId)) return ["errorType" => "invalidImageId"];

        $user = $this->entityManager->getRepository(User::class)->find($this->user["id"]);
        $userImgs = $this->entityManager->getRepository(UserImg::class)->findBy(["user" => $user, "id" => $imageId]);

        if (empty($userImgs)) return ["errorType" => "imageNotFound"];

        $userImg = $userImgs[0];
        $key = $userImg->getImg();

        try {
            $this->s3Client->deleteObject([
                'Bucket' => $this->s3Bucket,
                'Key'    => $key,
            ]);
        } catch (AwsException $e) {
            return [
                "success" => false,
                "id"      => $imageId,
                "message" => "Image deletion failed: " . $e->getMessage(),
            ];
        }

        $this->entityManager->remove($userImg);
        $this->entityManager->flush();

        return ["success" => true, "id" => $imageId, "message" => "Image deleted successfully"];
    }

    protected function handleUploadToS3(array $options): array
    {
        $defaults = [
            'fieldName' => 'file',
            'allowedExtensions' => [],
            'maxSize' => 0,
            'subDir' => 'user_resources',
            'customBaseName' => null,
            'defaultContentType' => 'application/octet-stream',
            'requireAuth' => true,
        ];
        $options = array_merge($defaults, $options);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return ['errors' => [['errorType' => 'methodNotAllowed']]];
        }

        if ($options['requireAuth'] && empty($_SESSION['id'])) {
            return ['errors' => [['errorType' => 'notAuthenticated']]];
        }

        $fieldName = $options['fieldName'];

        if (empty($_FILES[$fieldName])) {
            return ['errors' => [['errorType' => 'fileMissing']]];
        }

        $incomingData = $_FILES[$fieldName];

        $fileError = intval($incomingData['error']);
        $rawName = $options['customBaseName'] ?? ($incomingData['name'] ?? '');
        $fileName = htmlspecialchars(strip_tags(trim($rawName)));
        $tmpName = $incomingData['tmp_name'] ?? '';
        $fileSize = isset($incomingData['size']) ? intval($incomingData['size']) : 0;
        $mimeType = $incomingData['type'] ?? $options['defaultContentType'];

        $extension = '';
        if (!empty($mimeType) && strpos($mimeType, '/') !== false) {
            $extension = explode('/', $mimeType)[1] ?? '';
        }
        if (empty($extension) && !empty($incomingData['name'])) {
            $parts = explode('.', $incomingData['name']);
            $extension = strtolower(end($parts));
        }
        $extension = htmlspecialchars(strip_tags(trim($extension)));

        $errors = [];

        if ($fileError !== 0) {
            $errors[] = ['errorType' => 'fileUploadError'];
        }
        if (empty($fileName)) {
            $errors[] = ['errorType' => 'invalidFileName'];
        }
        if (empty($tmpName)) {
            $errors[] = ['errorType' => 'invalidFileTempName'];
        }
        if (empty($extension)) {
            $errors[] = ['errorType' => 'invalidFileExtension'];
        } elseif (!empty($options['allowedExtensions']) && !in_array(strtolower($extension), $options['allowedExtensions'], true)) {
            $errors[] = ['errorType' => 'invalidFileExtension'];
        }

        if (empty($fileSize)) {
            $errors[] = ['errorType' => 'invalidFileSize'];
        } elseif (!empty($options['maxSize']) && $fileSize > $options['maxSize']) {
            $errors[] = ['errorType' => 'fileSizeTooLarge'];
        }

        if (!empty($errors)) {
            return ['errors' => $errors];
        }

        $base = explode('.', str_replace(["'", '"', ' '], '_', $fileName))[0];
        $base = htmlspecialchars(strip_tags(trim($base)));

        $filenameToUpload = time() . '_' . $base . '.' . $extension;
        $key = rtrim($options['subDir'], '/') . '/' . $filenameToUpload;

        try {
            $this->s3Client->putObject([
                'Bucket' => $this->s3Bucket,
                'Key' => $key,
                'SourceFile' => $incomingData['tmp_name'],
                'ACL' => 'public-read',
                'ContentType' => $mimeType ?: $options['defaultContentType'],
            ]);
        } catch (AwsException $e) {
            return [
                'errors' => [
                    [
                        'errorType' => 'fileNotStored',
                        'message'   => $e->getMessage(),
                    ]
                ]
            ];
        }

        return [
            'filename' => $filenameToUpload,
            'key' => $key,
            'src' => $this->buildPublicUrl($key),
            'mimeType' => $mimeType,
            'extension' => $extension,
            'size' => $fileSize,
            'fieldName' => $fieldName,
        ];
    }
}
