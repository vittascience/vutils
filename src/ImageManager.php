<?php

namespace Utils;

class ImageManager
{

    private static $sharedInstance;
    private function __construct()
    { }

    public static function getSharedInstance()
    {
        if (!isset(self::$sharedInstance)) {
            self::$sharedInstance = new ImageManager();
        }
        return self::$sharedInstance;
    }

    public function resizeToDimension($dimension, $source, $extension, $destination)
    {
        //get the image size
        $size = getimagesize($source);
        if (!$size)
            return false;


        $extension = strtolower($extension);

        //determine what the file extension of the source
        //image is
        switch ($extension) {
                //its a gif
            case 'gif':
                //create a gif from the source
                $destinationImage = imagecreatefromgif($source);
                break;
            case 'jpg':
            case 'jpeg':
                //create a jpg from the source
                $destinationImage = imagecreatefromjpeg($source);
                break;
            case 'png':
                //create a png from the source
                $destinationImage = imagecreatefrompng($source);
                break;
            default:
                return false;
                break;
        }

        if (!$destinationImage)
            return false;

        //exif only supports jpg in our supported file types
        if ($extension == "jpg" || $extension == "jpeg") {
            //fix photos taken on cameras that have incorrect
            //dimensions
            if (@exif_read_data($source)) {
                $exif = @exif_read_data($source);
                //get the orientation after checking if field exists in EXIF data.
                if (array_key_exists("Orientation", $exif)) {
                    $ort = $exif['Orientation'];
                    //determine what oreientation the image was taken at
                    switch ($ort) {
                        case 2: // horizontal flip
                            $destinationImage = $destinationImage;
                            $destinationImage = $this->imageFlip($destinationImage);
                            break;
                        case 3: // 180 rotate left
                            $destinationImage = imagerotate($destinationImage, 180, 1);
                            break;
                        case 4: // vertical flip
                            $destinationImage = $this->imageFlip($destinationImage);
                            break;
                        case 5: // vertical flip + 90 rotate right
                            $sourceImage = $this->imageFlip($destinationImage);
                            $destinationImage = imagerotate($sourceImage, -90, 1);
                            break;
                        case 6: // 90 rotate right
                            $destinationImage = imagerotate($destinationImage, -90, 1);
                            break;
                        case 7: // horizontal flip + 90 rotate right
                            $destinationImage = $this->imageFlip($destinationImage);
                            $destinationImage = imagerotate($destinationImage, -90, 1);
                            break;
                        case 8: // 90 rotate left
                            $destinationImage = imagerotate($destinationImage, 90, 1);
                            break;
                    }
                }
            }
        }
        // create the jpeg
        $res = imagejpeg($destinationImage, $destination, 50);
        imagedestroy($destinationImage);
        return $res;
    }


    public function imageFlip($image, $x = 0, $y = 0, $width = null, $height = null)
    {

        if ($width  < 1) $width  = imagesx($image);
        if ($height < 1) $height = imagesy($image);

        if (!$width || !$height)
            return false;

        // Truecolor provides better results, if possible.
        if (function_exists('imageistruecolor') && imageistruecolor($image)) {
            $tmp = imagecreatetruecolor(1, $height);
        } else {
            $tmp = imagecreate(1, $height);
        }
        if (!$tmp)
            return false;
        $x2 = $x + $width - 1;
        for ($i = (int) floor(($width - 1) / 2); $i >= 0; $i--) {
            // Backup right stripe.
            imagecopy($tmp, $image, 0, 0, $x2 - $i, $y, 1, $height);

            // Copy left stripe to the right.
            imagecopy($image, $image, $x2 - $i, $y, $x + $i, $y, 1, $height);

            // Copy backuped right stripe to the left.
            imagecopy($image, $tmp, $x + $i,  $y, 0, 0, 1, $height);
        }
        imagedestroy($tmp);
        return $image;
    }

    public function makeThumbnail($source, $destination, $desired_width)
    {
        /* read the source image */
        $source_image = imagecreatefromjpeg($source);
        if (!$source_image)
            return false;
        $width = imagesx($source_image);
        $height = imagesy($source_image);
        /* find the "desired height" of this thumbnail, relative to the desired width  */
        $desired_height = floor($height * ($desired_width / $width));
        /* create a new, "virtual" image */
        $virtual_image = imagecreatetruecolor($desired_width, $desired_height);
        if (!$virtual_image)
            return false;
        /* copy source image at a resized size */
        if (!imagecopyresampled($virtual_image, $source_image, 0, 0, 0, 0, $desired_width, $desired_height, $width, $height))
            return false;
        /* create the physical thumbnail image to its destination */
        return imagejpeg($virtual_image, $destination, 90);
    }

    public function makeMedium($source, $destination, $scale)
    {
        /* read the source image */
        $source_image = imagecreatefromjpeg($source);
        if (!$source_image)
            return false;
        $width = imagesx($source_image);
        $height = imagesy($source_image);
        /* find the "desired height" of this medium, relative to the desired width  */
        $desired_width = floor($width * $scale);
        $desired_height = floor($height * $scale);
        /* create a new, "virtual" image */
        $virtual_image = imagecreatetruecolor($desired_width, $desired_height);
        if (!$virtual_image)
            return false;
        /* copy source image at a resized size */
        if (!imagecopyresampled($virtual_image, $source_image, 0, 0, 0, 0, $desired_width, $desired_height, $width, $height))
            return false;
        /* create the physical thumbnail image to its destination */
        return imagejpeg($virtual_image, $destination, 90);
    }

    public function makeLazy($source, $destination)
    {
        /* read the source image */
        $source_image = imagecreatefromjpeg($source);
        if (!$source_image)
            return false;
        $width = imagesx($source_image);
        $height = imagesy($source_image);
        /* find the "desired height" of this lazy, relative to the desired width  */
        $desired_width = 40;
        $desired_height = 40;
        /* create a new, "virtual" image */
        $virtual_image = imagecreatetruecolor($desired_width, $desired_height);
        if (!$virtual_image)
            return false;
        /* copy source image at a resized size */
        if (!imagecopyresampled($virtual_image, $source_image, 0, 0, 0, 0, $desired_width, $desired_height, $width, $height))
            return false;
        /* create the physical thumbnail image to its destination */
        return imagejpeg($virtual_image, $destination, 90);
    }
}
