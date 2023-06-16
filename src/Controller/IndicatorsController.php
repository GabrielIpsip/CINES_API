<?php


namespace App\Controller;


use App\Common\Enum\Role;
use App\Common\Traits\IndicatorsTrait;
use App\Common\Traits\SurveysTrait;
use App\Controller\AbstractController\ESGBUController;
use App\Utils\StringTools;
use Exception;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Swagger\Annotations as SWG;
use App\Entity\Indicators;
use Nelmio\ApiDocBundle\Annotation\Model;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class IndicatorsController
 * @package App\Controller
 * @SWG\Tag(name="Indicators")
 */
class IndicatorsController extends ESGBUController
{

    use IndicatorsTrait,
        SurveysTrait;

    private const TABLE_NAME = 'indicators';

    /**
     * @var array
     */
    private $dataCalendarYearPublished;

    /** Get indicator information and their values.
     * @SWG\Response(
     *     response="200",
     *     description="Return indicator value.",
     *     @SWG\Items(allOf={
     *        @SWG\Schema(ref=@Model(type=Indicators::class))},
     *        @SWG\Property(property="name", type="string"),
     *        @SWG\Property(property="description", type="string"),
     *        @SWG\Property(property="result", type="object"),
     *        @SWG\Property(property="query", type="object")))
     * )
     * @SWG\Response(
     *     response="404",
     *     description="No indicator found.",
     * )
     * @SWG\Parameter(name="id",type="integer", in="path", description="Indicator id.")
     * @Rest\Get(
     *      path = "/public/indicators/{id}",
     *      name = "app_public_indicators_show",
     *      requirements = {"id"="\d+"}
     * )
     * @Rest\QueryParam(name="result", strict=true, requirements="true|false", default="false")
     * @Rest\QueryParam(name="year", requirements="\d{4}")
     * @Rest\QueryParam(name="lang", requirements="[a-z]{2}", default=ESGBUController::DEFAULT_LANG)
     * @param int $id
     * @param string $result
     * @param string $year
     * @param string $lang
     * @return View
     */
    public function showAction(int $id, string $result, string $year, string $lang): View
    {
        try
        {
            $result = StringTools::stringToBool($result);
            $indicator = $this->getIndicatorById($id);

            return $this->createView(
                $this->formatIndicator($indicator, $lang, $result, $year, true, true),
                Response::HTTP_OK);

        }
        catch (Exception $e)
        {
            return $this->createView($e->getMessage(), $e->getCode());
        }
    }

