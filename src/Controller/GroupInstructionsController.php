<?php


namespace App\Controller;


use App\Common\Enum\Role;
use App\Common\Traits\GroupInstructionsTrait;
use App\Common\Traits\GroupsTrait;
use App\Common\Traits\SurveysTrait;
use App\Entity\GroupInstructions;
use Exception;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\Response;
use Swagger\Annotations as SWG;
use App\Controller\AbstractController\ESGBUController;

/**
 * Class GroupInstructionController
 * @package App\Controller
 * @SWG\Tag(name="Group instructions")
 */
class GroupInstructionsController extends ESGBUController
{

    use GroupsTrait,
        SurveysTrait,
        GroupInstructionsTrait;

    private const TABLE_NAME = 'group-instructions';


    /** Show group instruction by survey id and group id.
     * @SWG\Response(
     *     response="200",
     *     description="Return group instruction by survey id and group id.",
     *     @SWG\Items(
     *     @SWG\Property(property="title", type="string"),
     *     @SWG\Property(property="groupId", type="integer"),
     *     @SWG\Property(property="surveyId", type="integer"))
     * )
     * @SWG\Parameter(name="lang",type="string", in="query", description="Language code.")
     * @SWG\Response(
     *     response="404",
     *     description="No survey, group or group instruction found.",
     * )
     * @SWG\Parameter(name="groupId",type="integer", in="path", description="Group id.")
     * @SWG\Parameter(name="surveyId",type="integer", in="path", description="Survey id.")
     * @Rest\QueryParam(name="lang", requirements="[a-z]{2}", default=ESGBUController::DEFAULT_LANG)
     * @Rest\Get(
     *      path = "/group-instructions/{groupId}/{surveyId}",
     *      name = "app_group_instructions_show",
     *      requirements = {"groupId"="\d+", "surveyId"="\d+"}
     * )
     * @Rest\View
     * @param int $groupId
     * @param $surveyId
     * @param string $lang Code to choose title lang.
     * @return View Group instruction.
     */
    public function showAction(int $groupId, $surveyId, string $lang) : View
    {
        try
        {
            $group = $this->getGroupById($groupId);
            $survey = $this->getSurveyById($surveyId);
            $instruction = $this->getGroupInstructionBySurveyAndGroup($survey, $group);

            return $this->createView(
                $this->getFormattedInstructionForResponse($instruction, $lang), Response::HTTP_OK);
        }
        catch (Exception $e)
        {
            return $this->createView($e->getMessage(), $e->getCode());
        }
    }


    /**
     * Create new group instruction.
     * @SWG\Response(
     *     response="201",
     *     description="Create a group instruction.",
     *     @SWG\Items(
     *     @SWG\Property(property="title", type="string"),
     *     @SWG\Property(property="groupId", type="integer"),
     *     @SWG\Property(property="surveyId", type="integer"))
     * )
     * @SWG\Response(response="404", description="No group, survey or lang found.")
     * @SWG\Response(response="400", description="Bad request. Body not valid.")
     * @SWG\Response(response="403", description="Not authorized.")
     * @SWG\Parameter(name="body", in="body", description="Group informations.",
     *     @SWG\Schema(type="object",
     *     @SWG\Property(property="groupId", type="integer"),
     *     @SWG\Property(property="surveyId", type="integer"),
     *     @SWG\Property(property="instructions", type="array",
     *     @SWG\Items(type="object",
     *      @SWG\Property(property="lang", type="string"),
     *      @SWG\Property(property="value", type="string"))))
     * )
     * @Rest\Post(path="/group-instructions", name="app_group_instruction_create")
     * @Rest\RequestParam(name="groupId", requirements="\d+", nullable=false)
     * @Rest\RequestParam(name="surveyId", requirements="\d+", nullable=false)
     * @Rest\RequestParam(name="instructions", nullable=false)
     * @Rest\QueryParam(name="lang", requirements="[a-z]{2}", default=ESGBUController::DEFAULT_LANG)
     * @Rest\View
     * @param array $instructions Instruction for this group and this survey.
     * @param int $groupId Id of group linked with this instruction.
     * @param int $surveyId Id of survey linked with this instruction.
     * @param string $lang Code to choose title lang.
     * @return View Group instruction has just been created.
     */
    public function createAction(array $instructions, int $groupId, int $surveyId, string $lang) : View
    {
        try
        {
            $this->checkRights([Role::ADMIN]);

            $em = $this->managerRegistry->getManager();

            $group = $this->getGroupById($groupId);
            $survey = $this->getSurveyById($surveyId);

            $content = $this->addTranslation($instructions, self::TABLE_NAME);

            $newGroupInstruction = new GroupInstructions($group, $content, $survey);
            $em->persist($newGroupInstruction);
            $em->flush();

            return $this->createView($this->getFormattedInstructionForResponse($newGroupInstruction, $lang),
                Response::HTTP_CREATED, true);
        }
        catch(Exception $e)
        {
            return $this->createView($e->getMessage(), $e->getCode(), true);
        }
    }

