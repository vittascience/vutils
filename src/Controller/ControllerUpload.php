<?php

namespace Utils\Controller;

use Dotenv\Dotenv;

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
        $imageName = !empty($incomingData['name']) ? htmlspecialchars(strip_tags(trim($incomingData['name']))) : "";
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
        elseif ($imageSize > 1000000) array_push($errors, array("errorType" => "imageSizeToLarge"));

        // some errors found, return them
        if (!empty($errors)) return array('errors' => $errors);

        // no errors, we can process the data
        // replace whitespaces by _ and get the first chunk in case of duplicated ".someMisleadingExtensionBeforeTheRealFileExtension"
        $filenameWithoutSpaces = explode('.', str_replace(' ', '_', $imageName))[0];
        $filenameToUpload = time() . "_$filenameWithoutSpaces.$extension";

        // no errors, we can process the data
        // $uploadDir = __DIR__ . "/../../../../public/content/user_data/resources";
        $uploadDir = __DIR__ . "/../../../../{$this->envVariables['VS_RESOURCE_UPLOAD_DIR']}";

        $success = move_uploaded_file($imageTempName, "$uploadDir/$filenameToUpload");

        // something went wrong while storing the image, return an error
        if (!$success) {
            array_push($errors, array('errorType' => "imageNotStored"));
            return array('errors' => $errors);
        }

        // no errors, return data
        return array(
            "filename" => $filenameToUpload,
            "src" => "/{$this->envVariables['VS_RESOURCE_UPLOAD_DIR']}/$filenameToUpload"
        );
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
        $uploadDir = __DIR__ . "/../../../../../{$this->envVariables['VS_RESOURCE_UPLOAD_DIR']}";
        $success = move_uploaded_file($fileTempName, "$uploadDir/$filenameToUpload");

        // something went wrong while storing the file, return an error
        if (!$success) {
            array_push($errors, array('errorType' => "fileNotStored"));
            return array('errors' => $errors);
        }

        // no errors, return data
        return array(
            "filename" => $filenameToUpload,
            "src" => "/{$this->envVariables['VS_RESOURCE_UPLOAD_DIR']}/$filenameToUpload"
        );
    }
}
