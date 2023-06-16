<?php


namespace App\Script\DatabaseExport;


use App\Common\Classes\Elasticsearch;
use App\Common\Enum\AdministrationType;
use App\Common\Enum\Type;
use App\Entity\AbstractEntity\Administrations;
use App\Entity\DataTypes;
use App\Entity\DocumentaryStructureDataValues;
use App\Entity\DocumentaryStructures;
use App\Entity\EstablishmentDataValues;
use App\Entity\Establishments;
use App\Entity\Numbers;
use App\Entity\PhysicalLibraries;
use App\Entity\PhysicalLibraryDataValues;
use App\Entity\Surveys;
use App\Utils\StringTools;
use Exception;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 *
 * Tool to create dump data values from database for Elasticsearch.
 *
 * Commands to insert data in Elasticsearch:
 * curl -XDELETE localhost:9200/esgbu_physical_libraries
 * curl -XPUT -H "Content-Type: application/json" localhost:9200/esgbu_physical_libraries --data-binary @mapping_esgbu_physical_libraries.json
 * curl -XPUT -H "Content-Type: application/json" localhost:9200/_bulk --data-binary @esgbu_physical_libraries.json
 *
 * curl -XDELETE localhost:9200/esgbu_documentary_structures
 * curl -XPUT -H "Content-Type: application/json" localhost:9200/esgbu_documentary_structures --data-binary @mapping_esgbu_documentary_structures.json
 * curl -XPUT -H "Content-Type: application/json" localhost:9200/_bulk --data-binary @esgbu_documentary_structures.json
 *
 * curl -XDELETE localhost:9200/esgbu_institutions
 * curl -XPUT -H "Content-Type: application/json" localhost:9200/esgbu_institutions --data-binary @mapping_esgbu_institutions.json
 * curl -XPUT -H "Content-Type: application/json" localhost:9200/_bulk --data-binary @esgbu_institutions.json
 *
 * Trait DatabaseExportElasticsearchTrait
 * @package App\Script\DatabaseExport
 */
trait DatabaseExportElasticsearchTrait
{

    private $ELASTICSEARCH_DIR;

    private function executeElasticsearch(string $directory)
    {
        $this->ELASTICSEARCH_DIR = $directory . '/elasticsearch';

        if (!file_exists($this->ELASTICSEARCH_DIR)) {
            mkdir($this->ELASTICSEARCH_DIR, 0755);
        }

        print("Building institution mapping..\n");
        $establishmentMappingFileName = $this->buildEstablishmentMapping();

        print("Building institution index..\n");
        $establishmentIndexFileName = $this->buildEstablishmentIndex();

        print("Rebuilding institution index on elasticsearch..\n");
        $this->rebuildingElasticsearchIndex(
            Elasticsearch::ESTABLISHMENT_INDEX_NAME,
            $establishmentMappingFileName,
            $establishmentIndexFileName);

    }

    private function rebuildingElasticsearchIndex(string $indexName, string $mappingPath, string $indexPath)
    {
        try {
            $elasticsearch = new Elasticsearch($indexName);

            $response = $elasticsearch->deleteIndex();
            if ($response->getStatusCode() === Response::HTTP_NOT_FOUND) {
                print("$indexName already deleted!\n");
            }
            $elasticsearch->setMapping($mappingPath);
            $elasticsearch->feedIndex($indexPath);

        } catch (TransportExceptionInterface $e) {
            print("Error with $indexName!\n");
        } catch (Exception $e) {
            print("Index '$indexName' not found!\n");
            print_r($e->getMessage());
        }
    }

