<?php


namespace App\Controller;

use App\Common\Enum\Role;
use App\Common\Enum\State;
use App\Common\Traits\EditorialsTrait;
use App\Common\Traits\FileUploadTrait;
use App\Common\Traits\SurveysTrait;
use App\Utils\StringTools;
use ErrorException;
use Exception;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Swagger\Annotations as SWG;
use App\Entity\Editorials;
use Nelmio\ApiDocBundle\Annotation\Model;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;
use App\Entity\States;
use App\Controller\AbstractController\ESGBUController;

/**
 * Class EditorialsController
 * @package App\Controller
 * @SWG\Tag(name="Editorials")
 */
class EditorialsController extends ESGBUController
{
    use EditorialsTrait,
        SurveysTrait,
        FileUploadTrait;

    const EDITORIAL_KEY = 'http://esgbu.esr.gouv.fr/editorials';

    const FILE_FOLDER = 'editorial_files';
    const IMG_FOLDER = 'img';
    const DOC_FOLDER = 'doc';


    /**
     * Show all editorials.
     * @SWG\Response(
     *     response="200",
     *     description="List of all editorials.",
     *     @SWG\Schema(type="array",
     *      @SWG\Items(type="object",
     *          @SWG\Property(property="title", type="string"),
     *          @SWG\Property(property="survey",
     *              @SWG\Property(property="id", type="integer"),
     *              @SWG\Property(property="name", type="string"),
     *              @SWG\Property(property="calendarYear", type="string"),
     *              @SWG\Property(property="dataCalendarYear", type="string"),
     *              @SWG\Property(property="start", type="string"),
     *              @SWG\Property(property="end", type="string"),
     *              @SWG\Property(property="state", ref=@Model(type=States::class))))))
     * )
     * @SWG\Response(
     *     response="404",
     *     description="No editorial found.",
     * )
     * @Rest\Get(
     *      path = "/public/editorials",
     *      name = "app_public_editorials_list"
     * )
     * @Rest\View
     * @return View Array with all editorial.
     */
    public function publicListAction(): View
    {
        try
        {
            $editorials = $this->getAllEditorials(State::PUBLISHED);
            foreach ($editorials as &$editorial)
            {
                $editorial = $this->formatEditorialForPublic($editorial, false);
            }
            return $this->createView($editorials, Response::HTTP_OK);
        }
        catch(Exception $e)
        {
            return $this->createView($e->getMessage(), $e->getCode());
        }
    }

    /** Show editorial by survey id.
     * @SWG\Response(
     *     response="200",
     *     description="Return editorial select by survey id.",
     *     @Model(type=Editorials::class)
     * )
     * @SWG\Response(
     *     response="404",
     *     description="No editorial found.",
     * )
     * @SWG\Parameter(name="surveyId",type="integer", in="path", description="Survey id.")
     * @Rest\Get(
     *      path = "/public/editorials/{surveyId}",
     *      name = "app_public_editorials_show",
     *      requirements = {"surveyId"="\d+"}
     * )
     * @Rest\View
     * @param int $surveyId Survey id.
     * @return View Editorial information.
     */
    public function publicShowAction(int $surveyId): View
    {
        try
        {
            $editorial = $this->getEditorialBySurveyId($surveyId);
            $editorial = $this->formatEditorialForPublic($editorial);
            return $this->createView($editorial, Response::HTTP_OK);
        }
        catch (Exception $e)
        {
            return $this->createView($e->getMessage(), $e->getCode());
        }
    }


    /**
     * Create new editorial.
     * @SWG\Response(
     *     response="201",
     *     description="Editorial has been created.",
     *     @Model(type=Editorials::class)
     * )
     * @SWG\Response(response="404", description="No survey found.")
     * @SWG\Response(response="403", description="Not authorized.")
     * @SWG\Parameter(name="body", in="body", description="Editorial informations.",
     *     @SWG\Schema(type="object",
     *     @SWG\Property(property="title", type="string"),
     *     @SWG\Property(property="content", type="string"),
     *     @SWG\Property(property="surveyId", type="integer"))
     * )
     * @Rest\Post(path="/editorials", name="app_editorials_create")
     * @Rest\RequestParam(name="title", nullable=true)
     * @Rest\RequestParam(name="content", nullable=true)
     * @Rest\RequestParam(name="surveyId", nullable=false, requirements="[0-9]*")
     * @Rest\View
     * @param string|null $title Title of editorial.
     * @param array|null $content Content of editorial.
     * @param int $surveyId Id of survey linked with this editorial.
     * @return View Editorial has just been created.
     */
    public function createAction(?string $title, ?array $content, int $surveyId): View
    {
        try
        {
            $this->checkRights([Role::ADMIN]);
            $survey = $this->getSurveyById($surveyId);
            $editorial = new Editorials($title, json_encode($content), $survey);

            $em = $this->managerRegistry->getManager();
            $em->persist($editorial);
            $em->flush();

            return $this->createView($editorial, Response::HTTP_CREATED, true);
        }
        catch (Exception $e)
        {
            return $this->createView($e->getMessage(), $e->getCode(), true);
        }
    }

