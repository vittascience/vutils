<?php

namespace Utils;

use Utils\Traits\UploadTrait;

class BdcFileFinder
{
    use UploadTrait;

    public function findByName(string $name): ?string
    {
        if ($this->isS3OnlyMode()) {
            $keys = $this->listS3KeysByPrefix('user_data/shop_pdf/BDC-' . $name . '.');
            return !empty($keys) ? UserDataUrl::resolve(substr($keys[0], strlen('user_data/'))) : null;
        }

        $bdcBaseDir = realpath(dirname(__DIR__, 4) . '/public/content/user_data/shop_pdf');
        if (!$bdcBaseDir) {
            return null;
        }
        $files = glob($bdcBaseDir . '/BDC-' . $name . '.*');
        return !empty($files) ? UserDataUrl::resolve('shop_pdf/' . basename($files[0])) : null;
    }

    public function attachmentPath(string $name): ?string
    {
        $localPath = dirname(__DIR__, 4) . '/public/content/user_data/shop_pdf/' . $name;
        if (is_file($localPath)) {
            return $localPath;
        }

        $body = $this->getS3ObjectBody('user_data/shop_pdf/' . $name);
        if ($body === null) {
            return null;
        }

        $tmpPath = rtrim(sys_get_temp_dir(), '/') . '/' . uniqid('bdc_') . '_' . $name;
        if (file_put_contents($tmpPath, $body) === false) {
            return null;
        }
        register_shutdown_function(function () use ($tmpPath) {
            @unlink($tmpPath);
        });

        return $tmpPath;
    }

    public function allNames(): array
    {
        if ($this->isS3OnlyMode()) {
            $names = [];
            foreach ($this->listS3KeysByPrefix('user_data/shop_pdf/BDC-') as $key) {
                if (preg_match('#/BDC-(.+)\.[^./]+$#', $key, $m)) {
                    $names[] = $m[1];
                }
            }
            return $names;
        }

        $bdcBaseDir = realpath(dirname(__DIR__, 4) . '/public/content/user_data/shop_pdf');
        $names = [];
        if ($bdcBaseDir) {
            foreach (glob($bdcBaseDir . '/BDC-*.*') as $f) {
                if (preg_match('/^BDC-(.+)\.[^.]+$/', basename($f), $m)) {
                    $names[] = $m[1];
                }
            }
        }
        return $names;
    }
}
