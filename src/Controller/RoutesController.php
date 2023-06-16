<?php


namespace App\Controller;


use App\Common\Enum\Role;
use App\Common\Traits\FileUploadTrait;
use App\Controller\AbstractController\ESGBUController;
use App\Utils\StringTools;
use ErrorException;
use Exception;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Swagger\Annotations as SWG;
use App\Entity\Routes;
use App\Entity\RouteContents;
use Nelmio\ApiDocBundle\Annotation\Model;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;
use App\Common\Traits\RoutesTrait;

/**
 * Class RoutesController
 * @package App\Controller
 * @SWG\Tag(name="Routes")
 */
class RoutesController extends ESGBUController
{
    use RoutesTrait,
        FileUploadTrait;

    const ROUTE_KEY = 'http://esgbu.esr.gouv.fr/routes';

    const FILE_FOLDER = 'routes_files';
    const IMG_FOLDER = 'img';
    const DOC_FOLDER = 'doc';

    /**
     * Show all editorials.
     * @SWG\Response(
     *     response="200",
     *     description="List all editable routes.",
     *     @SWG\Schema(type="array",
     *      @SWG\Items(type="object", ref=@Model(type=Routes::class))))))
     * )
     * @SWG\Response(
     *     response="404",
     *     description="No route found.",
     * )
     * @Rest\Get(
     *      path = "/routes",
     *      name = "app_route_list"
     * )
     * @Rest\View
     * @return View Array with all route.
     */
    public function listAction(): View
    {
        try
        {
            $this->checkRights([Role::ADMIN, Role::ADMIN_RO]);
            $routes = $this->getAllRoutes();

            return $this->createView($routes, Response::HTTP_OK);
        }
        catch (Exception $e)
        {
            return $this->createView($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Show route content by name.
     *
     * @SWG\Response(
     *     response="200",
     *     description="Return route selected by name.",
     *     @SWG\Schema(type="object", ref=@Model(type=RouteContents::class))))
     * )
     * @SWG\Response(response="404", description="No route found.")
     * @SWG\Parameter(name="lang",type="string", in="query", description="Language code.")
     * @Rest\Get(
     *      path = "/public/routes/{name}",
     *      name = "app_routes_show",
     *     requirements = {"name"="[a-zA-Z0-9\-]+"}
     * )
     * @Rest\QueryParam(name="lang", requirements="[a-z]{2}", default=ESGBUController::DEFAULT_LANG)
     * @Rest\View
     * @param string $name Route name.
     * @param string $lang Content language.
     * @return View Route content.
     */
    public function publicShowAction(string $name, string $lang): View
    {
        try
        {
            $routeContent = $this->getRouteContentByName($name, $lang);
            return $this->createView($routeContent, Response::HTTP_OK);
        }
        catch (Exception $e)
        {
            return $this->createView($e->getMessage(), $e->getCode());
        }
    }


    /**
     * Update route content.
     * @SWG\Response(
     *     response="200",
     *     description="Route content has been updated.",
     *     @Model(type=RouteContents::class)
     * )
     * @SWG\Response(response="404", description="Route content not found.")
     * @SWG\Response(response="403", description="Not authorized.")
     * @SWG\Parameter(name="name", type="integer", in="path", description="Route name.")
     * @SWG\Parameter(name="lang",type="string", in="query", description="Language code.")
     * @SWG\Parameter(name="body", in="body", description="Route content informations.",
     *     @SWG\Schema(type="object", @SWG\Property(property="content", type="string"))
     * )
     * @Rest\Patch(
     *     path="/routes/{name}",
     *     name="app_routes_update",
     *     requirements={"name"="[a-zA-Z0-9\-]+"}
     * )
     * @Rest\RequestParam(name="content", nullable=true)
     * @Rest\QueryParam(name="lang", requirements="[a-z]{2}", default=ESGBUController::DEFAULT_LANG)
     * @Rest\View
     * @param string $name Route name.
     * @param string $lang Language of route content.
     * @param string $content Route content.
     * @return View Route content has just been updated.
     */
    public function updateAction(string $name, string $lang, string $content): View
    {
        try
        {
            $this->checkRights([Role::ADMIN]);

            $routeContent = $this->getRouteContentByName($name, $lang);
            $routeContent->setContent($content);

            $this->getDoctrine()->getManager()->flush();

            return $this->createView($routeContent, Response::HTTP_OK, true);
        }
        catch (Exception $e)
        {
            return $this->createView($e->getMessage(), $e->getCode(), true);
        }
    }

    /**
     * Upload image for editable route.
     * @SWG\Response(
     *     response="200",
     *     description="Image informations.",
     *     @SWG\Schema(type="object",
     *       @SWG\Property(property="imageUrl", type="string")))
     * )
     * @SWG\Response(response="404", description="Route not found.")
     * @SWG\Response(response="403", description="Not authorized.")
     * @Rest\Post(
     *     path="/routes/image/{name}",
     *     name="app_routes_upload_image",
     *     requirements={"name"="[a-zA-Z0-9\-]+"}
     * )
     * @Rest\FileParam(name="file", description="Image to upload", nullable=false, image=true)
     * @param string $name
     * @param UploadedFile $file
     * @return View
     */
    public function uploadImageAction(string $name, UploadedFile $file): View
    {
        try
        {
            $this->checkRights([Role::ADMIN]);
            $this->getRouteByName($name);

            $info = $this->saveImg($file, $name, self::FILE_FOLDER, self::IMG_FOLDER,
                self::ROUTE_KEY, 'routes-image-upload');

            return $this->createView($info, Response::HTTP_OK, true);
        }
        catch (Exception $e)
        {
            return $this->createView($e->getMessage(), $e->getCode(), true);
        }
    }

    /**
     * Upload file for route.
     * @SWG\Response(
     *     response="200",
     *     description="Document informations.",
     *     @SWG\Schema(type="object",
     *       @SWG\Property(property="docUrl", type="string")))
     * )
     * @SWG\Response(response="404", description="No route found.")
     * @SWG\Response(response="403", description="Not authorized.")
     * @SWG\Response(response="500", description="Unknown file type.")
     * @Rest\Post(
     *     path="/routes/document/{name}",
     *     name="app_routes_upload_document",
     *     requirements={"name"="[a-zA-Z0-9\-]+"}
     * )
     * @Rest\FileParam(name="file", description="Document to upload", nullable=false)
     * @param string $name
     * @param UploadedFile $file
     * @return View
     */
    public function uploadDocumentAction(string $name, UploadedFile $file): View
    {
        try
        {
            $this->checkRights([Role::ADMIN]);
            $this->getRouteByName($name);

            $info = $this->saveDocument($file, $name, self::FILE_FOLDER, self::DOC_FOLDER);

            return $this->createView($info, Response::HTTP_OK, true);
        }
        catch (Exception $e)
        {
            return $this->createView($e->getMessage(), $e->getCode(), true);
        }
    }

    /**
     * Show all route document.
     * @SWG\Response(
     *     response="200",
     *     description="List of all route document.",
     *     @SWG\Schema(type="array",
     *      @SWG\Items(type="object",
     *          @SWG\Property(property="name", type="string"),
     *          @SWG\Property(property="url", type="string")))
     * )
     * @SWG\Response(
     *     response="404",
     *     description="No route document found.",
     * )
     * @Rest\Get(
     *      path = "/public/routes/document/{name}",
     *      name = "app_route_document_list",
     *      requirements={"name"="[a-zA-Z0-9\-]+"}
     * )
     * @Rest\QueryParam(name="img", strict=true, requirements="true|false", default="false")
     * @Rest\View
     * @param string $name Route name.
     * @return View Array with all editorial.
     */
    public function publicListFileAction(string $name, string $img): View
    {
        try
        {
            $this->getRouteByName($name);

            $img = StringTools::stringToBool($img);
            if ($img)
            {
                $subFolder = self::IMG_FOLDER;
            }
            else
            {
                $subFolder = self::DOC_FOLDER;
            }

            $fileList = $this->listFile($name, self::FILE_FOLDER, $subFolder);

            return $this->createView($fileList, Response::HTTP_OK);
        }
        catch (Exception $e)
        {
            return $this->createView('No document found.', Response::HTTP_NOT_FOUND);
        }
    }

    /**
     * Delete route document.
     * @SWG\Response(response="204", description="Image deleted.")
     * @SWG\Response(response="403", description="Not authorized.")
     * @SWG\Parameter(name="url",type="string", in="path", description="Url of file")
     * @Rest\Delete(
     *      path="/routes/document/{url}",
     *      name="app_routes_document_delete",
     *     requirements={"url"=".*"}
     * )
     * @Rest\View
     * @param string $url
     * @return View Information about action.
     */
    public function deleteFileAction(string $url): View
    {
        try
        {
            $this->checkRights([Role::ADMIN]);

            $this->deleteFile($url, self::FILE_FOLDER, [self::IMG_FOLDER, self::DOC_FOLDER]);

            return $this->createView('Document deleted.', Response::HTTP_NO_CONTENT, true);
        }
        catch (ErrorException $e)
        {
            return $this->createView('Document not found.', Response::HTTP_NOT_FOUND, true);
        }
        catch (Exception $e)
        {
            return $this->createView($e->getMessage(), $e->getCode(), true);
        }
    }
}
