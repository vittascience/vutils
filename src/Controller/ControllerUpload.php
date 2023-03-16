<?php

namespace Utils\Controller;

use Dotenv\Dotenv;
use User\Entity\User;
use Utils\Entity\UserImg;

class ControllerUpload
{
    protected $actions = [];
    protected $entityManager;
    protected $user;

    public function __construct($entityManager, $user)
    {
        $dotenv = Dotenv::createImmutable(__DIR__."/../");
        $dotenv->safeLoad();
        $this->envVariables = $_ENV;
        $this->entityManager = $entityManager;
        $this->user = $user;
    }

    public function uploadImgFromTextEditor() {

        // accept only POST request
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return ["error" => "Method not Allowed"];

        // accept only connected user
        if (empty($_SESSION['id'])) return ["errorType" => "uploadImgFromTextEditorNotAuthenticated"];

        // bind and sanitize incoming data and 
        $incomingData = $_FILES['image'];
        $imageError = intval($incomingData['error']);
        
        $imageName = !empty($incomingData['name']) ? $incomingData['name'] : "";
        
        // replace whitespaces, " , ' by _ and get the first chunk in case of duplicated ".someMisleadingExtensionBeforeTheRealFileExtension"
        $filenameWithoutSpaces = explode('.', str_replace(["'", " ", '"'], "_", $imageName))[0];
        $filenameHtmlSpecial = htmlspecialchars(strip_tags(trim($filenameWithoutSpaces)));

        $imageTempName = !empty($incomingData['tmp_name']) ? htmlspecialchars(strip_tags(trim($incomingData['tmp_name']))) : "";
        $extension = !empty($incomingData['type'])
            ? htmlspecialchars(strip_tags(trim(
                explode('/', $incomingData['type'])[1]
            )))
            : "";
        $imageSize = !empty($incomingData['size']) ? intval($incomingData['size']) : 0;

        // initialize $errors array and check for errors if any
        $errors = [];
        if ($imageError !== 0) array_push($errors, array("errorType" => "imageUploadError"));
        if (empty($imageName)) array_push($errors, array("errorType" => "invalidImageName"));
        if (empty($imageTempName)) array_push($errors, array("errorType" => "invalidImageTempName"));
        if (empty($extension)) array_push($errors, array("errorType" => "invalidImageExtension"));
        if (!in_array($extension, array("jpg", "jpeg", "png", "svg", "webp", "gif", "apng"))) {
            array_push($errors, array("errorType" => "invalidImageExtension"));
        }
        if (empty($imageSize)) array_push($errors, array("errorType" => "invalidImageSize"));
        elseif ($imageSize > 3000000) array_push($errors, array("errorType" => "imageSizeToLarge"));

        // some errors found, return them
        if (!empty($errors)) return array('errors' => $errors);

        
        // no errors, we can process the data




        $filenameToUpload = time() . "_$filenameHtmlSpecial.$extension";

         // no errors, we can process the data
         $resourceUploadDir = !empty($this->envVariables['VS_RESOURCE_UPLOAD_DIR'])
            ? $this->envVariables['VS_RESOURCE_UPLOAD_DIR']
            : 'public/content/user_data/resources';
        $uploadDir = __DIR__ . "/../../../../../$resourceUploadDir";

        $success = move_uploaded_file($imageTempName, "$uploadDir/$filenameToUpload");

        // something went wrong while storing the image, return an error
        if (!$success) {
            array_push($errors, array('errorType' => "imageNotStored"));
            return array('errors' => $errors);
        }
        
        $user = $this->entityManager->getRepository(User::class)->find($this->user["id"]);
        $userImg = new UserImg();
        $userImg->setUser($user);
        $userImg->setImg($filenameToUpload);
        $userImg->setIsPublic(0); // default value is 0 (change it to 1 if you want to make it public)
        $this->entityManager->persist($userImg);
        $this->entityManager->flush();

        // no errors, return data
        return array(
            "filename" => $filenameToUpload,
            "src" => "/$resourceUploadDir/$filenameToUpload"
        );
    }


    public function getAllMyImages() {
        // accept only POST request
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return ["error" => "Method not Allowed"];
        if (empty($_SESSION['id'])) return ["errorType" => "uploadFileFromTextEditorNotAuthenticated"];


        $user = $this->entityManager->getRepository(User::class)->find($this->user["id"]);
        $userImgs = $this->entityManager->getRepository(UserImg::class)->findBy(["user" => $user]);
        $userFiles = [];
        foreach ($userImgs as $userImg) {
            array_push($userFiles, [
                "id" => $userImg->getId(),
                "filename" => $userImg->getImg(),
                "src" => "/public/content/user_data/resources/" . $userImg->getImg(),
                "isPublic" => $userImg->getIsPublic()
            ]);
        }

        return $userFiles;
    }