    private function buildEstablishmentIndex(): string
    {
        $establishmentIndexFileName = $this->ELASTICSEARCH_DIR . '/' . Elasticsearch::ESTABLISHMENT_INDEX_NAME . '.json';
        $establishmentIndexFile = fopen($establishmentIndexFileName, 'w');

        foreach ($this->surveys as $survey) {
            foreach ($this->indexedEstablishments[$survey->getId()] as $establishment) {
                $establishmentBegin = $this->buildEstablishmentDocumentBegin($establishment);
                $establishmentId = $establishment->getId();

                $establishmentDocument = $establishmentBegin;
                $establishmentDocument['year'] = $survey->getDataCalendarYear()->format('Y');

                $dataValues = $this->getEstablishmentDataValues($establishment, $survey);
                $this->addDataValueToDocument($establishmentDocument, $dataValues, AdministrationType::institution);

                if (array_key_exists($establishmentId, $this->indexedDocStruct[$survey->getId()])) {
                    $establishmentDocument['documentaryStructures'] = array();

                    foreach ($this->indexedDocStruct[$survey->getId()][$establishmentId] as $docStruct) {
                        $docStructId = $docStruct->getId();

                        $docStructInfo = [
                            'id' => $docStruct->getId(),
                            'officialName' => $docStruct->getOfficialName(),
                            'useName' => $docStruct->getUseName(),
                            'acronym' => $docStruct->getAcronym(),
                            'address' => $docStruct->getAddress(),
                            'postalCode' => $docStruct->getPostalCode(),
                            'department' => $docStruct->getDepartment()->getName(),
                            'region' => $docStruct->getDepartment()->getRegion()->getName(),
                            'city' => $docStruct->getCity(),
                            'website' => $docStruct->getWebSite(),
                            'instruction' => $docStruct->getInstruction(),
                        ];

                        $dataValues = $this->getDocStructDataValues($docStruct, $survey);
                        $this->addDataValueToDocument($docStructInfo, $dataValues, AdministrationType::documentaryStructure);

                        $this->addPhysicLibInfoToDocument($docStructInfo, $docStructId, $survey);
                        array_push($establishmentDocument['documentaryStructures'], $docStructInfo);
                    }
                }

                $establishmentIndex = json_encode($this->getIndex($establishment, $survey, Elasticsearch::ESTABLISHMENT_INDEX_NAME));
                $establishmentDocument = json_encode($establishmentDocument);

                fwrite($establishmentIndexFile, $establishmentIndex . "\n" . $establishmentDocument . "\n");
            }
        }
        fclose($establishmentIndexFile);
        return $establishmentIndexFileName;
    }

    private function addPhysicLibInfoToDocument(array &$document, int $docStructId, Surveys $survey)
    {
        if (array_key_exists($docStructId, $this->indexedPhysicLib[$survey->getId()])) {
            $document['physicalLibraries'] = array();
            foreach ($this->indexedPhysicLib[$survey->getId()][$docStructId] as $physicLib) {
                $physicLibInfo = [
                    'id' => $physicLib->getId(),
                    'officialName' => $physicLib->getOfficialName(),
                    'useName' => $physicLib->getUseName(),
                    'address' => $physicLib->getAddress(),
                    'city' => $physicLib->getCity(),
                    'postalCode' => $physicLib->getPostalCode(),
                    'department' => $physicLib->getDepartment()->getName(),
                    'region' => $physicLib->getDepartment()->getRegion()->getName(),
                    'instruction' => $physicLib->getInstruction(),
                    'sortOrder' => $physicLib->getSortOrder(),
                    'fictitious' => $physicLib->getFictitious(),
                ];

                $dataValues = $this->getPhysicLibDataValues($physicLib, $survey);
                $this->addDataValueToDocument($physicLibInfo, $dataValues, AdministrationType::physicalLibrary);
                array_push($document['physicalLibraries'], $physicLibInfo);
            }
        }
    }

    private function getPhysicLibDataValues(PhysicalLibraries $physicLib, Surveys $survey): array
    {
        return $this->managerRegistry->getRepository(PhysicalLibraryDataValues::class)
            ->getAllPhysicLibDataValuesLikeArray($physicLib, $survey);
    }

    private function getDocStructDataValues(DocumentaryStructures $docStruct, Surveys $survey): array
    {
        return $this->managerRegistry->getRepository(DocumentaryStructureDataValues::class)
            ->getAllDocStructDataValuesLikeArray($docStruct, $survey);
    }