    /**
     * Update a instruction for a group.
     * @SWG\Response(
     *     response="200",
     *     description="Update a instruction selected by id.",
     *     @SWG\Items(
     *     @SWG\Property(property="title", type="string"),
     *     @SWG\Property(property="groupId", type="integer"),
     *     @SWG\Property(property="surveyId", type="integer"))
     * )
     * @SWG\Response(response="404", description="No group or survey found")
     * @SWG\Response(response="400", description="Error to update instruction group. Body not valid.")
     * @SWG\Response(response="403", description="Not authorized.")
     * @SWG\Parameter(name="groupId", type="integer", in="path", description="Group id of instruction.")
     * @SWG\Parameter(name="surveyId", type="integer", in="path", description="Survey id of instruction.")
     * @SWG\Parameter(name="body", in="body", description="Group instruction.",
     *     @SWG\Schema(type="object",
     *     @SWG\Property(property="instructions", type="array",
     *     @SWG\Items(type="object",
     *      @SWG\Property(property="lang", type="string"),
     *      @SWG\Property(property="value", type="string"))))
     * )
     * @Rest\Put(
     *     path="/group-instructions/{groupId}/{surveyId}",
     *     name="app_group_instruction_update",
     *     requirements={"groupId"="\d+", "surveyId"="\d+"}
     * )
     * @Rest\RequestParam(name="instructions", nullable=false)
     * @Rest\QueryParam(name="lang", requirements="[a-z]{2}", default=ESGBUController::DEFAULT_LANG)
     * @Rest\View
     * @param int $groupId Id of group linked to instruction.
     * @param int $surveyId Id of survey linked to instruction.
     * @param array $instructions Array contains all new instructions for each lang.
     * @param string $lang Code to choose title lang.
     * @return View Groups has just been updated.
     */
    public function updateAction(int $groupId, int $surveyId, array $instructions, string $lang) : View
    {
        try
        {
            $this->checkRights([Role::ADMIN]);

            $group = $this->getGroupById($groupId);
            $survey = $this->getSurveyById($surveyId);

            $groupInstruction = $this->getGroupInstructionBySurveyAndGroup($survey, $group);

            $this->updateTranslation($instructions, $groupInstruction->getInstruction(), self::TABLE_NAME);

            return $this->createView(
                $this->getFormattedInstructionForResponse($groupInstruction, $lang),
                Response::HTTP_OK, true);
        }
        catch(Exception $e)
        {
            return $this->createView($e->getMessage(), $e->getCode(), true);
        }
    }

    /**
     * Get array representation of group instruction for response.
     * @param GroupInstructions|null $groupInstruction Group instruction entity.
     * @param string $lang Lang for the response.
     * @return array|null Group instruction array representation for the response.
     */
    private function getFormattedInstructionForResponse(?GroupInstructions $groupInstruction, string $lang): ?array
    {
        if (!$groupInstruction) {
            return null;
        }

        $instruction = $this->getTranslation($lang, $groupInstruction->getInstruction());

        return array(
            'instruction' => $instruction,
            'surveyId' => $groupInstruction->getSurvey()->getId(),
            'groupId' => $groupInstruction->getGroup()->getId()
        );
    }

}