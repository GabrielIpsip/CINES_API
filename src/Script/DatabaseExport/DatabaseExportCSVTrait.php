<?php


namespace App\Script\DatabaseExport;

use App\Common\Enum\AdministrationType;
use App\Common\Enum\AdministrationTypeStr;
use App\Common\Enum\Encoding;
use App\Common\Enum\Type;
use App\Controller\AbstractController\ESGBUController;
use App\Entity\DocumentaryStructureDataValues;
use App\Entity\DocumentaryStructures;
use App\Entity\EstablishmentDataValues;
use App\Entity\Establishments;
use App\Entity\PhysicalLibraries;
use App\Entity\PhysicalLibraryDataValues;
use App\Utils\StringTools;
use ReflectionClass;

trait DatabaseExportCSVTrait
{
    private $CSV_DELIMITER = ';';

    private $ESTABLISHMENT_GENERAL_INFO_CSV_FILE_NAME = 'institutions_general_infos.csv';
    private $DOC_STRUCT_GENERAL_INFO_CSV_FILE_NAME = 'documentary_structures_general_infos.csv';
    private $PHYSIC_LIB_GENERAL_INFO_CSV_FILE_NAME = 'physical_libraries_general_infos.csv';

    private $ESTABLISHMENT_GENERAL_INFO_DATA_CSV_FILE_NAME = 'institutions_general_infos_and_data.csv';
    private $DOC_STRUCT_GENERAL_INFO_DATA_CSV_FILE_NAME = 'documentary_structures_general_infos_and_data.csv';
    private $PHYSIC_LIB_GENERAL_INFO_DATA_CSV_FILE_NAME = 'physical_libraries_general_infos_and_data.csv';

    private $defaultEncoding = Encoding::UTF8;

    private $CSV_DIR;

    private function executeCSV(string $directory)
    {
        $this->CSV_DIR = $directory . '/csv';

        if (!file_exists($this->CSV_DIR)) {
            mkdir($this->CSV_DIR, 0755);
        }

        if (!$this->justLastSurvey) {
            foreach ($this->surveys as $survey) {
                $dataCalendarYear = $survey->getDataCalendarYear()->format('Y');
                $beginningFileName = $this->CSV_DIR . '/' . $this->defaultEncoding . '_' . $dataCalendarYear . '_';
                $surveyId = $survey->getId();

                print("Building establishment general information in CSV..\n");
                $this->buildEstablishmentGeneralInfo($surveyId, $beginningFileName);

                print("Building documentary structure general information in CSV..\n");
                $this->buildDocStructGeneralInfo($surveyId, $beginningFileName);

                print("Building physical library general information in CSV..\n");
                $this->buildPhysicLibGeneralInfo($surveyId, $beginningFileName);
            }
        }

        if (!$this->justLastSurvey || $this->justLastSurvey === AdministrationTypeStr::institution) {
            print("Building establishment general info and data in CSV..\n");
            $this->buildEstablishmentGeneralInfoAndData();
        }

        if (!$this->justLastSurvey || $this->justLastSurvey === AdministrationTypeStr::documentaryStructure) {
            print("Building documentary structure general info and data in CSV..\n");
            $this->buildDocStructGeneralInfoAndData();
        }

        if (!$this->justLastSurvey || $this->justLastSurvey === AdministrationTypeStr::physicalLibrary) {
            print("Building physical library general info and data in CSV..\n");
            $this->buildPhysicLibGeneralInfoAndData();
        }

        $this->convertCSVFile();
    }