    private function getEstablishmentDataValues(Establishments $establishment, Surveys $survey): array
    {
        return $this->managerRegistry->getRepository(EstablishmentDataValues::class)
            ->getAllEstablishmentDataValuesLikeArray($establishment, $survey);
    }

    private function getIndex(Administrations $administration, Surveys $survey, string $indexName): array
    {
        return [
            'index' => [
                '_index' => $indexName,
                '_id' => $administration->getId() . '-' . $survey->getId()
            ]
        ];
    }

    private function buildEstablishmentDocumentBegin(Establishments $establishment): array
    {
        return [
            'id' => $establishment->getId(),
            'officialName' => $establishment->getOfficialName(),
            'useName' => $establishment->getUseName(),
            'acronym' => $establishment->getAcronym(),
            'brand' => $establishment->getBrand(),
            'address' => $establishment->getAddress(),
            'city' => $establishment->getCity(),
            'postalCode' => $establishment->getPostalCode(),
            'department' => $establishment->getDepartment()->getName(),
            'region' => $establishment->getDepartment()->getRegion()->getName(),
            'website' => $establishment->getWebsite(),
            'type' => $establishment->getType()->getName(),
            'instruction' => $establishment->getInstruction()
        ];
    }

    private function addDataValueToDocument(array &$document, array $dataValues, int $adminTypes)
    {
        foreach ($dataValues as $dataValue) {

            if (!array_key_exists($dataValue['data_type_fk'], $this->indexedDataTypes)) {
                continue;
            }

            $dataType = $this->indexedDataTypes[$dataValue['data_type_fk']];

            if ($dataType->getGroup()->getAdministrationType()->getId() !== $adminTypes) {
                continue;
            }

            $value = trim($dataValue['value']);

            switch ($dataType->getType()->getId()) {

                case Type::boolean:
                    if ($value === '1' || $value === 'true') {
                        $value = true;
                    } else if ($value === '0' || $value === 'false') {
                        $value = false;
                    } else {
                        $value = null;
                    }
                    break;

                case Type::number:
                case Type::operation:
                    $value = (trim($value) === 'ND')
                        ? null
                        : floatval($value);
                    break;
            }

            $document[$dataType->getCode()] = $value;
        }
    }

    private function buildEstablishmentMapping(): string
    {
        $properties = [
            'year' => ['type' => 'date', 'format' => 'yyyy'],

            'id' => ['type' => 'long'],
            'officialName' => ['type' => 'text'],
            'useName' => ['type' => 'text'],
            'acronym' => ['type' => 'text'],
            'brand' => ['type' => 'text'],
            'address' => ['type' => 'text'],
            'city' => ['type' => 'text'],
            'postalCode' => ['type' => 'text'],
            'department' => ['type' => 'keyword', 'normalizer' => 'keyword_normalizer'],
            'region' => ['type' => 'keyword', 'normalizer' => 'keyword_normalizer'],
            'website' => ['type' => 'text'],
            'type' => ['type' => 'keyword'],
            'instruction' => ['type' => 'text'],

            'documentaryStructures' => ['type' => 'nested', 'properties' => [
                'id' => ['type' => 'long'],
                'officialName' => ['type' => 'text'],
                'useName' => ['type' => 'text'],
                'acronym' => ['type' => 'text'],
                'address' => ['type' => 'text'],
                'postalCode' => ['type' => 'text'],
                'department' => ['type' => 'keyword', 'normalizer' => 'keyword_normalizer'],
                'region' => ['type' => 'keyword', 'normalizer' => 'keyword_normalizer'],
                'city' => ['type' => 'text'],
                'website' => ['type' => 'text'],
                'instruction' => ['type' => 'text'],
            ]]
        ];

        $properties['documentaryStructures']['properties']['physicalLibraries'] = $this->getPhysicalLibrariesNestedType();

        foreach ($this->indexedDataTypes as $dataType) {
            switch ($dataType->getGroup()->getAdministrationType()->getId()) {
                case AdministrationType::institution:
                    $properties[$dataType->getCode()] = $this->getTypeForMapping($dataType);
                    break;
                case AdministrationType::documentaryStructure:
                    $properties['documentaryStructures']['properties'][$dataType->getCode()] = $this->getTypeForMapping($dataType);
                    break;
                case AdministrationType::physicalLibrary:
                    $properties['documentaryStructures']['properties']['physicalLibraries']['properties'][$dataType->getCode()] = $this->getTypeForMapping($dataType);
                    break;
            }
        }

        $mapping = [
            'settings' => $this->getFrenchAnalyser(),
            'mappings' => [
                'properties' => $properties,
            ]
        ];

        $institutionMappingFileName = $this->ELASTICSEARCH_DIR . '/mapping_' . Elasticsearch::ESTABLISHMENT_INDEX_NAME . '.json';
        $institutionMapping = fopen($institutionMappingFileName, 'w');
        fwrite($institutionMapping, json_encode($mapping));
        fclose($institutionMapping);
        return $institutionMappingFileName;
    }