    /** Get list of indicator information and their values.
     * @SWG\Response(
     *     response="200",
     *     description="Return list of indicator value.",
     *     @SWG\Schema(type="array", @SWG\Items(allOf={
     *        @SWG\Schema(ref=@Model(type=Indicators::class))},
     *        @SWG\Property(property="name", type="string"),
     *        @SWG\Property(property="description", type="string"),
     *        @SWG\Property(property="result", type="object"),
     *        @SWG\Property(property="query", type="object")))
     * )
     * @SWG\Response(
     *     response="404",
     *     description="No indicator found.",
     * )
     * @Rest\Get(
     *      path = "/public/indicators",
     *      name = "app_public_indicators_list"
     * )
     * @Rest\QueryParam(name="result", strict=true, requirements="true|false", default="false")
     * @Rest\QueryParam(name="withQuery", strict=true, requirements="true|false", default="false")
     * @Rest\QueryParam(name="year", requirements="\d{4}")
     * @Rest\QueryParam(name="lang", requirements="[a-z]{2}", default=ESGBUController::DEFAULT_LANG)
     * @param string $result
     * @param string $year
     * @param string $lang
     * @param string $withQuery
     * @return View
     */
    public function listAction(string $result, string $year, string $lang, string $withQuery): View
    {
        try
        {
            $result = StringTools::stringToBool($result);
            $withQuery = StringTools::stringToBool($withQuery);

            $isDistrd = $this->checkRightsBool([Role::ADMIN, Role::ADMIN_RO]);
            $indicators = $this->getAllIndicator($result, !$isDistrd);

            foreach ($indicators as &$indicator)
            {
                $indicator = $this->formatIndicator($indicator, $lang, $result, $year, $withQuery);
            }

            return $this->createView($indicators, Response::HTTP_OK);
        }
        catch (Exception $e)
        {
            return $this->createView($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Create new indicator.
     * @SWG\Response(
     *     response="201",
     *     description="Create an indicator.",
     *     @SWG\Items(allOf={
     *        @SWG\Schema(ref=@Model(type=Indicators::class))},
     *        @SWG\Property(property="name", type="string"),
     *        @SWG\Property(property="description", type="string"),
     *        @SWG\Property(property="query", type="object")))
     * )
     * @SWG\Response(response="404", description="No lang found.")
     * @SWG\Response(response="403", description="Not authorized.")
     * @SWG\Parameter(name="body", in="body", description="Indicator informations.",
     *     @SWG\Schema(type="object",
     *      @SWG\Property(property="query", type="object"),
     *      @SWG\Property(property="byEstablishment", type="boolean"),
     *      @SWG\Property(property="byDocStruct", type="boolean"),
     *      @SWG\Property(property="byRegion", type="boolean"),
     *      @SWG\Property(property="global", type="boolean"),
     *      @SWG\Property(property="keyFigure", type="boolean"),
     *      @SWG\Property(property="active", type="boolean"),
     *      @SWG\Property(property="displayOrder", type="integer"),
     *      @SWG\Property(property="administrator", type="boolean"),
     *      @SWG\Property(property="prefix", type="string"),
     *      @SWG\Property(property="suffix", type="string"),
     *      @SWG\Property(property="names", type="array",
     *          @SWG\Items(type="object",
     *              @SWG\Property(property="lang", type="string"),
     *              @SWG\Property(property="value", type="string"))),
     *      @SWG\Property(property="descriptions", type="array",
     *          @SWG\Items(type="object",
     *              @SWG\Property(property="lang", type="string"),
     *              @SWG\Property(property="value", type="string"))))
     * )
     * @Rest\Post(path="/indicators", name="app_indicators_create")
     * @ParamConverter("indicator", converter="fos_rest.request_body")
     * @Rest\RequestParam(name="names", nullable=false)
     * @Rest\RequestParam(name="descriptions", nullable=true)
     * @Rest\QueryParam(name="lang", requirements="[a-z]{2}", default=ESGBUController::DEFAULT_LANG)
     * @Rest\View
     * @param Indicators $indicator
     * @param array $names
     * @param array|null $descriptions
     * @param string $lang Language code.
     * @return View Groups has just been created.
     */
    public function createAction(Indicators $indicator, array $names, ?array $descriptions, string $lang): View
    {
        try
        {
            $this->checkRights([Role::ADMIN]);
            $name = $this->addTranslation($names, self::TABLE_NAME);
            $descriptions = $this->addTranslation($descriptions, self::TABLE_NAME);
            $indicator->setName($name);
            $indicator->setDescription($descriptions);

            $em = $this->getDoctrine()->getManager();
            $em->persist($indicator);
            $em->flush();

            $this->createDisplayOrderForEachIndicator($indicator);
            $em->flush();

            return $this->createView(
                $this->formatIndicator($indicator, $lang, false),
                Response::HTTP_CREATED, true);
        }
        catch (Exception $e)
        {
            return $this->createView($e->getMessage(), $e->getCode(), true);
        }
    }

    /**
     * Update an indicator.
     * @SWG\Response(
     *     response="200",
     *     description="Update an indicator selected by id.",
     *     @SWG\Items(allOf={
     *        @SWG\Schema(ref=@Model(type=Indicators::class))},
     *        @SWG\Property(property="name", type="string"),
     *        @SWG\Property(property="description", type="string"),
     *        @SWG\Property(property="query", type="object")))
     * )
     * @SWG\Response(response="404", description="Data missing in database")
     * @SWG\Response(response="403", description="Not authorized.")
     * @SWG\Parameter(name="id", type="integer", in="path", description="Indicator id to update.")
     * @SWG\Parameter(name="body", in="body", description="Indicator informations.",
     *     @SWG\Schema(type="object",
     *      @SWG\Property(property="query", type="object"),
     *      @SWG\Property(property="byEstablishment", type="boolean"),
     *      @SWG\Property(property="byDocStruct", type="boolean"),
     *      @SWG\Property(property="byRegion", type="boolean"),
     *      @SWG\Property(property="global", type="boolean"),
     *      @SWG\Property(property="keyFigure", type="boolean"),
     *      @SWG\Property(property="active", type="boolean"),
     *      @SWG\Property(property="displayOrder", type="integer"),
     *      @SWG\Property(property="administrator", type="boolean"),
     *      @SWG\Property(property="prefix", type="string"),
     *      @SWG\Property(property="suffix", type="string"),
     *      @SWG\Property(property="names", type="array",
     *          @SWG\Items(type="object",
     *              @SWG\Property(property="lang", type="string"),
     *              @SWG\Property(property="value", type="string"))),
     *      @SWG\Property(property="descriptions", type="array",
     *          @SWG\Items(type="object",
     *              @SWG\Property(property="lang", type="string"),
     *              @SWG\Property(property="value", type="string"))))
     * )
     * @Rest\Put(
     *     path="/indicators/{id}",
     *     name="app_indicators_update",
     *     requirements={"id"="\d+"}
     * )
     * @ParamConverter("indicator", converter="fos_rest.request_body")
     * @Rest\RequestParam(name="names", nullable=false)
     * @Rest\RequestParam(name="descriptions", nullable=true)
     * @Rest\QueryParam(name="lang", requirements="[a-z]{2}", default=ESGBUController::DEFAULT_LANG)
     * @Rest\QueryParam(name="result", strict=true, requirements="true|false", default="false")
     * @Rest\View
     * @param int $id
     * @param Indicators $indicator
     * @param array $names
     * @param array|null $descriptions
     * @param string $lang Language code.
     * @param string $result
     * @return View Groups has just been updated.
     */
    public function updateAction(int $id, Indicators $indicator, array $names, ?array $descriptions, string $lang,
                                 string $result): View
    {
        try
        {
            $this->checkRights([Role::ADMIN]);
            $result = StringTools::stringToBool($result);
            $existingIndicator = $this->getIndicatorById($id);

            $this->updateTranslation($names, $existingIndicator->getName(), self::TABLE_NAME);
            if ($descriptions)
            {
                $this->updateOrCreateDescription($existingIndicator, $descriptions);
            }

            $this->updateDisplayOrderForEachIndicator($indicator, $existingIndicator);
            $existingIndicator->update($indicator);

            $this->getDoctrine()->getManager()->flush();

            return $this->createView(
                $this->formatIndicator($existingIndicator, $lang, $result, null, true,
                    true),
                Response::HTTP_OK, true);
        }
        catch (Exception $e)
        {
            return $this->createView($e->getMessage(), $e->getCode(), true);
        }
    }

    /**
     * Format indicator for http response.
     * @param Indicators $indicator Indicator doctrine entity to format.
     * @param string $lang Lang of response.
     * @param bool $result True to show result, else false.
     * @param string|null $year Add year with 'y' format to show just one year.
     * @param bool $withQuery Show query and full query in response.
     * @param bool $cleanCache Clean result cache to update result value.
     * @return array Array that represents formatted doctrine entity.
     * @throws Exception 404 : Not survey found.
     */
    private function formatIndicator(Indicators $indicator, string $lang, bool $result,
                                     string $year = null, bool $withQuery = true, bool $cleanCache = false): array
    {
        if ($cleanCache)
        {
            $this->cleanIndicatorCache([$indicator]);
        }

        $name = $this->getTranslation($lang, $indicator->getName());
        $description = $this->getTranslation($lang, $indicator->getDescription());

        $response = [
            'id' => $indicator->getId(),
            'name' => $name,
            'description' => $description,
            'byEstablishment' => $indicator->getByEstablishment(),
            'byDocStruct' => $indicator->getByDocStruct(),
            'byRegion' => $indicator->getByRegion(),
            'global' => $indicator->getGlobal(),
            'keyFigure' => $indicator->getKeyFigure(),
            'active' => $indicator->getActive(),
            'displayOrder' => $indicator->getDisplayOrder(),
            'administrator' => $indicator->getAdministrator(),
            'prefix' => $indicator->getPrefix(),
            'suffix' => $indicator->getSuffix()
        ];

        if ($withQuery)
        {
            $response['query'] =  $indicator->getQuery();
        }

        if ($result)
        {
            if ($year)
            {
                $years = [$year];
            }
            else
            {
                $years = $this->getAllDataCalendarYearPublished();
            }
            $response['result'] = $this->getResults($indicator, $years);
        }

        return $response;
    }

}