    public function convertCSVFile()
    {
        $files = array_diff(scandir($this->CSV_DIR), array('..', '.'));

        $encodings = new ReflectionClass(Encoding::class);
        $encodings = $encodings->getConstants();

        foreach ($files as $file) {
            if (!str_starts_with($file, $this->defaultEncoding)) {
                continue;
            }

            $originalFilePath = $this->CSV_DIR . '/' . $file;

            foreach ($encodings as $encoding) {
                if ($encoding === $this->defaultEncoding) {
                    continue;
                }

                $filePath = $this->CSV_DIR . '/' . $encoding . substr($file, strlen($this->defaultEncoding));

                $originalFile = fopen($originalFilePath, 'r');
                $file = fopen($filePath, 'w');

                if ($originalFile && $file) {
                    while (($buffer = fgets($originalFile)) !== false) {
                        fwrite($file, StringTools::getEncodedString($buffer, $encoding));
                    }
                }

                fclose($file);
                fclose($originalFile);
            }
        }
    }

    private function fputcsv($handle, array $fields)
    {
        foreach ($fields as &$field) {
            if ($this->defaultEncoding !== Encoding::UTF8 && is_string($field)) {
                StringTools::encodeString($field, $this->defaultEncoding);
            }
        }

        fputcsv($handle, $fields, $this->CSV_DELIMITER);
    }

    private function buildEstablishmentGeneralInfoAndData()
    {
        $establishmentGeneralInfoDataFile = null;

        if (!$this->justLastSurvey) {
            $establishmentGeneralInfoDataFile = fopen($this->CSV_DIR . '/' . $this->defaultEncoding . '_' . $this->ESTABLISHMENT_GENERAL_INFO_DATA_CSV_FILE_NAME, 'w');
        }

        $headerName = $this->getEstablishmentGeneralInfoNameHeader();
        $headerCode = $this->getEstablishmentGeneralInfoCodeHeader();
        array_unshift($headerName, 'Année');
        array_unshift($headerCode, 'Année');

        $dataTypeInHeader = array();

        foreach ($this->indexedDataTypes as $dataType) {
            if ($dataType->getGroup()->getAdministrationType()->getId() === AdministrationType::institution) {
                array_push($headerName, $this->getTranslation(ESGBUController::DEFAULT_LANG, $dataType->getName()));
                array_push($headerCode, $dataType->getCode());
                array_push($dataTypeInHeader, $dataType);
            }
        }

        if (!$this->justLastSurvey) {
            $this->fputcsv($establishmentGeneralInfoDataFile, $headerName);
            $this->fputcsv($establishmentGeneralInfoDataFile, $headerCode);
        }

        foreach ($this->surveys as $survey) {
            $dataCalendarYear = $survey->getDataCalendarYear()->format('Y');
            $establishmentGeneralInfoDataYearFile = fopen($this->getGeneralInfoAndDataFileName($dataCalendarYear, AdministrationType::institution), 'w');
            $this->fputcsv($establishmentGeneralInfoDataYearFile, $headerName);
            $this->fputcsv($establishmentGeneralInfoDataYearFile, $headerCode);

            foreach ($this->indexedEstablishments[$survey->getId()] as $establishment) {
                $generalInfo = $this->formatEstablishmentGeneralInfo($establishment);

                $line = array_merge([$dataCalendarYear], $generalInfo);

                $dataValues = $this->managerRegistry->getRepository(EstablishmentDataValues::class)
                    ->getAllEstablishmentDataValuesLikeArray($establishment, $survey);

                $this->insertDataValuesInLine($line, $dataTypeInHeader, $dataValues);
                $this->fputcsv($establishmentGeneralInfoDataYearFile, $line);
                if (!$this->justLastSurvey) {
                    $this->fputcsv($establishmentGeneralInfoDataFile, $line);
                }
            }
            fclose($establishmentGeneralInfoDataYearFile);
        }
        if (!$this->justLastSurvey) {
            fclose($establishmentGeneralInfoDataFile);
        }
    }

