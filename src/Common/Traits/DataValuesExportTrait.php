<?php


namespace App\Common\Traits;


use App\Common\Enum\Type;
use App\Entity\AbstractEntity\Administrations;
use App\Entity\DocumentaryStructures;
use App\Entity\PhysicalLibraries;
use App\Entity\PhysicalLibraryDataValues;
use App\Entity\Surveys;
use App\Utils\StringTools;
use DateTime;
use Exception;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Trait DataValuesExportTrait
 * @package App\Common\Traits
 *
 * To export data value list in CSV - for moment, just CSV exists. This class will must be modified in case of new
 * format are asked to integrate in ESGBU.
 */
trait DataValuesExportTrait
{
    /**
     * Load data for documentary structure and this physical libraries or just for one physical library format to
     * make easy translation to CSV format.
     * @param string $entityClass To know which repository to use. (ex: Establishments::class)
     * @param Administrations $administration Administration id of data values.
     * @param string $lang Lang to format value.
     * @return array|null Array with all value adapted for convert to CSV.
     */
    private function loadExportData(string $entityClass, Administrations $administration, string $lang): ?array
    {
        $doctrine = $this->managerRegistry;
        $criteria[self::ADMINISTRATION_CAMEL_CASE[$entityClass]] = $administration;
        $surveys = $this->managerRegistry->getRepository(Surveys::class)->findAll();
        $groups = $this->getAllGroupSortedByParent();
        $physicLibDataType = $this->getAllAdministrationTypeDataTypeOrdered(
            PhysicalLibraries::class, $groups);

        switch ($entityClass)
        {
            case PhysicalLibraries::class:
                $this->updateOperationForAllSurvey(PhysicalLibraries::class, $administration, $surveys);
                $values = $doctrine->getRepository(self::ADMINISTRATION_DATA_VALUE_CLASS[$entityClass])
                    ->getAllValueForExport($administration->getId());
                $values = $this->formatValuesForExport($values, $physicLibDataType, $lang);
                $values['sortOrder'] = $administration->getSortOrder();
                break;

            case DocumentaryStructures::class:
                $this->updateOperationForAllSurvey(DocumentaryStructures::class, $administration, $surveys);
                $values = $doctrine->getRepository(self::ADMINISTRATION_DATA_VALUE_CLASS[$entityClass])
                    ->getAllValueForExport($administration->getId());
                $docStructDataType = $this->getAllAdministrationTypeDataTypeOrdered(
                    DocumentaryStructures::class, $groups);
                $values = $this->formatValuesForExport($values, $docStructDataType, $lang);

                // Associated physical libraries
                $physicalLibraries = $doctrine->getRepository(PhysicalLibraries::class)
                    ->findBy(array('documentaryStructure' => $administration));

                $values['associatedPhysicalLibraries'] = array();
                if (count($physicalLibraries) > 0) {

                    foreach ($physicalLibraries as $physicalLibrary)
                    {
                        $this->updateOperationForAllSurvey(PhysicalLibraries::class, $physicalLibrary, $surveys);
                        $libValues = $doctrine->getRepository(PhysicalLibraryDataValues::class)
                            ->getAllValueForExport($physicalLibrary->getId());
                        $libValues = $this->formatValuesForExport($libValues, $physicLibDataType, $lang);
                        $lib = array('physicLibId' => $physicalLibrary->getId(),
                            'physicLibName' => $physicalLibrary->getUseName(),
                            'physicLibAddress' => $physicalLibrary->getAddress(),
                            'physicLibPostalCode' => $physicalLibrary->getPostalCode(),
                            'physicLibCity' => $physicalLibrary->getCity(),
                            'physicLibSortOrder' => $physicalLibrary->getSortOrder(),
                            'values' => $libValues
                        );
                        array_push($values['associatedPhysicalLibraries'], $lib);
                    }
                }
                break;

        }
        $values['id'] = $administration->getId();
        $values['name'] = $administration->getUseName();
        $values['address'] = $administration->getAddress();
        $values['postalCode'] = $administration->getPostalCode();
        $values['city'] = $administration->getCity();
        return $values;
    }

    /**
     * Format value for make easiest translation to CSV format. Organized by survey, dataType code.
     * @param array $valuesInfos Array in special format that contains all values to insert in CSV.
     * @param array $dataTypeOrdered Array that contains list of dataType. This list is used to sort values in
     *                               result array.
     * @param string $lang Lang to format values.
     * @return array|null Array with all data value ready for create CSV document.
     */
    private function formatValuesForExport(array $valuesInfos, array $dataTypeOrdered, string $lang): ?array
    {
        if (!$valuesInfos)
        {
            return null;
        }
        $result = array();

        $dataTypeArrayTemplate = array();
        $numberDataType = array();
        foreach ($dataTypeOrdered as $dataType)
        {
            $dataTypeArrayTemplate[$dataType->getCode()] = null;
            if ($dataType->getType()->getId() === Type::number || $dataType->getType()->getId() === Type::operation)
            {
                array_push($numberDataType, $dataType->getCode());
            }
        }

        foreach ($valuesInfos as $valueInfo)
        {
            try
            {
                $year = new DateTime($valueInfo['surveyDataCalendarYear']);
                $year = $year->format('Y');
            } catch (Exception $e)
            {
                $year = $valueInfo['surveyDataCalendarYear'];
            }

            if (!isset($result[$year]))
            {
                $result[$year] = $dataTypeArrayTemplate;
            }

            $dataTypeCode = $valueInfo['dataTypeCode'];
            $result[$year][$dataTypeCode] = $this->formatValueForExport(
                $valueInfo['value'], $dataTypeCode, $numberDataType, $lang);
        }
        return $result;
    }

    /**
     * Return CSV view for list data value.
     * @param string $entityClass To know which repository to use. (ex: Establishments::class)
     * @param Administrations $administration Administration entity of value.
     * @param string $format Format to get data.
     * @param string|null $encoding Encoding format
     * @param string $lang Lang to get data.
     * @return View CSV view of data.
     */
    private function exportData(string $entityClass, Administrations $administration, string $format, ?string $encoding,
                                string $lang, ?Surveys $survey): View
    {
        $data['values'] = $this->loadExportData($entityClass, $administration, $lang);
        $data['encoding'] = $encoding;
        $data['lang'] = $lang;
        $data['survey'] = $survey;
        return $this->createView($data, Response::HTTP_OK)->setFormat($format);
    }

    private function formatValueForExport(?string $value, string $dataTypeCode, array $numberDataType,
                                          string $lang = self::DEFAULT_LANG): ?string
    {
        if ($value == null)
        {
            return null;
        }
        else if (in_array($dataTypeCode, $numberDataType))
        {
            return StringTools::numberToLocale($value, $lang);
        }
        else
        {
            return '"' . StringTools::stringBooltoInt($value) . '"';
        }
    }
}