    public function deleteImage() {
        // accept only POST request
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return ["error" => "Method not Allowed"];
        if (empty($_SESSION['id'])) return ["errorType" => "uploadFileFromTextEditorNotAuthenticated"];

        $imageId = !empty($_POST['id']) ? intval($_POST['id']) : 0;
        if (empty($imageId)) return ["errorType" => "invalidImageId"];

        $user = $this->entityManager->getRepository(User::class)->find($this->user["id"]);
        $userImgs = $this->entityManager->getRepository(UserImg::class)->findBy(["user" => $user, "id" => $imageId]);

        if (empty($userImgs)) return ["errorType" => "imageNotFound"];

        $userImg = $userImgs[0];
        $filename = $userImg->getImg();
        $resourceUploadDir = !empty($this->envVariables['VS_RESOURCE_UPLOAD_DIR']) 
                            ? $this->envVariables['VS_RESOURCE_UPLOAD_DIR'] 
                            : 'public/content/user_data/resources';

        $uploadDir = __DIR__ . "/../../../../../$resourceUploadDir";

        unlink("$uploadDir/$filename");
        $this->entityManager->remove($userImg);
        $this->entityManager->flush();

        return ["success" => true, "id" => $imageId, "message" => "Image deleted successfully"];
    }


    public function uploadFileFromTextEditor() {

        // accept only POST request
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return ["error" => "Method not Allowed"];

        // accept only connected user
        if (empty($_SESSION['id'])) return ["errorType" => "uploadFileFromTextEditorNotAuthenticated"];
        // bind and sanitize incoming data and 
        $incomingData = $_FILES['file'];
        $fileError = intval($incomingData['error']);
        $fileName = !empty($incomingData['name']) ? htmlspecialchars(strip_tags(trim($incomingData['name']))) : "";
        $fileTempName = !empty($incomingData['tmp_name']) ? htmlspecialchars(strip_tags(trim($incomingData['tmp_name']))) : "";
        $extension = !empty($incomingData['type'])
            ? htmlspecialchars(strip_tags(trim(
                explode('/', $incomingData['type'])[1]
            )))
            : "";
        $fileSize = !empty($incomingData['size']) ? intval($incomingData['size']) : 0;

        // initialize $errors array and check for errors if any
        $errors = [];
        if ($fileError !== 0) array_push($errors, array("errorType" => "fileUploadError"));
        if (empty($fileName)) array_push($errors, array("errorType" => "invalidFileName"));
        if (empty($fileTempName)) array_push($errors, array("errorType" => "invalidFileTempName"));
        if (empty($extension)) array_push($errors, array("errorType" => "invalidFileExtension"));
        if (!in_array($extension, array("pdf"))) {
            array_push($errors, array("errorType" => "invalidFileExtension"));
        }
        if (empty($fileSize)) array_push($errors, array("errorType" => "invalidFileSize"));
        elseif ($fileSize > 5000000) array_push($errors, array("errorType" => "fileSizeToLarge"));

        // some errors found, return them
        if (!empty($errors)) return array('errors' => $errors);

        // no errors, we can process the data
        // replace whitespaces by _ and get the first chunk in case of duplicated ".someMisleadingExtensionBeforeTheRealFileExtension"
        $filenameWithoutSpaces = explode('.', str_replace(' ', '_', $fileName))[0];
        $filenameToUpload = time() . "_$filenameWithoutSpaces.$extension";

        // set the target dir and move file
        $resourceUploadDir = !empty($this->envVariables['VS_RESOURCE_UPLOAD_DIR'])
                                ? $this->envVariables['VS_RESOURCE_UPLOAD_DIR']
                                : 'public/content/user_data/resources';
        $uploadDir = __DIR__ . "/../../../../../$resourceUploadDir";
        $success = move_uploaded_file($fileTempName, "$uploadDir/$filenameToUpload");

        // something went wrong while storing the file, return an error
        if (!$success) {
            array_push($errors, array('errorType' => "fileNotStored"));
            return array('errors' => $errors);
        }

        // no errors, return data
        return array(
            "filename" => $filenameToUpload,
            "src" => "/$resourceUploadDir/$filenameToUpload"
        );
    }
}