    private function buildDocStructGeneralInfoAndData()
    {
        $docStructGeneralInfoDataFile = null;
        if (!$this->justLastSurvey) {
            $docStructGeneralInfoDataFile = fopen($this->CSV_DIR . '/' . $this->defaultEncoding . '_' . $this->DOC_STRUCT_GENERAL_INFO_DATA_CSV_FILE_NAME, 'w');
        }

        $headerName = $this->getDocStructGeneralInfoNameHeader();
        $headerCode = $this->getDocStructGeneralInfoCodeHeander();
        array_unshift($headerName, 'Année');
        array_unshift($headerCode, 'Année');

        $dataTypeInHeader = array();

        foreach ($this->indexedDataTypes as $dataType) {
            if ($dataType->getGroup()->getAdministrationType()->getId() === AdministrationType::documentaryStructure) {
                array_push($headerName, $this->getTranslation(ESGBUController::DEFAULT_LANG, $dataType->getName()));
                array_push($headerCode, $dataType->getCode());
                array_push($dataTypeInHeader, $dataType);
            }
        }

        if (!$this->justLastSurvey) {
            $this->fputcsv($docStructGeneralInfoDataFile, $headerName);
            $this->fputcsv($docStructGeneralInfoDataFile, $headerCode);
        }

        foreach ($this->surveys as $survey) {
            $dataCalendarYear = $survey->getDataCalendarYear()->format('Y');
            $docStructGeneralInfoDataYearFile = fopen($this->getGeneralInfoAndDataFileName($dataCalendarYear, AdministrationType::documentaryStructure), 'w');
            $this->fputcsv($docStructGeneralInfoDataYearFile, $headerName);
            $this->fputcsv($docStructGeneralInfoDataYearFile, $headerCode);

            foreach ($this->indexedDocStruct[$survey->getId()] as $establishmentId => $indexedDocStruct) {
                foreach ($indexedDocStruct as $docStruct) {
                    $generalInfo = $this->formatDocStructGeneralInfo($docStruct, $establishmentId, $survey->getId());
                    if ($generalInfo == null) {
                        continue;
                    }

                    $line = array_merge([$dataCalendarYear], $generalInfo);

                    $dataValues = $this->managerRegistry->getRepository(DocumentaryStructureDataValues::class)
                        ->getAllDocStructDataValuesLikeArray($docStruct, $survey);

                    $this->insertDataValuesInLine($line, $dataTypeInHeader, $dataValues);
                    $this->fputcsv($docStructGeneralInfoDataYearFile, $line);
                    if (!$this->justLastSurvey) {
                        $this->fputcsv($docStructGeneralInfoDataFile, $line);
                    }
                }
            }
            fclose($docStructGeneralInfoDataYearFile);
        }

        if (!$this->justLastSurvey) {
            fclose($docStructGeneralInfoDataFile);
        }
    }

