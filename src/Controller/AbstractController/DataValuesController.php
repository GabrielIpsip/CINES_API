<?php


namespace App\Controller\AbstractController;

use App\Common\Enum\State;
use App\Common\Interfaces\IAdministrations;
use App\Common\Interfaces\ITypes;
use App\Common\Traits\AdministrationGroupLocksTrait;
use App\Common\Traits\AdministrationsTrait;
use App\Common\Traits\DataValuesExportTrait;
use App\Common\Traits\DataTypesTrait;
use App\Common\Traits\DataValuesTrait;
use App\Common\Traits\GroupsTrait;
use App\Common\Traits\SurveysTrait;
use App\Common\Traits\SurveyValidationsTrait;
use App\Common\Enum\Role;
use App\Entity\AbstractEntity\AdministrationDataValues;
use App\Entity\AbstractEntity\Administrations;
use App\Entity\DataTypes;
use App\Entity\DocumentaryStructures;
use App\Entity\Establishments;
use App\Entity\PhysicalLibraries;
use App\Entity\Surveys;
use Exception;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\Response;


abstract class DataValuesController extends ESGBUController implements ITypes, IAdministrations
{

    use DataValuesTrait,
        DataTypesTrait,
        SurveysTrait,
        AdministrationsTrait,
        DataValuesExportTrait,
        GroupsTrait,
        SurveyValidationsTrait,
        AdministrationGroupLocksTrait;

    // Common action part //////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Common function to list data value. Choose administration type with $entityClass parameter.
     * @param string $entityClass To know which repository to use. (ex: Establishments::class)
     * @param int|null $surveyId Optional : to get values just for this survey.
     * @param int|null $administrationId Optional : to get value just for this administration.
     * @param string|null $type Optional : type name, to get value just with this type.
     * @param string|null $format Optional : to convert response in other format, like CSV.
     * @param string|null $encoding Optional : To choose encoding of response.
     * @param string|null $lang Optional : To choose response language.
     * @return View
     */
    protected function commonListAction(string $entityClass, ?int $surveyId, ?int $administrationId, ?string $type,
                                        ?string $format, ?string $encoding, ?string $lang)
    : View
    {
        $survey = null;
        $administration = null;
        $criteria = array();
        $doctrine = $this->getDoctrine();
        $format = strtolower($format);
        $encoding = strtoupper($encoding);

        try
        {
            if ($surveyId)
            {
                $survey = $this->getSurveyById($surveyId);
                $criteria['survey'] = $survey;
            }

            if ($administrationId)
            {
                $administration = $this->getAdministrationById($entityClass, $administrationId);
                $criteria[self::ADMINISTRATION_CAMEL_CASE[$entityClass]] = $administration;
            }

            $this->checkDataValuesRightsListAction($entityClass, $administration);

            if ($format)
            {
                return $this->exportData($entityClass, $administration, $format, $encoding, $lang, $survey);
            }

            if ($type)
            {
                $typeEntity = $this->getTypeByName($type);
                $values = $doctrine->getRepository(self::ADMINISTRATION_DATA_VALUE_CLASS[$entityClass])
                    ->findByTypeValue($administration, $survey, $typeEntity);
            }
            else
            {
                $values = $doctrine->getRepository(self::ADMINISTRATION_DATA_VALUE_CLASS[$entityClass])
                    ->findBy($criteria);
            }

            if (count($values) === 0)
            {
                return $this->createView('No value between ' .
                    self::ADMINISTRATION_NAME[$entityClass] . ' and survey found.',
                    Response::HTTP_NOT_FOUND);
            }

            foreach ($values as &$value)
            {
                $value = $this->formatValueForResponse($entityClass, $value);
            }

            return $this->createView($values, Response::HTTP_OK);
        }
        catch(Exception $e)
        {
            return $this->createView($e->getMessage(), $e->getCode());
        }
    }


    /**
     * Common function to insert data value. Choose administration type with $entityClass parameter.
     * @param string $entityClass To know which repository to use. (ex: Establishments::class)
     * @param string $value Value to insert for this dataTypeValue.
     * @param int $surveyId Id of survey of new value.
     * @param int $dataTypeId Id of dataType of new value.
     * @param int $administrationId Id of administration of new value.
     * @return View
     */
    protected function commonInsertAction(string $entityClass, string $value, int $surveyId, int $dataTypeId,
                                          int $administrationId) : View
    {
        try
        {
            $dataType = $this->getDataTypeById($dataTypeId);

            if ($this->isOperationType($dataType))
            {
                return $this->createView("Can't set value for operation type.", Response::HTTP_BAD_REQUEST,
                    true);
            }

            $survey = $this->getSurveyById($surveyId);
            $administration = $this->getAdministrationById($entityClass, $administrationId);

            $this->checkModifyRights($entityClass, $administration, $dataType, $survey);
            $this->checkIfValidateSurveyForAdministration($entityClass, $administration, $survey);
            $this->lockResource($entityClass, $dataType, $survey, $administration);
        }
        catch(Exception $e)
        {
            return $this->createView($e->getMessage(), $e->getCode(), true);
        }

        $value = trim($value);
        $valueInfoArray = $this->updateOrInsertValue($entityClass, $survey, $dataType, $administration, $value);
        $this->managerRegistry->getManager()->flush();

        $this->updateOperationValues($entityClass, $survey, $administration);

        return $this->createView($this->formatValueForResponse($entityClass, $valueInfoArray),
            Response::HTTP_CREATED, true);
    }

