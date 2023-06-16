<?php


namespace App\Common\Traits;


use ErrorException;
use Exception;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;

trait FileUploadTrait
{
    private function saveImg(UploadedFile $file, string $id, string $fileFolder, string $fileSubFolder,
                              string $mercureKey, string $mercureMessage): array
    {
        $imgName = $file->getClientOriginalName();
        $imgName = str_replace('..', '', $imgName);

        $folder = $fileFolder . "/$id/" . $fileSubFolder;

        $file = $file->move($folder, $imgName);

        $info = array();
        $info['imageUrl'] = $this->apiUrl . $folder . '/' . rawurlencode($file->getFilename());

        $this->sendMercureMessage($mercureKey, $mercureMessage);

        return $info;
    }

    private function saveDocument(UploadedFile $file, string $id, string $fileFolder, string $fileSubFolder): array
    {
        $authorizedExtension = ['png', 'jpeg', 'svg', 'gif', 'tiff', 'webp',
            'pdf', 'docx', 'xlsx', 'odt', 'csv', 'ods', 'txt', 'pptx'];

        if (!in_array($file->guessClientExtension(), $authorizedExtension))
        {
            throw new Exception('Unknown file type.', Response::HTTP_BAD_REQUEST);
        }

        $docName = $file->getClientOriginalName();
        $docName = str_replace('..', '', $docName);

        $folder = "$fileFolder/$id/$fileSubFolder";

        $file = $file->move($folder, $docName);

        $info = array();
        $info['docUrl'] = $this->apiUrl . rawurlencode($file->getPathname());

        return $info;
    }

    private function listFile(string $id, string $fileFolder, string $fileSubFolder): array
    {
        $result = array();
        $folder = $fileFolder . "/$id/" . $fileSubFolder;

        $files = array_diff(
            scandir($folder), array('..', '.'));

        foreach ($files as $file)
        {
            $fileInfo = array();
            $fileInfo['name'] = $file;
            $fileInfo['url'] = $this->apiUrl . $folder . '/' . rawurlencode($file);
            array_push($result, $fileInfo);
        }

        return $result;
    }

    private function deleteFile(string $url, string $fileFolder, array $fileTypeCheck)
    {
        $filePath = str_replace($this->apiUrl, '', $url);
        $filePath = str_replace('../', '', $filePath);
        $filePath = str_replace($fileFolder . '/', '', $filePath);

        $splitUrl = explode('/', $filePath);

        if (count($splitUrl) === 3)
        {
            $surveyId = $splitUrl[0];
            $fileType = $splitUrl[1];
            $fileName = $splitUrl[2];

            if (!in_array($fileType, $fileTypeCheck))
            {
                throw new ErrorException();
            }

            $filePath = "$fileFolder/$surveyId/$fileType";
            $filePath .= "/$fileName";

            unlink($filePath);
        }
        else
        {
            throw new ErrorException();
        }
    }
}