    private function getPhysicalLibrariesNestedType(): array
    {
        return ['type' => 'nested', 'properties' => [
            'id' => ['type' => 'long'],
            'officialName' => ['type' => 'text'],
            'useName' => ['type' => 'text'],
            'address' => ['type' => 'text'],
            'city' => ['type' => 'text'],
            'postalCode' => ['type' => 'text'],
            'department' => ['type' => 'keyword', 'normalizer' => 'keyword_normalizer'],
            'region' => ['type' => 'keyword', 'normalizer' => 'keyword_normalizer'],
            'instruction' => ['type' => 'text'],
            'sortOrder' => ['type' => 'long'],
            'fictitious' => ['type' => 'boolean'],
        ]];
    }

    private function getTypeForMapping(DataTypes $dataType): array
    {
        $typeInfo = array();

        $type = $dataType->getType();

        switch ($type->getId()) {

            case Type::number:
                $numberInfo = $this->managerRegistry->getRepository(Numbers::class)->find($dataType->getId());
                ($numberInfo && $numberInfo->getIsDecimal())
                    ? $typeInfo['type'] = 'float'
                    : $typeInfo['type'] = 'long';
                break;

            case Type::operation:
                $typeInfo['type'] = 'float';
                break;

            case Type::text:
                $textInfo = $this->getTextByDataType($dataType);
                $type = 'text';

                if ($textInfo != null) {
                    $regex = $textInfo->getRegex();
                    if ($regex != null && StringTools::regexIsOptionType($regex)) {
                        $type = 'keyword';
                    }
                }

                $typeInfo['type'] = $type;
                break;

            default:
                $typeInfo['type'] = $type->getName();
                break;
        }

        return $typeInfo;
    }

    private function getFrenchAnalyser(): array
    {
        return [
            'analysis' => [
                'filter' => [
                    'french_elision' => [
                        'type' => 'elision',
                        'articles_case' => true,
                        'articles' => [
                            'l', 'm', 't', 'qu', 'n', 's',
                            'j', 'd', 'c', 'jusqu', 'quoiqu',
                            'lorsqu', 'puisqu'
                        ]
                    ],
                    'french_stop' => [
                        'type' => 'stop',
                        'stopwords' => '_french_'
                    ],
                    'french_stemmer' => [
                        'type' => 'stemmer',
                        'language' => 'light_french'
                    ]
                ],
                'analyzer' => [
                    'default' => [
                        'tokenizer' => 'icu_tokenizer',
                        'filter' => [
                            'french_elision',
                            'icu_folding',
                            'lowercase',
                            'french_stop',
                            'french_stemmer'
                        ]
                    ]
                ],
                'normalizer' => [
                    'keyword_normalizer' => [
                        'type' => 'custom',
                        'filter' => [
                            'lowercase',
                            'french_elision'
                        ]
                    ]
                ]
            ]
        ];
    }
}
