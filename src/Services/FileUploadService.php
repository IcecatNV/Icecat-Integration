<?php


namespace IceCatBundle\Services;


use Pimcore\Tool\Admin;

class FileUploadService extends InfoService
{
    public function saveFile($file, $user)
    {

        $userId = $user->getId();
        $newFileName = $file->getClientOriginalName();
        $uploadDir = self::$fileUploadPath . '/' . $userId;

        if (!$this->checkFilePathExist($uploadDir, true)) {
            echo 'error: unable to create dir';
        }
        if (file_exists($uploadDir . '/' . $newFileName)) {
            unlink($uploadDir . '/' . $newFileName);
        }
        $file->move($uploadDir . '/', $newFileName);
    }

    public function saveFileViaUrl($url, $user, $newFileName)
    {
        $userId = $user->getId();

        $uploadDir = self::$fileUploadPath . '/' . $userId;

        if (!$this->checkFilePathExist($uploadDir, true)) {
            echo 'error: unable to create dir';
        }
        if (file_exists($uploadDir . '/' . $newFileName)) {
            unlink($uploadDir . '/' . $newFileName);
        }

        file_put_contents("$uploadDir/$newFileName", file_get_contents($url, FILE_USE_INCLUDE_PATH));
    }
}
