<?php


namespace App\Utils;


use App\Common\Enum\AdministrationTypeStr;
use App\Common\Enum\Type;
use App\Common\Traits\SurveysTrait;
use App\Controller\AbstractController\ESGBUController;
use App\Controller\DataTypesController;
use App\Controller\GroupsController;
use App\Entity\SurveyDataTypes;
use App\Entity\Surveys;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandler;
use NumberFormatter;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Process\Process;

class PdfViewHandler
{

    use SurveysTrait;

    const TEMPLATE_DIR = '../templates/pdf-export/';
    const IMG_FOLDER = 'img';
    const LATEX_EXT = '.tex';
    const PDF_EXT = '.pdf';
    const TEMP_DIR = 'pdf_export';

    const ADMINISTRATION_NAME = '$ADMINISTRATION_NAME$';
    const SURVEY_NAME = '$SURVEY_NAME$';
    const BODY = '$BODY$';

    const SECTIONS = ['section', 'subsection', 'subsubsection', 'subsubsection', 'subparagraph'];

    const ASSOCIATED_LIB_KEY = 'associatedPhysicalLibraries';
    const LIB_VALUES_KEY = 'values';

    const LATEX_SPECIAL_CHAR = [
        '#' =>'\#',
        '$' =>'\$',
        '%' =>'\%',
        '&' =>'\&',
        '~' =>'\textasciitilde{}',
        '_' =>'\_',
        '^' =>'\textasciicircum{}',
        '\\' =>'\textbackslash{}',
        '{' =>'\{',
        '}' =>'\}',
    ];

    /**
     * @var ManagerRegistry
     */
    private $managerRegistry;

    /**
     * @var DataTypesController
     */
    private $dataTypesController;

    /**
     * @var GroupsController
     */
    private $groupsController;


    public function __construct(ManagerRegistry $managerRegistry, DataTypesController $dataTypesController,
                                GroupsController $groupsController)
    {
        $this->managerRegistry = $managerRegistry;
        $this->dataTypesController = $dataTypesController;
        $this->groupsController = $groupsController;
    }