    private function buildPhysicLibGeneralInfoAndData()
    {
        $physicLibGeneralInfoDataFile = null;
        if (!$this->justLastSurvey) {
            $physicLibGeneralInfoDataFile = fopen($this->CSV_DIR . '/' . $this->defaultEncoding . '_' . $this->PHYSIC_LIB_GENERAL_INFO_DATA_CSV_FILE_NAME, 'w');
        }

        $headerName = $this->getPhysicLibGeneralInfoNameHeader();
        $headerCode = $this->getPhysicLibGeneralInfoCodeHeader();
        array_unshift($headerName, 'Année');
        array_unshift($headerCode, 'Année');

        $dataTypeInHeader = array();

        foreach ($this->indexedDataTypes as $dataType) {
            if ($dataType->getGroup()->getAdministrationType()->getId() === AdministrationType::physicalLibrary) {
                array_push($headerName, $this->getTranslation(ESGBUController::DEFAULT_LANG, $dataType->getName()));
                array_push($headerCode, $dataType->getCode());
                array_push($dataTypeInHeader, $dataType);
            }
        }

        if (!$this->justLastSurvey) {
            $this->fputcsv($physicLibGeneralInfoDataFile, $headerName);
            $this->fputcsv($physicLibGeneralInfoDataFile, $headerCode);
        }

        foreach ($this->surveys as $survey) {
            $dataCalendarYear = $survey->getDataCalendarYear()->format('Y');
            $physicLibGeneralInfoDataYearFile = fopen($this->getGeneralInfoAndDataFileName($dataCalendarYear, AdministrationType::physicalLibrary), 'w');
            $this->fputcsv($physicLibGeneralInfoDataYearFile, $headerName);
            $this->fputcsv($physicLibGeneralInfoDataYearFile, $headerCode);

            foreach ($this->indexedPhysicLib[$survey->getId()] as $docStructId => $indexedPhysicLib) {
                foreach ($indexedPhysicLib as $physicLib) {
                    $generalInfo = $this->formatPhysicLibGeneralInfo($physicLib, $docStructId, $survey->getId());
                    if ($generalInfo == null) {
                        continue;
                    }

                    $line = array_merge([$dataCalendarYear], $generalInfo);

                    $dataValues = $this->managerRegistry->getRepository(PhysicalLibraryDataValues::class)
                        ->getAllPhysicLibDataValuesLikeArray($physicLib, $survey);

                    $this->insertDataValuesInLine($line, $dataTypeInHeader, $dataValues);
                    $this->fputcsv($physicLibGeneralInfoDataYearFile, $line);

                    if (!$this->justLastSurvey) {
                        $this->fputcsv($physicLibGeneralInfoDataFile, $line);
                    }
                }
            }
            fclose($physicLibGeneralInfoDataYearFile);
        }

        if (!$this->justLastSurvey) {
            fclose($physicLibGeneralInfoDataFile);
        }
    }

    private function insertDataValuesInLine(array &$line, array $dataTypeInHeader, array $dataValues)
    {
        foreach ($dataTypeInHeader as $dataType) {
            $inserted = false;
            foreach ($dataValues as $dataValue) {
                if ($dataType->getId() === $dataValue['data_type_fk']) {
                    $value = trim($dataValue['value']);
                    switch ($dataType->getType()->getId()) {
                        case Type::number:
                        case Type::operation:
                            $value = str_replace('.', ',', $value);
                            break;
                        case Type::boolean:
                            if ($value === '1' || $value === 'true') {
                                $value = 'oui';
                            } else if ($value === '0' || $value === 'false') {
                                $value = 'non';
                            }
                            break;
                        case Type::text:
                            $value = html_entity_decode($value);
                            if (preg_match('/^[0-9]+$/', $value)) {
                                $value = '\'' . $value . '\'';
                            }
                    }

                    if ($value === 'ND') {
                        $value = '';
                    }

                    array_push($line, $value);
                    $inserted = true;
                    break;
                }
            }
            if (!$inserted) {
                array_push($line, '');
            }
        }
    }

    private function buildDocStructGeneralInfo(int $surveyId, string $beginningFileName)
    {
        $docStructGeneralInfoFile = fopen($beginningFileName . $this->DOC_STRUCT_GENERAL_INFO_CSV_FILE_NAME, 'w');

        $header = $this->getDocStructGeneralInfoNameHeader();
        $this->fputcsv($docStructGeneralInfoFile, $header);
        $header = $this->getDocStructGeneralInfoCodeHeander();
        $this->fputcsv($docStructGeneralInfoFile, $header);

        foreach ($this->indexedDocStruct[$surveyId] as $establishmentId => $indexedDocStruct) {
            foreach ($indexedDocStruct as $docStruct) {
                $generalInfo = $this->formatDocStructGeneralInfo($docStruct, $establishmentId, $surveyId);
                if ($generalInfo != null) {
                    $this->fputcsv($docStructGeneralInfoFile, $generalInfo);
                }
            }
        }

        fclose($docStructGeneralInfoFile);
    }

