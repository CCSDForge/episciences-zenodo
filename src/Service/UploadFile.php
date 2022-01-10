<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\Exception\FileException;

class UploadFile
{
    public function uploadFileLocally($path,$depositFile){
        // foreach pour prévenir des multiple upload
        foreach ($depositFile as $fileInfo) {
            $originalFilename = pathinfo($fileInfo->getClientOriginalName(), PATHINFO_FILENAME).'.'.pathinfo($fileInfo->getClientOriginalName(), PATHINFO_EXTENSION);
            try {
                $fileInfo->move(
                    $path,
                    $originalFilename
                );
            }catch (FileException $e) {
                echo $e->getMessage();
            }
        }
    }
}


