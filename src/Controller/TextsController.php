<?php


namespace App\Controller;

use App\Common\Enum\Role;
use App\Common\Traits\DataTypesTrait;
use App\Common\Traits\TextsTrait;
use App\Entity\Texts;
use Exception;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Response;
use Swagger\Annotations as SWG;
use Nelmio\ApiDocBundle\Annotation\Model;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use App\Controller\AbstractController\ESGBUController;

/**
 * Class TextsController
 * @package App\Controller
 * @SWG\Tag(name="Texts")
 */
class TextsController extends ESGBUController
{
    use TextsTrait,
        DataTypesTrait;

    /**
     * Show all texts information.
     * @SWG\Response(
     *     response="200",
     *     description="Array with all texts information.",
     *     @SWG\Schema(type="array",
     *     @SWG\Items(allOf={
     *          @SWG\Schema(ref=@Model(type=Texts::class))},
     *          @SWG\Property(property="dataTypeId", type="integer")))
     * )
     * @SWG\Response(
     *     response="404",
     *     description="No text information found.",
     * )
     * @Rest\Get(
     *      path = "/texts",
     *      name = "app_texts_list"
     * )
     * @Rest\View
     * @return View Array with all texts information.
     */
    public function listAction() : View
    {
        try
        {
            $texts = $this->getAllTexts();
            $formattedTexts = array();
            foreach ($texts as $text)
            {
                array_push($formattedTexts, $this->formatText($text));
            }
            return $this->createView($formattedTexts, Response::HTTP_OK);
        }
        catch (Exception $e)
        {
            return $this->createView($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Create a new text information.
     *
     * @SWG\Response(
     *     response="200",
     *     description="Return text information created.",
     *     @SWG\Schema(type="object", allOf={
     *          @SWG\Schema(ref=@Model(type=Texts::class))},
     *              @SWG\Property(property="dataTypeId", type="integer"))
     * )
     * @SWG\Response(response="404", description="No data type found.")
     * @SWG\Response(response="400", description="Bad value.")
     * @SWG\Response(response="409", description="Data type already have text information.")
     * @SWG\Response(response="403", description="Not authorized.")
     * @SWG\Parameter(name="body", in="body", description="Data type informations.",
     *     @SWG\Schema(type="object", allOf={
     *          @SWG\Schema(ref=@Model(type=Texts::class))},
     *              @SWG\Property(property="dataTypeId", type="integer"))
     * )
     * @Rest\Post(path="/texts", name="app_texts_create")
     * @Rest\RequestParam(name="dataTypeId", requirements="[0-9]*", nullable=false)
     * @Rest\View
     * @ParamConverter("text", converter="fos_rest.request_body")
     * @param Texts $text Text information.
     * @param int $dataTypeId Data type id linked with this information.
     * @param ConstraintViolationListInterface $validationErrors Constraint entity validation.
     * @return View Text information has just been created.
     */
    public function createAction(Texts $text, int $dataTypeId, ConstraintViolationListInterface $validationErrors)
    : View
    {
        try
        {
            $this->checkRights([Role::ADMIN]);
            $this->checkInfosIsValid($text, $validationErrors);
            $this->checkIfAlreadyHasTextInfo($dataTypeId);

            $dataType = $this->getDataTypeById($dataTypeId);
            $text->setDataType($dataType);

            $em = $this->getDoctrine()->getManager();
            $em->persist($text);
            $em->flush();
        }
        catch(Exception $e)
        {
            return $this->createView($e->getMessage(), $e->getCode(), true);
        }
        return $this->createView($this->formatText($text), Response::HTTP_CREATED, true);
    }

    /**
     * Update text information.
     *
     * @SWG\Response(
     *     response="200",
     *     description="Return text information updated.",
     *     @SWG\Schema(type="object", allOf={
     *          @SWG\Schema(ref=@Model(type=Texts::class))},
     *              @SWG\Property(property="dataTypeId", type="integer"))
     * )
     * @SWG\Response(response="404", description="No data type found.")
     * @SWG\Response(response="400", description="Bad value.")
     * @SWG\Response(response="409", description="Data type already have text information.")
     * @SWG\Response(response="403", description="Not authorized.")
     * @SWG\Parameter(name="body", in="body", description="Data type informations.",
     *     @SWG\Schema(type="object", allOf={
     *          @SWG\Schema(ref=@Model(type=Texts::class))},
     *              @SWG\Property(property="dataTypeId", type="integer"))
     * )
     * @Rest\Put(path="/texts/{id}", name="app_texts_update", requirements={"id"="\d+"})
     * @Rest\RequestParam(name="dataTypeId", requirements="[0-9]*", nullable=false)
     * @Rest\View
     * @ParamConverter("text", converter="fos_rest.request_body")
     * @param Texts $text Text information.
     * @param int $id Id of data type which identify text information.
     * @param int $dataTypeId Data type id linked with this information.
     * @param ConstraintViolationListInterface $validationErrors Constraint entity validation.
     * @return View Text information has just been updated.
     */
    public function updateAction(Texts $text, int $id, int $dataTypeId,
                                 ConstraintViolationListInterface $validationErrors) : View
    {
        try
        {
            $this->checkRights([Role::ADMIN]);
            $this->checkInfosIsValid($text, $validationErrors);

            $dataType = $this->getDataTypeById($id);
            $existingText = $this->getTextByDataType($dataType);

            $newDataType = ($dataTypeId) ? $this->getDataTypeById($dataTypeId) : $existingText->getDataType();

            if ($dataTypeId && $dataTypeId != $id)
            {
                $this->checkIfAlreadyHasTextInfo($dataTypeId);
            }

            $existingText->update($text, $newDataType);

            $em = $this->getDoctrine()->getManager();
            $em->flush();
            return $this->createView($this->formatText($existingText), Response::HTTP_CREATED, true);
        }
        catch(Exception $e)
        {
            return $this->createView($e->getMessage(), $e->getCode(), true);
        }
    }

    /**
     * Format text for response.
     * @param Texts|null $text Text doctrine entity to format.
     * @return array|null Array that represents text entity for response.
     */
    private function formatText(?Texts $text): ?array
    {
        if (!$text)
        {
            return null;
        }

        return array(
            'dataTypeId' => $text->getDataType()->getId(),
            'minLength' => $text->getMinLength(),
            'maxLength' => $text->getMaxLength(),
            'regex' => $text->getRegex());
    }


}