    public function createResponse(ViewHandler $handler, View $view, Request $request, $format): Response
    {
        try
        {
            $data = $view->getData();
            $values = $data['values'];
            $lang = $data['lang'];
            $survey = $data['survey'];

            $administrationType = array_key_exists(self::ASSOCIATED_LIB_KEY, $values)
                ? AdministrationTypeStr::documentaryStructure
                : AdministrationTypeStr::physicalLibrary;

            $indexedDataTypes = $this->getAllDataTypeInformationIndexedByCode($lang);
            $indexedGroups = $this->getAllGroupsInformationIndexedById($administrationType, $lang);

            $lastActiveSurvey1 = $survey ?: $this->getLastActiveSurvey();
            $yearsToUse = $this->getYearsToUse($lastActiveSurvey1, $values);

            $lastActiveSurvey2 = $yearsToUse[1] ? $this->managerRegistry->getRepository(Surveys::class)
                ->getByDataCalendarYear($yearsToUse[1]) : null;
            $lastActiveSurvey3 = $yearsToUse[2] ? $this->managerRegistry->getRepository(Surveys::class)
                ->getByDataCalendarYear($yearsToUse[2]) : null;

            $this->filterDisableValue($values, $yearsToUse, $lastActiveSurvey1, $lastActiveSurvey2,
                $lastActiveSurvey3, $administrationType);
            $latexDocument = $this->getTemplate($values, $lastActiveSurvey1->getName(), $lang);

            $body = '';
            $this->addSection($body, $indexedGroups);
            $this->addTable($body, $indexedDataTypes, $values, $yearsToUse, $lang);

            if ($administrationType === AdministrationTypeStr::documentaryStructure)
            {
                $indexedPhysicLibGroup = $this->getAllGroupsInformationIndexedById(
                    AdministrationTypeStr::physicalLibrary, $lang);
                $this->addLibSectionAndTable($body, $values[self::ASSOCIATED_LIB_KEY], $indexedPhysicLibGroup,
                    $indexedDataTypes, $yearsToUse, $lang);
            }

            $latexDocument = str_replace(self::BODY, $body, $latexDocument);

            $fileInfo = $this->compileLatexDocument($latexDocument, $data['values']['name'],
                $lastActiveSurvey1->getName());
            $view->setHeader('Content-type', 'application/pdf');
            $view->setHeader('Content-Disposition' ,"attachment; filename=$fileInfo[1]");
            $view->setHeader('Pragma', 'no-cache');
            return new BinaryFileResponse($fileInfo[0] . $fileInfo[1], Response::HTTP_OK, $view->getHeaders());
        }
        catch(Exception $e)
        {
            Return new Response($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * @throws Exception
     */
    private function compileLatexDocument(string $fileContent, string $administrationName, string $surveyName): array
    {
        $directory = TempDirTools::getTempDir(self::TEMP_DIR);
        $fileName = StringTools::replaceWhiteCharByUnderscore($surveyName . '-' . $administrationName);
        $inputFileName = StringTools::sanitizeFileName($fileName . self::LATEX_EXT);
        $outputFileName = StringTools::sanitizeFileName($fileName . self::PDF_EXT);


        file_put_contents($directory . '/' . $inputFileName, $fileContent);
        symlink('../../../templates/pdf-export/img', $directory . '/' . self::IMG_FOLDER);

        $pdfLatex = $_ENV['PDFLATEX_PATH'];
        $compilationProcess = new Process([$pdfLatex, $inputFileName], $directory);
        $compilationProcess->mustRun();
        $compilationProcess->mustRun(); // Must be launch twice, to add page number and img.
        $compilationProcess->mustRun(); // Three times to add correct total of pages.
        return [$directory, $outputFileName];
    }

    private function addLibSectionAndTable(string &$body, array $associatedPhysicLib, array $indexedPhysicLibGroup,
                                           array $indexedDataTypes, array $yearsToUse, string $lang)
    {
        if (count($associatedPhysicLib) === 0)
        {
            return;
        }

        $physicLibSection = '';
        $this->addSection($physicLibSection, $indexedPhysicLibGroup, null, 1);

        foreach ($associatedPhysicLib as $physicLibInfoAndValues)
        {
            $physicLibName = $physicLibInfoAndValues['physicLibName'];
            $values = $physicLibInfoAndValues['values'];

            $physicLibBody = "\\newpage\n" . '\section{' .  $this->formatValue($physicLibName, Type::text) . "}\n";
            $physicLibBody .= $physicLibSection;
            $this->addTable($physicLibBody, $indexedDataTypes, $values, $yearsToUse, $lang);
            $body .= $physicLibBody;
        }
    }

    private function filterDisableValue(array &$values, array $yearToUse, Surveys $lastActiveSurvey1,
                                        ?Surveys $lastActiveSurvey2, ?Surveys $lastActiveSurvey3,
                                        string $administrationType)
    {
        $activeValues = $this->managerRegistry->getRepository(SurveyDataTypes::class)
            ->findBy([
                'survey' => [$lastActiveSurvey1, $lastActiveSurvey2, $lastActiveSurvey3],
                'active' => true
            ]);

        $valuesLastYear = &$values[$yearToUse[0]];

        foreach ($valuesLastYear as $code => $value)
        {
            $found = false;
            foreach ($activeValues as $activeValue)
            {
                if ($code === $activeValue->getType()->getCode())
                {
                    $found = true;
                    break;
                }
            }

            if (!$found)
            {
                unset($valuesLastYear[$code]);
            }
        }

        if ($administrationType === AdministrationTypeStr::documentaryStructure)
        {
            $this->filterLibDisableValue($values, $yearToUse, $activeValues);
        }
    }

    private function filterLibDisableValue(array &$values, array $yearToUse, array $activeValues)
    {
        if ($values[self::ASSOCIATED_LIB_KEY] && count($values[self::ASSOCIATED_LIB_KEY]) > 0)
        {
            foreach ($values[self::ASSOCIATED_LIB_KEY] as &$associatedPhysicLibValues)
            {
                $libValueLastYear = &$associatedPhysicLibValues[self::LIB_VALUES_KEY][$yearToUse[0]];

                foreach ($libValueLastYear as $code => $value)
                {
                    $found = false;
                    foreach ($activeValues as $activeValue)
                    {
                        if ($code === $activeValue->getType()->getCode())
                        {
                            $found = true;
                            break;
                        }
                    }

                    if (!$found)
                    {
                        unset($libValueLastYear[$code]);
                    }
                }
            }
        }
    }

    private function addTable(string &$body, array $indexedDataTypes, array $values, array $yearToUse, string $lang)
    {
        if ($yearToUse[0] === null)
        {
            return;
        }

        $year1 = $yearToUse[0];
        $year2 = $yearToUse[1] === null ? '---' : $yearToUse[1];
        $year3 = $yearToUse[2] === null ? '---' : $yearToUse[2];
        $valuesToUse = $values[$year1];

        $groupWithType = $this->getGroupIdWithDataType($valuesToUse, $indexedDataTypes);

        foreach ($groupWithType as $groupId)
        {
            $nbrDataType = 0;
            $tabular = '';
            $previousTypeValueIsLong = null;

            foreach ($valuesToUse as $code => $value)
            {
                $dataType = $indexedDataTypes[$code];
                if ($dataType['groupId'] !== $groupId)
                {
                    continue;
                }

                $typeId = $dataType['type']->getId();
                $value1 = $this->formatValue($value, $dataType['type']->getId(), $lang, $dataType);

                $formattedCode = $this->getHyphenateCode($this->formatValue($code, Type::text));
                $name = $this->formatValue($dataType['name'], Type::text);

                $isLongText = ($typeId === Type::text && $this->isLongText($dataType));
                if ($nbrDataType === 0)
                {
                    if ($isLongText)
                    {
                        $tabular =  $this->getLongTextValueLine($formattedCode, $name, $value1);
                    }
                    else
                    {
                        $tabular = $this->getHeaderTabular($year1, $year2, $year3);
                        $tabular .= $this->getValueLine($values, $value1, $year2, $year3, $name, $formattedCode,
                           $code, $typeId, $lang, $dataType);
                    }
                }
                else
                {
                    if ($isLongText)
                    {
                        if (!$previousTypeValueIsLong)
                        {
                            $tabular .= "\\end{longtable}\n";
                        }
                        $tabular .= $this->getLongTextValueLine($formattedCode, $name, $value1);
                    }
                    else
                    {
                        if ($previousTypeValueIsLong)
                        {
                            $tabular .= $this->getHeaderTabular($year1, $year2, $year3);
                        }
                        $tabular .= $this->getValueLine($values, $value1, $year2, $year3, $name, $formattedCode, $code,
                            $typeId, $lang, $dataType);
                    }
                }
                $previousTypeValueIsLong = $isLongText;
                $nbrDataType += 1;
            }

            if ($nbrDataType > 0)
            {
                if (!$previousTypeValueIsLong)
                {
                    $tabular .= '\end{longtable}';
                }
                $body = str_replace("%$groupId%", $tabular, $body);
            }
        }
    }

    private function getValueLine(array $values, string $value1, string $year2, string $year3, string $name,
                                  string $formattedCode, string $code, int $typeId, string $lang, array $dataType)
    : string
    {
        $value2 = $this->formatValue($this->getOldValue($year2, $code, $values), $typeId, $lang, $dataType);
        $value3 = $this->formatValue($this->getOldValue($year3, $code, $values), $typeId, $lang, $dataType);
        $measureUnit = $this->formatValue($dataType['measureUnit'], Type::text);
        $date = $this->formatValue($dataType['date'], Type::text);

        $line = "$formattedCode & $name & $value1 & $measureUnit & $date & $value2 & $value3 ";
        $line .= "\\tabularnewline\n\hline\n";
        return $line;
    }

    private function getGroupIdWithDataType(array $valuesToUse, array $indexedDataTypes): array
    {
        $groupWithType = [];
        foreach ($valuesToUse as $code => $value)
        {
            $dataType = $indexedDataTypes[$code];
            if (!in_array($dataType['groupId'], $groupWithType))
            {
                array_push($groupWithType, $dataType['groupId']);
            }

        }
        return $groupWithType;
    }

    private function getHeaderTabular(string $year1, string $year2, string $year3): string
    {
        $header = "\begin{longtable}{|P{2.5cm}|M{5.5cm}|M{3cm}|M{2.5cm}|M{2cm}|M{3cm}|M{3cm}|}\n";
        $header .= "\hline\n";
        $header .= '\textbf{Code} & \textbf{Nom} & \hfill\textbf{' . $year1 . '} ';
        $header .= '& \textbf{Unité} & \textbf{Date} & \hfill\textbf{' . $year2 . '}';
        $header .= '& \hfill\textbf{' . $year3 . '}' . "\n";
        $header .= "\\tabularnewline\n";
        $header .= "\hline\n\\endhead\n";
        return $header;
    }

    private function isLongText(?array $dataType): bool
    {
        if ($dataType && $dataType['type']->getId() === Type::text && $dataType['constraint'] != null)
        {
            $maxLength = $dataType['constraint']->getMaxLength();
            return $maxLength != null && $maxLength > 64;
        }
        return false;
    }

    private function getHyphenateCode(string $code): string
    {
        if (strlen($code) <= 12)
        {
            return '\mbox{' . $code . '}';
        }

        $sub1 = substr($code, 0, 11);
        $sub2 = substr($code, 11);

        return '\makecell[l]{' . $sub1 . '\\\\' . $sub2 . '}';
    }

    private function formatValue(?string $value, int $type, string $lang = ESGBUController::DEFAULT_LANG,
                                 ?array $dataType = null): string
    {
        if ($value == null)
        {
            return '---';
        }

        $value = trim($value);
        $value = trim($value, '"');

        if (strlen($value) === 0)
        {
            return '---';
        }

        switch ($type)
        {
            case Type::boolean:
                if ($value === '1' || $value === 'true') {
                    return 'oui';
                } else if ($value === '0' || $value === 'false') {
                    return 'non';
                } else {
                    return $value;
                }
            case Type::text:
                return preg_replace_callback("/([\^%~\\\\#\$&_{}])/", function ($match) {
                    return self::LATEX_SPECIAL_CHAR[$match[0]];
                }, $value);
            case Type::number:
                if ($value === '---' || $value === 'ND')
                {
                    return $value;
                }
                $isDecimal = false;
                if ($dataType !== null && $dataType['constraint'] != null)
                {
                    $isDecimal = $dataType['constraint']->getIsDecimal();
                }
                $numberFormatter = new NumberFormatter($lang, NumberFormatter::DECIMAL);
                if ($isDecimal)
                {
                    $numberFormatter->setAttribute($numberFormatter::FRACTION_DIGITS, 2);
                } else {
                    $numberFormatter->setAttribute($numberFormatter::FRACTION_DIGITS, 0);
                }
                $value = $numberFormatter->format(str_replace(',', '.', $value));
                return "\hfill $value";
            case Type::operation:
                if ($value === '---')
                {
                    return '---';
                }
                $numberFormatter = new NumberFormatter($lang, NumberFormatter::DECIMAL);
                $numberFormatter->setAttribute($numberFormatter::FRACTION_DIGITS, 2);
                $value = $numberFormatter->format($value);
                return "\hfill $value";
            default:
                return $value;
        }
    }

    private function getLongTextValueLine(string $code, string $name, ?string $value): string
    {
        $longTextValue = "\begin{longtable}{|P{2.5cm}|M{5.5cm}|M{15.22cm}|}\n";
        $longTextValue .= "\hline\n";
        $longTextValue .= '\textbf{Code} & \textbf{Nom} & \textbf{Valeur} \tabularnewline' . "\n";
        $longTextValue .= "\hline\n\\endhead\n";
        $longTextValue .= "$code & $name & $value \\tabularnewline\n";
        $longTextValue .= "\hline\n";
        $longTextValue .= "\\end{longtable}\n";
        return $longTextValue;
    }

    private function getOldValue(string $year, string $code, array $values): ?string
    {
        if ($year !== '---' && array_key_exists($code, $values[$year]))
        {
            return $values[$year][$code];
        }
        return '---';
    }

    private function addSection(string &$body, array $indexedGroups, ?int $lastGroupId = null, int $deep = 0)
    {
        $i = 0;
        foreach ($indexedGroups as $groupId => $group)
        {
            if ($lastGroupId === $group['parentGroupId']) {
                $body .= '\\' . $this->getSectionByDeep($deep);
                $body .= '{' . $this->formatValue($group['title'], Type::text) . '}' . "\n%$groupId%\n";
                $this->addSection($body, $indexedGroups, $groupId, $deep + 1);
            }
            $i++;
        }
    }

    private function getSectionByDeep(int $deep): string
    {
        if ($deep < count(self::SECTIONS)) {
            return self::SECTIONS[$deep];
        } else {
            return self::SECTIONS[count(self::SECTIONS) - 1];
        }
    }


    /**
     * @param Surveys $lastActiveSurvey
     * @param array $values
     * @return string[]
     * @throws Exception
     */
    private function getYearsToUse(Surveys $lastActiveSurvey, array $values): array
    {
        $yearToUse = [null, null, null];
        $lastActiveSurveyYear = $lastActiveSurvey->getDataCalendarYear()->format('Y');
        $years = array_keys($values);
        $lastActiveYearIndex = array_search($lastActiveSurveyYear, $years);

         if ($lastActiveYearIndex === false) {
             throw new Exception('Bad survey year', Response::HTTP_BAD_REQUEST);
         }
        $yearToUse[0] = $lastActiveSurveyYear;

         $beforeLastActiveYearIndex = $lastActiveYearIndex + 1;
         if ($beforeLastActiveYearIndex < count($years)
             && preg_match('/\d{4}/', $years[$beforeLastActiveYearIndex])) {
             $yearToUse[1] = $years[$beforeLastActiveYearIndex];
         }

         $lastBeforeLastActiveYearIndex = $beforeLastActiveYearIndex + 1;
        if ($lastBeforeLastActiveYearIndex < count($years)
            && preg_match('/\d{4}/', $years[$lastBeforeLastActiveYearIndex])) {
            $yearToUse[2] = $years[$lastBeforeLastActiveYearIndex];
        }

        return $yearToUse;
    }

    private function getTemplate(array $values, string $surveyName, string $lang): string
    {
        $fileName = self::TEMPLATE_DIR . $lang . self::LATEX_EXT;
        if (!file_exists($fileName))
        {
            $fileName = self::TEMPLATE_DIR . ESGBUController::DEFAULT_LANG . self::LATEX_EXT;
        }

        $template = file_get_contents($fileName);
        $template = str_replace(
            self::ADMINISTRATION_NAME,
            $this->formatValue($values['name'], Type::text), $template);
        return str_replace(
            self::SURVEY_NAME,
            $this->formatValue($surveyName, Type::text), $template);
    }

    private function getAllDataTypeInformationIndexedByCode(string $lang): array
    {
        $dataTypes = $this->dataTypesController->listAction(null, $lang)->getData();
        $indexedDataTypes = [];
        if (!$dataTypes)
        {
            return [];
        }
        foreach ($dataTypes as $dataType) {
            $indexedDataTypes[$dataType['code']] = $dataType;
        }
        return $indexedDataTypes;
    }

    private function getAllGroupsInformationIndexedById(string $administrationType, string $lang): array
    {
        $groups = $this->groupsController->listAction(0, $administrationType, $lang)->getData();
        $indexedGroups = [];
        if (!$groups)
        {
            return [];
        }
        foreach ($groups as $group) {
            $indexedGroups[$group['id']] = $group;
        }

        return $indexedGroups;
    }
}
