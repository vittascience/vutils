<?php

namespace Utils;

require_once(__DIR__ . "/../vendor/autoload.php");

use Spatie\PdfToImage\Pdf;

class PdfManager
{
    const PDF_THUMBNAIL_SIZE = 150;

    public static function pdfToImage($source, $destination)
    {
        try {
            $pdf = new Pdf($source);
            $pdf->setCompressionQuality(80);
            $pdf->setPage(1);
            $tmpFile = md5(uniqid()) . ".jpeg";
            $pdf->saveImage($tmpFile);

            if (!ImageManager::getSharedInstance()->makeThumbnail($tmpFile, $destination, self::PDF_THUMBNAIL_SIZE))
                return false;

            if (!unlink($tmpFile))
                return false;
        } catch (\Exception $e) {
            return false;
        }
        return true;
    }

    public static function countPdfPages($file)
    {
        try {
            $pdf = new Pdf($file);
        } catch (\Exception $e) {
            return false;
        }
        return $pdf->getNumberOfPages();
    }
}
