<?php

namespace Utils;

require($_SERVER["DOCUMENT_ROOT"] . "/vendor/autoload.php");


class PicturesUtils
{
    public static function checkPicture($picture, $ratioMin = 1, $ratioMax = 3, $widthMin = 300, $max_picture_size = 10000000)
    {
        if ($picture != 'basic.png') {
            $arrayData = [];
            if (preg_match("/image\/png/", $picture["type"])) {
                $arrayData['success'] = true;
                $arrayData['ext'] = 'png';
            } else if (preg_match("/image\/jpeg/", $picture["type"])) {
                $arrayData['success'] = true;
                $arrayData['ext'] = 'jpeg';
            } else if (preg_match("/image\/jpg/", $picture["type"])) {
                $arrayData['success'] = true;
                $arrayData['ext'] = 'jpg';
            } else {
                $arrayData['success'] = false;
            }
            if ($picture["size"] > $max_picture_size)
                $arrayData['success'] = false;
            list($width, $height) = getimagesize($picture["tmp_name"]);
            if ($width < $widthMin)
                $arrayData['success'] = false;
            $ratio = $width / $height;
            if ($ratio < $ratioMin || $ratio > $ratioMax)
                $arrayData['success'] = false;
            return $arrayData;
        }
    }
}