    /**
     * Update a editorial.
     * @SWG\Response(
     *     response="200",
     *     description="Editorial has been updated.",
     *     @Model(type=Editorials::class)
     * )
     * @SWG\Response(response="404", description="Survey not found.")
     * @SWG\Response(response="403", description="Not authorized.")
     * @SWG\Parameter(name="id", type="integer", in="path", description="Survey id of editorial to update.")
     * @SWG\Parameter(name="body", in="body", description="Editorial informations.",
     *     @SWG\Schema(type="object",
     *     @SWG\Property(property="title", type="string"),
     *     @SWG\Property(property="content", type="string"))
     * )
     * @Rest\Patch(
     *     path="/editorials/{id}",
     *     name="app_editorials_update",
     *     requirements={"id"="\d+"}
     * )
     * @Rest\RequestParam(name="title", nullable=true)
     * @Rest\RequestParam(name="content", nullable=true)
     * @Rest\View
     * @param int $id $Id of survey linked with the existing editorial to update.
     * @param string|null $title Title of editorial.
     * @param array|null $content Content of editorial.
     * @return View Editorial has just been updated.
     */
    public function updateAction(int $id, ?string $title, ?array $content): View
    {
        try
        {
            $this->checkRights([Role::ADMIN]);
            $editorial = $this->getEditorialBySurveyId($id);

            $editorial->setTitle($title);
            $editorial->setContent(json_encode($content));

            $this->managerRegistry->getManager()->flush();

            return $this->createView($editorial, Response::HTTP_OK, true);
        }
        catch (Exception $e)
        {
            return $this->createView($e->getMessage(), $e->getCode(), true);
        }
    }

    /**
     * Upload image for editorial.
     * @SWG\Response(
     *     response="200",
     *     description="Image informations.",
     *     @SWG\Schema(type="object",
     *       @SWG\Property(property="imageUrl", type="string")))
     * )
     * @SWG\Response(response="404", description="Survey not found.")
     * @SWG\Response(response="403", description="Not authorized.")
     * @Rest\Post(
     *     path="/editorials/image/{surveyId}",
     *     name="app_editorials_upload_image",
     *     requirements={"surveyId"="\d+"}
     * )
     * @Rest\FileParam(name="file", description="Image to upload", nullable=false, image=true)
     * @param int $surveyId
     * @param UploadedFile $file
     * @return View
     */
    public function uploadImageAction(int $surveyId, UploadedFile $file): View
    {
        try
        {
            $this->checkRights([Role::ADMIN]);
            $this->getSurveyById($surveyId);

            $info = $this->saveImg($file, $surveyId, self::FILE_FOLDER, self::IMG_FOLDER,
                self::EDITORIAL_KEY, 'editorials-image-upload');

            return $this->createView($info, Response::HTTP_OK, true);
        }
        catch (Exception $e)
        {
            return $this->createView($e->getMessage(), $e->getCode(), true);
        }
    }

    /**
     * Upload file for editorial.
     * @SWG\Response(
     *     response="200",
     *     description="Document informations.",
     *     @SWG\Schema(type="object",
     *       @SWG\Property(property="docUrl", type="string")))
     * )
     * @SWG\Response(response="404", description="Survey not found.")
     * @SWG\Response(response="403", description="Not authorized.")
     * @SWG\Response(response="500", description="Unknown file type.")
     * @Rest\Post(
     *     path="/editorials/document/{surveyId}",
     *     name="app_editorials_upload_document",
     *     requirements={"surveyId"="\d+"}
     * )
     * @Rest\FileParam(name="file", description="Document to upload", nullable=false)
     * @param int $surveyId
     * @param UploadedFile $file
     * @return View
     */
    public function uploadDocumentAction(int $surveyId, UploadedFile $file): View
    {
        try
        {
            $this->checkRights([Role::ADMIN]);
            $this->getSurveyById($surveyId);

            $info = $this->saveDocument($file, $surveyId, self::FILE_FOLDER, self::DOC_FOLDER);

            return $this->createView($info, Response::HTTP_OK, true);
        }
        catch (Exception $e)
        {
            return $this->createView($e->getMessage(), $e->getCode(), true);
        }
    }

    /**
     * Show all editorials files.
     * @SWG\Response(
     *     response="200",
     *     description="List of all editorial documents.",
     *     @SWG\Schema(type="array",
     *      @SWG\Items(type="object",
     *          @SWG\Property(property="name", type="string"),
     *          @SWG\Property(property="url", type="string")))
     * )
     * @SWG\Response(
     *     response="404",
     *     description="No editorial document found.",
     * )
     * @Rest\Get(
     *      path = "/public/editorials/document/{surveyId}",
     *      name = "app_public_editorials_document_list",
     *      requirements={"surveyId"="\d+"}
     * )
     * @Rest\QueryParam(name="img", strict=true, requirements="true|false", default="false")
     * @Rest\View
     * @param int $surveyId
     * @param string $img
     * @return View Array with all editorial.
     */
    public function publicListFileAction(int $surveyId, string $img): View
    {
        try
        {
            $img = StringTools::stringToBool($img);

            if ($img)
            {
                 $subFolder = self::IMG_FOLDER;
            }
            else
            {
                $subFolder = self::DOC_FOLDER;
            }

            $fileList = $this->listFile($surveyId, self::FILE_FOLDER, $subFolder);

            return $this->createView($fileList, Response::HTTP_OK);
        }
        catch (Exception $e)
        {
            return $this->createView('No document found.', Response::HTTP_NOT_FOUND);
        }
    }

    /**
     * Delete editorial document.
     * @SWG\Response(response="204", description="Document deleted.")
     * @SWG\Response(response="403", description="Not authorized.")
     * @SWG\Parameter(name="url",type="string", in="path", description="Url of file")
     * @Rest\Delete(
     *      path="/editorials/document/{url}",
     *      name="app_editorials_document_delete",
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

    private function formatEditorialForPublic(Editorials $editorial, bool $content = true): array
    {
        $response = [
            'title' => $editorial->getTitle(),
            'survey' => SurveysController::formatSurveyForPublic($editorial->getSurvey()),
        ];

        if ($content)
        {
            $response['content'] = $editorial->getContent();
        }
        return $response;
    }


}