    private function buildEstablishmentGeneralInfo(int $surveyId, string $beginningFileName)
    {
        $establishmentGeneralInfoFile = fopen($beginningFileName . $this->ESTABLISHMENT_GENERAL_INFO_CSV_FILE_NAME, 'w');

        $header = $this->getEstablishmentGeneralInfoNameHeader();
        $this->fputcsv($establishmentGeneralInfoFile, $header);
        $header = $this->getEstablishmentGeneralInfoCodeHeader();
        $this->fputcsv($establishmentGeneralInfoFile, $header);

        foreach ($this->indexedEstablishments[$surveyId] as $establishment) {
            $this->fputcsv($establishmentGeneralInfoFile, $this->formatEstablishmentGeneralInfo($establishment));
        }

        fclose($establishmentGeneralInfoFile);
    }

    private function buildPhysicLibGeneralInfo(int $surveyId, string $beginningFileName)
    {
        $physicLibGeneralInfoFile = fopen($beginningFileName . $this->PHYSIC_LIB_GENERAL_INFO_CSV_FILE_NAME, 'w');

        $header = $this->getPhysicLibGeneralInfoNameHeader();
        $this->fputcsv($physicLibGeneralInfoFile, $header);
        $header = $this->getPhysicLibGeneralInfoCodeHeader();
        $this->fputcsv($physicLibGeneralInfoFile, $header);

        foreach ($this->indexedPhysicLib[$surveyId] as $docStructId => $indexedPhysicLib) {
            foreach ($indexedPhysicLib as $physicLib) {
                $generalInfo = $this->formatPhysicLibGeneralInfo($physicLib, $docStructId, $surveyId);
                if ($generalInfo != null) {
                    $this->fputcsv($physicLibGeneralInfoFile, $generalInfo);
                }
            }
        }

        fclose($physicLibGeneralInfoFile);
    }

    private function formatEstablishmentGeneralInfo(Establishments $establishment): array
    {
        return [
            $establishment->getId(),
            $establishment->getOfficialName(),
            $establishment->getUseName(),
            $establishment->getAcronym(),
            $establishment->getBrand(),
            $establishment->getAddress(),
            $establishment->getCity(),
            '\'' . $establishment->getPostalCode() . '\'',
            $establishment->getDepartment()->getName(),
            $establishment->getDepartment()->getRegion()->getName(),
            $establishment->getWebsite(),
            $establishment->getType()->getName(),
            html_entity_decode(strip_tags($establishment->getInstruction()))
        ];
    }

    private function formatDocStructGeneralInfo(DocumentaryStructures $docStruct, int $establishmentId, int $surveyId): ?array
    {
        foreach ($this->indexedEstablishments[$surveyId] as $establishment) {
            if ($establishment->getId() === $establishmentId) {
                return [
                    $docStruct->getId(),
                    $establishmentId,
                    $establishment->getUseName(),
                    $docStruct->getOfficialName(),
                    $docStruct->getOfficialName(),
                    $docStruct->getAcronym(),
                    $docStruct->getAddress(),
                    $docStruct->getCity(),
                    '\'' . $docStruct->getPostalCode() . '\'',
                    $docStruct->getDepartment()->getName(),
                    $docStruct->getDepartment()->getRegion()->getName(),
                    $docStruct->getWebsite(),
                    html_entity_decode(strip_tags($docStruct->getInstruction()))
                ];
            }
        }
        return null;
    }

