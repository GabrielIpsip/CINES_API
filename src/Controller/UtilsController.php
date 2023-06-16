<?php


namespace App\Controller;


use App\Utils\StringTools;
use App\Utils\TempDirTools;
use Exception;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Swagger\Annotations as SWG;
use Symfony\Component\HttpFoundation\Response;
use App\Controller\AbstractController\ESGBUController;

/**
 * Class UtilsController
 * @package App\Controller
 * @SWG\Tag(name="Utils")
 */
class UtilsController extends ESGBUController
{
    /**
     * Encode string and get this file url.
     * @SWG\Response(
     *     response="200",
     *     description="String encoded."
     * )
     * @SWG\Parameter(name="body", in="body", description="Elasticsearch request",
     *     @SWG\Schema(type="object",
     *      @SWG\Property(property="encoding", type="string"),
     *      @SWG\Property(property="fileName", type="string"),
     *      @SWG\Property(property="content", type="string"))
     * )
     * @Rest\RequestParam(name="encoding")
     * @Rest\RequestParam(name="fileName")
     * @Rest\RequestParam(name="content")
     * @Rest\Post(
     *      path = "/public/utils/encode",
     *      name = "app_public_utils_encode"
     * )
     * @param string $encoding Encoding of result content.
     * @param string $fileName Name file to download.
     * @param string $content Content to encode in utf-8.
     * @return View
     */
    public function publicEncode(string $encoding, string $fileName, string $content): View
    {
        try
        {
            $rootDir = 'table_export';
            $directory = TempDirTools::getTempDir($rootDir);

            $fileName = str_replace('..', '', $fileName);
            $filePath = $directory . $fileName;
            file_put_contents($filePath, StringTools::getEncodedString($content, $encoding));

            $url = $this->apiUrl . $directory . rawurlencode($fileName);
            return $this->createView($url, Response::HTTP_OK);
        }
        catch (Exception $e)
        {
            return $this->createView('Error to convert file.', Response::HTTP_BAD_REQUEST);
        }
    }


}