    /**
     * Common function to delete data value. Choose administration type with $entityClass parameter.
     * @param string $entityClass To know which repository to use. (ex: Establishments::class)
     * @param int $surveyId Id of survey of value to delete.
     * @param int $administrationId Id of administration of value to delete.
     * @param int $dataTypeId Id of dataType of value to delete.
     * @return View
     */
    protected function commonDeleteAction(string $entityClass, int $surveyId, int $administrationId, int $dataTypeId)
    : View
    {
        $em = $this->managerRegistry->getManager();

        try
        {
            $survey = $this->getSurveyById($surveyId);
            $administration = $this->getAdministrationById($entityClass, $administrationId);
            $dataType = $this->getDataTypeById($dataTypeId);

            $this->checkModifyRights($entityClass, $administration, $dataType, $survey);
            $this->checkIfValidateSurveyForAdministration($entityClass, $administration, $survey);
            $this->lockResource($entityClass, $dataType, $survey, $administration);

            $value = $this->getDataValue($entityClass, $survey, $administration, $dataType);

            $em->remove($value);
            $em->flush();

            $this->updateOperationValues($entityClass, $survey, $administration);

            return $this->createView(ucfirst(self::ADMINISTRATION_NAME[$entityClass]) . ' data value deleted.',
                Response::HTTP_NO_CONTENT, true);
        }
        catch(Exception $e)
        {
            return $this->createView($e->getMessage(), $e->getCode(), true);
        }
    }

    // Private function part ///////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Format administrationDataValues doctrine entity for the response.
     * @param string $entityClass To know which repository to use. (ex: Establishments::class)
     * @param AdministrationDataValues $value Doctrine entity of administrationDataValue.
     * @return array Return an array that represent value information formatted for response.
     */
    private function formatValueForResponse(string $entityClass, AdministrationDataValues $value): array
    {
        $result = array();
        $result[self::ADMINISTRATION_ID_NAME[$entityClass]] = $value->getAdministration()->getId();
        $result["surveyId"] = $value->getSurvey()->getId();
        $result["dataTypeId"] = $value->getDataType()->getId();
        $result["value"] = $value->getValue();
        
        return $result;
    }

    /**
     * Check if current user get good right for modify data values for survey state.
     * @param string $entityClass To know which repository to use. (ex: Establishments::class)
     * @param Administrations $administration Administration entity on which we want to verify the rights.
     * @param DataTypes $dataType DataType entity to check if administrator data type.
     * @param Surveys $survey Survey entity to check if survey is open.
     * @throws Exception 403 : Not authorized.
     */
    private function checkModifyRights(string $entityClass, Administrations $administration, DataTypes $dataType,
                                       Surveys $survey)
    {
        switch ($survey->getState()->getId()) {
            case State::CLOSE:
            case State::PUBLISHED:
            case State::CREATED:
                $this->checkRights([Role::ADMIN]);
                break;

            case State::OPEN:
                $this->checkDataValuesRightsModifyAction($entityClass, $administration, $dataType);
                break;

        }
    }

    /**
     * Check if current user get good right for list data values.
     * @param string $entityClass To know which repository to use. (ex: Establishments::class)
     * @param Administrations|null $administrations Optional : Check current user right for this administration.
     * @throws Exception 403 : Not authorized.
     */
    private function checkDataValuesRightsListAction(string $entityClass, ?Administrations $administrations)
    {
        if (!$administrations) {
            $this->checkRights([Role::ADMIN, Role::ADMIN_RO]);
            return;
        }

        $isAdmin = $this->checkRightsBool([Role::ADMIN, Role::ADMIN_RO, Role::SURVEY_ADMIN, Role::VALID_SURVEY_RESP],
        null, null, null, false);

        if ($isAdmin) {
            return;
        }

        switch ($entityClass)
        {
            case  Establishments::class:
                $this->checkRights([Role::USER], null, $administrations);
                break;
            case DocumentaryStructures::class:
                $this->checkRights([Role::USER], $administrations);
                break;
            case PhysicalLibraries::class:
                $this->checkRights([Role::USER], null, null, $administrations);
                break;
        }
    }

    /**
     * Check if current user get good right for modify data values.
     * @param string $entityClass To know which repository to use. (ex: Establishments::class)
     * @param Administrations|null $administrations Optional : Check current user right for this administration.
     * @param DataTypes $dataTypes DataType of dataValue to check if is administrator dataType.
     * @throws Exception 403 : Not authorized.
     */
    private function checkDataValuesRightsModifyAction(string $entityClass, Administrations $administrations,
                                                       DataTypes $dataTypes)
    {
        $roles = array();
        if ($dataTypes->getAdministrator())
        {
            array_push($roles , Role::ADMIN);
        }

        switch ($entityClass)
        {
            case  Establishments::class:
                if (count($roles) === 0)
                {
                    array_push($roles, Role::ADMIN, Role::SURVEY_ADMIN, Role::VALID_SURVEY_RESP);
                }
                $this->checkRights($roles, null, $administrations);
                break;

            case DocumentaryStructures::class:
                if (count($roles) === 0)
                {
                    array_push($roles, Role::ADMIN, Role::SURVEY_ADMIN, Role::VALID_SURVEY_RESP,
                        Role::USER);
                }
                $this->checkRights($roles, $administrations);
                break;

            case PhysicalLibraries::class:
                if (count($roles) === 0)
                {
                    array_push($roles, Role::ADMIN, Role::SURVEY_ADMIN, Role::VALID_SURVEY_RESP,
                        Role::USER);
                }
                $this->checkRights($roles, null, null, $administrations);
                break;
        }
    }
}