    private function formatPhysicLibGeneralInfo(PhysicalLibraries $physicLib, int $docStructId, int $surveyId): ?array
    {
        foreach ($this->indexedDocStruct[$surveyId] as $establishmentId => $indexedDocStruct) {
            foreach ($indexedDocStruct as $docStruct) {
                if ($docStruct->getId() === $docStructId) {
                    foreach ($this->indexedEstablishments[$surveyId] as $establishment) {
                        if ($establishment->getId() === $establishmentId) {
                            return [
                                $physicLib->getId(),
                                $docStructId,
                                $docStruct->getUseName(),
                                $establishmentId,
                                $establishment->getUseName(),
                                $physicLib->getOfficialName(),
                                $physicLib->getUseName(),
                                $physicLib->getAddress(),
                                $physicLib->getCity(),
                                '\'' . $physicLib->getPostalCode() . '\'',
                                $physicLib->getDepartment()->getName(),
                                $physicLib->getDepartment()->getRegion()->getName(),
                                $physicLib->getFictitious() ? 'Fictive' : 'Physique',
                                $physicLib->getSortOrder(),
                                html_entity_decode(strip_tags($physicLib->getInstruction()))
                            ];
                        }
                    }
                }
            }
        }
        return null;
    }

    private function getPhysicLibGeneralInfoNameHeader(): array
    {
        return ['ID Bib', 'ID SD', 'Nom d\'usage SD', 'ID établissement', 'Nom d\'usage établissement',
            'Nom officiel', 'Nom d\'usage', 'Adresse', 'Ville', 'Code postal', 'Département', 'Région', 'Type',
            'Ordre de tri', 'Commentaire'
        ];
    }

    private function getPhysicLibGeneralInfoCodeHeader(): array
    {
        return ['BibId', 'SdId', 'SdNomUsage', 'EtabId', 'EtabNomUsage', 'BibNomOfficiel', 'BibNomUsage',
            'BibAddress', 'BibVille', 'BibCodePostal', 'BibDepartement', 'BibRegion' , 'BibPhysique', 'BibOrdreTri',
            'BibCommentaire'];
    }

    private function getDocStructGeneralInfoNameHeader(): array
    {
        return ['ID SD', 'ID établissement', 'Nom d\'usage établissement', 'Nom officiel', 'Nom d\'usage',
            'Sigle', 'Adresse', 'Ville', 'Code postal', 'Département', 'Région', 'Site internet', 'Commentaire'
        ];
    }

    private function getDocStructGeneralInfoCodeHeander(): array
    {
        return ['SdId', 'EtabId', 'EtabNomUsage', 'SdNomOfficiel', 'SdNomUsage', 'SdSigle', 'SdAddress',
            'SdVille', 'SdCodePostal', 'SdDepartement', 'SdRegion', 'SdSiteInternet', 'SdCommentaire'];
    }

    private function getEstablishmentGeneralInfoNameHeader(): array
    {
        return ['ID établissement', 'Nom officiel', 'Nom d\'usage', 'Sigle', 'Marque', 'Adresse', 'Ville',
            'Code postal', 'Département', 'Région', 'Site internet', 'Type', 'Commentaire'
        ];
    }

    private function getEstablishmentGeneralInfoCodeHeader(): array
    {
        return ['EtabId', 'EtabNomOfficiel', 'EtabNomUsage', 'EtabSigle', 'EtabMarque', 'EtabAddress',
            'EtabVille', 'EtabCodePostal', 'EtabDepartement', 'EtabRegion', 'EtabSiteInternet', 'EtabType',
            'EtabCommentaire'];
    }

    private function getGeneralInfoAndDataFileName(string $dataCalendarYear, int $administrationType): string
    {
        $fileName = $this->CSV_DIR . '/' . $this->defaultEncoding;
        if ($this->justLastSurvey) {
            $fileName .= '_LAST';
        }
        $fileName .= '_' . $dataCalendarYear . '_';

        switch ($administrationType) {
            case AdministrationType::institution:
                $fileName .= $this->ESTABLISHMENT_GENERAL_INFO_DATA_CSV_FILE_NAME;
                break;
            case AdministrationType::documentaryStructure:
                $fileName .= $this->DOC_STRUCT_GENERAL_INFO_DATA_CSV_FILE_NAME;
                break;
            case AdministrationType::physicalLibrary:
                $fileName .= $this->PHYSIC_LIB_GENERAL_INFO_DATA_CSV_FILE_NAME;
                break;
        }

        return $fileName;
    }
}
