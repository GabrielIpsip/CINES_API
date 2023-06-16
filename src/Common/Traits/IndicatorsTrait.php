<?php


namespace App\Common\Traits;


use App\Common\Classes\Elasticsearch;
use App\Common\Enum\IndicatorType;
use App\Common\Enum\State;
use App\Entity\Indicators;
use Exception;
use phpDocumentor\Reflection\Types\Object_;
use Psr\Cache\InvalidArgumentException;
use ReflectionClass;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

trait IndicatorsTrait
{

    private $RESULT_KEY = '%RESULT%';
    private $DOC_STRUCT_KEY = '%DOCSTRUCT%';

    /**
     * Get indicator doctrine entity by id.
     * @param int $id Indicator id.
     * @return Indicators Indicator doctrine entity.
     * @throws Exception 404 : No indicator found.
     */
    private function getIndicatorById(int $id): Indicators
    {
        $indicator = $this->managerRegistry->getRepository(Indicators::class)->find($id);
        if (!$indicator)
        {
            throw new Exception('No indicator found with this id : ' . $id, Response::HTTP_NOT_FOUND);
        }
        return $indicator;
    }

    /**
     * Get all indicator in database.
     * @param bool $justActive To get just active indicator.
     * @return array Array of indicators doctrine entity.
     * @throws Exception 404 : No indicator found.
     */
    private function getAllIndicator(bool $justActive = false, bool $noAdministratorOnly = false): array
    {
        $criteria = [];
        if ($justActive)
        {
            $criteria['active'] = true;
        }

        if ($noAdministratorOnly)
        {
            $criteria['administrator'] = false;
        }

        $indicators = $this->managerRegistry->getRepository(Indicators::class)
            ->findBy($criteria, ['displayOrder' => 'ASC']);

        if (count($indicators) === 0)
        {
            throw new Exception('No indicator found', Response::HTTP_NOT_FOUND);
        }
        return $indicators;
    }

    /**
     * Build full query from query of indicator. Add year aggs and/or establishment aggs.
     * @param array $query
     * @return array Full query in the form of key/value array.
     */
    private function addCommonPartOnFullQuery(array $query): array
    {
        $fullQuery = $this->getPerYearAggs($query);
        $fullQuery['size'] = 0;

        return $fullQuery;
    }

    /**
     * Get Per year aggs.
     * @param array|null $subAggs Aggs to add in per year aggs.
     * @return \array[][][] Per year aggs in the form of key/value array.
     */
    private function getPerYearAggs(array $subAggs = null): array
    {
        $aggs =  [
            'aggs' => [
                'perYear' => [
                    'terms' => [
                        'field' => 'year',
                        'size' => 2147483647
                    ]
                ]
            ]
        ];

        if ($subAggs)
        {
            $aggs['aggs']['perYear'] += $subAggs;
        }

        return $aggs;
    }

    /**
     * Get Per institution aggs.
     * @param array|null $subAggs Aggs to add in per institution aggs.
     * @return \array[][] Per institution aggs in the form of key/value array.
     */
    private function getPerEstablishmentAggs(array $subAggs = null): array
    {
        $aggs = [
            'aggs' => [
                'perInstitution' => [
                    'terms' => [
                        'field' => 'id',
                        'size' => 2147483647
                    ],
                    'aggs' => [
                        'institutionUseName' => [
                            'top_hits' => [
                                '_source' => [
                                    'includes' => [
                                        'id',
                                        'useName'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        if ($subAggs)
        {
            $aggs['aggs']['perInstitution']['aggs'] += $subAggs;
        }

        return $aggs;
    }

    /**
     * Get Per institution aggs.
     * @param array|null $subAggs Aggs to add in per institution aggs.
     * @return \array[][] Per institution aggs in the form of key/value array.
     */
    private function getPerRegionAggs(array $subAggs = null): array
    {
        $aggs = [
            'aggs' => [
                'perRegion' => [
                    'terms' => [
                        'field' => 'region',
                        'size' => 2147483647
                    ],
                    'aggs' => [
                        'regionName' => [
                            'top_hits' => [
                                '_source' => [
                                    'includes' => [
                                        'region'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        if ($subAggs)
        {
            $aggs['aggs']['perRegion']['aggs'] += $subAggs;
        }

        return $aggs;
    }

    /**
     * Get per doc struct sub aggregation in elasticsearch query.
     * @param array|null $subAggs Aggregation wich contains doc struct aggregation.
     * @return array|\array[][] New query start to use for per doc struct indicator.
     */
    private function getPerDocStructAggs(array $subAggs = null): array
    {
        $perDocStructAggs = [
            'aggs' => [
                $this->DOC_STRUCT_KEY => [
                    'nested' => [
                        'path' => 'documentaryStructures'
                    ],
                    'aggs' => [
                        'perDocStruct' => [
                            'terms'=> [
                                'field' => 'documentaryStructures.id',
                                'size' => 2147483647
                            ],
                            'aggs' => [
                                'docStructUseName' => [
                                    'top_hits' => [
                                        '_source' => [
                                            'includes' => [
                                                'documentaryStructures.id',
                                                'documentaryStructures.useName'
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        if ($subAggs == null)
        {
            return [];
        }
        else if (array_key_exists($this->DOC_STRUCT_KEY, $subAggs))
        {
            $tmpDocStructAggs = $subAggs[$this->DOC_STRUCT_KEY]['aggs'];
            unset($subAggs[$this->DOC_STRUCT_KEY]);
            $subAggs = $this->replaceInstitutionSumByNestedReverseAggs($subAggs);

            $perDocStructAggs['aggs'][$this->DOC_STRUCT_KEY]['aggs']['perDocStruct']['aggs'] += $subAggs;
            $perDocStructAggs['aggs'][$this->DOC_STRUCT_KEY]['aggs']['perDocStruct']['aggs'] += $tmpDocStructAggs;
        }
        else
        {
            $perDocStructAggs['aggs'][$this->DOC_STRUCT_KEY]['aggs']['perDocStruct']['aggs'] += $subAggs;
        }

        $this->changeBucketPath($perDocStructAggs, 'institution');
        return $perDocStructAggs;
    }

    /**
     * Set institution sum or avg aggregation into nested reverse aggregation.
     * @param array $aggs Institution sum or avg aggregation query.
     * @return array|array[] New aggregation.
     */
    private function replaceInstitutionSumByNestedReverseAggs(array $aggs): array
    {
        $newAggs = [];

        foreach ($aggs as $aggName => $agg)
        {
            $key = null;
            if (array_key_exists('sum', $agg))
            {
                $key = 'sum';
            }
            if (array_key_exists('avg', $agg))
            {
                $key = 'avg';
            }

            if ($key !== null)
            {
                if (!array_key_exists('institution', $newAggs))
                {
                    $newAggs += [
                        'institution' => [
                            'reverse_nested' => new Object_(),
                            'aggs' => [
                                $aggName => [
                                    'max' => [
                                        'field' => $agg[$key]['field']
                                    ]
                                ]
                            ]
                        ]
                    ];
                }
                else
                {
                    $newAggs['institution']['aggs'] += [
                        $aggName => [
                            'max' => [
                                'field' => $agg[$key]['field']
                            ]
                        ]
                    ];
                }
            }
            else
            {
                $newAggs[$aggName] = $agg;
            }
        }

        return $newAggs;
    }

    /**
     * Recursive function to change buckets path in per doc struct indicator query.
     * @param array $resultResponse Per doc struct aggs.
     * @param string $addRoot Add root to new buckets path.
     * @return null If no thing to do.
     */
    private function changeBucketPath(array& $resultResponse, string $addRoot = '')
    {
        foreach ($resultResponse as &$array)
        {
            if (is_array($array))
            {
                if (array_key_exists('buckets_path', $resultResponse))
                {
                    foreach ($array as &$path)
                    {
                        if (str_contains($path, $this->DOC_STRUCT_KEY . '>'))
                        {
                            $path = str_replace($this->DOC_STRUCT_KEY . '>', '', $path);
                        }
                        if (strlen($addRoot) > 0 && !str_contains($path, '>'))
                        {
                            $path = $addRoot . '>' . $path;
                        }
                    }
                }
                else
                {
                    $result = $this->changeBucketPath($array, $addRoot);
                    if ($result)
                    {
                        return $result;
                    }
                }
            }
        }
        return null;
    }


    /**
     * Get result of indicator query.
     * @param Indicators $indicator Indicator doctrine entity.
     * @param array $years To get result for just one year. Empty for all year.
     * @return array Array with query result by year, and also by establishment.
     */
    private function getResults(Indicators $indicator, array $years = []): array
    {
        $result = [];
        $elasticsearch = new Elasticsearch(Elasticsearch::ESTABLISHMENT_INDEX_NAME);

        $query = $indicator->getQuery();
        if ($query == null || count($query) === 0) {
            return $result;
        }

        if ($indicator->getByEstablishment())
        {
            $fullQuery = $this->getPerEstablishmentAggs($query);
            $response = $this->getIndicatorResponse($elasticsearch, $fullQuery, $indicator->getId(),
                IndicatorType::byEstablishment);
            $result[IndicatorType::byEstablishment] = $this->getResultFromEstablishmentResponse($response, $years);
        }

        if ($indicator->getByDocStruct())
        {
            $fullQuery = $this->getPerDocStructAggs($query);
            $response = $this->getIndicatorResponse($elasticsearch, $fullQuery, $indicator->getId(),
                IndicatorType::byDocStruct);
            $result[IndicatorType::byDocStruct] = $this->getResultFromDocStructResponse($response, $years);
        }

        if ($indicator->getByRegion())
        {
            $fullQuery = $this->getPerRegionAggs($query);
            $response = $this->getIndicatorResponse($elasticsearch, $fullQuery, $indicator->getId(),
                IndicatorType::byRegion);
            $result[IndicatorType::byRegion] = $this->getResultFromRegionResponse($response, $years);
        }

        if ($indicator->getGlobal())
        {
            $fullQuery['aggs'] = $query;
            $response = $this->getIndicatorResponse($elasticsearch, $fullQuery, $indicator->getId(),
                IndicatorType::global);
            $result[IndicatorType::global] = $this->getResultFromGlobalResponse($response, $years);
        }

        return $result;
    }

    /**
     * Get elasticsearch response of indicator
     * @param Elasticsearch $elasticsearch elasticsearch client instance.
     * @param array $fullQuery Query to send to elasticsearch.
     * @param int $indicatorId Indicator id with this query.
     * @param string $typeResult IndicatorType result.
     * @return null|array Response of elasticsearch client with this query.
     */
    private function getIndicatorResponse(Elasticsearch $elasticsearch, array $fullQuery, int $indicatorId,
                                          string $typeResult): ?array
    {
        try
        {
            $cache = new FilesystemAdapter();
            $indicatorResponse = $cache->getItem("indicator.$indicatorId.$typeResult");
            if (!$indicatorResponse->isHit())
            {
                $response = $this->getResponse($elasticsearch, $fullQuery);

                $indicatorResponse->set($response);
                $cache->save($indicatorResponse);
                return $response;
            }
            return $indicatorResponse->get();
        }
        catch (Exception | ClientExceptionInterface | RedirectionExceptionInterface | ServerExceptionInterface
            | TransportExceptionInterface | InvalidArgumentException $e)
        {
            return null;
        }
    }

    /**
     * Remove indicator result in cache.
     * @param Indicators[] $indicators Indicator doctrine entity array.
     */
    private function cleanIndicatorCache(array $indicators)
    {
        $cache = new FilesystemAdapter();
        foreach ($indicators as $indicator)
        {
            $indicatorId = $indicator->getId();
            $indicatorTypes = new ReflectionClass(IndicatorType::class);
            $indicatorTypes = $indicatorTypes->getConstants();

            foreach ($indicatorTypes as $indicatorType)
            {
                try
                {
                    $cache->deleteItem("indicator.$indicatorId.$indicatorType");
                }
                catch (InvalidArgumentException $e)
                {
                    // Do nothing
                }
            }
        }
    }

    /**
     * Update indicator result for all active indicator in database.
     * @throws Exception 404 : No indicator found.
     */
    private function updateIndicatorCache() {
        $indicators = $this->getAllIndicator(true);
        $this->cleanIndicatorCache($indicators);
        foreach ($indicators as $indicator) {
            $this->getResults($indicator);
        }
    }

    /**
     * Send request to elasticsearch and return plain response.
     * @param Elasticsearch $elasticsearch Elasticsearch client.
     * @param array $fullQuery Full query to send to elasticsearch.
     * @return array Plain response json encoded in array.
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    private function getResponse(Elasticsearch $elasticsearch, array $fullQuery): array
    {
        $fullQuery = $this->addCommonPartOnFullQuery($fullQuery);
        $request = $elasticsearch->sendQuery('_search', (object)$fullQuery);
        return json_decode($request->getContent(), true);
    }

    /**
     * Get result value in elasticsearch response.
     * @param array|null $response Elasticsearch response of indicator query.
     * @param array $years Year to get in result.
     * @return array|null Array with <result['year']['establishment'] = value> array.
     */
    private function getResultFromEstablishmentResponse(?array $response, array $years): ?array
    {
        if ($response === null)
        {
            return null;
        }

        $result = [];
        foreach ($years as $year)
        {
            $buckets = $response['aggregations']['perYear']['buckets'];
            foreach ($buckets as $bucket)
            {
                if ($bucket['key_as_string'] === $year)
                {
                    $result[$year] = [];
                    $nestedBuckets = $bucket['perInstitution']['buckets'];
                    foreach ($nestedBuckets as $nestedBucket)
                    {
                        $establishmentInfo = $nestedBucket['institutionUseName']['hits']['hits'][0]['_source'];
                        $establishmentInfo['result'] = $this->getResultValueFromResponse($nestedBucket);
                        array_push($result[$year], $establishmentInfo);
                    }
                }
            }
        }
        return $result;
    }

    /**
     * Get result value in elasticsearch response.
     * @param array|null $response Elasticsearch response of indicator query.
     * @param array $years Year to get in result.
     * @return array|null Array with <result['year']['docStruct'] = value> array.
     */
    private function getResultFromDocStructResponse(?array $response, array $years): ?array
    {
        if ($response === null)
        {
            return null;
        }

        $result = [];
        foreach ($years as $year)
        {
            $buckets = $response['aggregations']['perYear']['buckets'];
            foreach ($buckets as $bucket)
            {
                if ($bucket['key_as_string'] === $year)
                {
                    $result[$year] = [];
                    $nestedBuckets = $bucket[$this->DOC_STRUCT_KEY]['perDocStruct']['buckets'];
                    foreach ($nestedBuckets as $nestedBucket)
                    {
                        $docStructInfo = $nestedBucket['docStructUseName']['hits']['hits'][0]['_source'];
                        $docStructInfo['result'] = $this->getResultValueFromResponse($nestedBucket);
                        array_push($result[$year], $docStructInfo);
                    }
                }
            }
        }
        return $result;
    }

    /**
     * Get result value in elasticsearch response.
     * @param array|null $response Elasticsearch response of indicator query.
     * @param array $years Year to get in result.
     * @return array|null Array with <result['year']['region'] = value> array.
     */
    private function getResultFromRegionResponse(?array $response, array $years): ?array
    {
        if ($response === null)
        {
            return null;
        }

        $result = [];
        foreach ($years as $year)
        {
            $buckets = $response['aggregations']['perYear']['buckets'];
            foreach ($buckets as $bucket)
            {
                if ($bucket['key_as_string'] === $year)
                {
                    $result[$year] = [];
                    $nestedBuckets = $bucket['perRegion']['buckets'];
                    foreach ($nestedBuckets as $nestedBucket)
                    {
                        $regionInfo = $nestedBucket['regionName']['hits']['hits'][0]['_source'];
                        $regionInfo['result'] = $this->getResultValueFromResponse($nestedBucket);
                        array_push($result[$year], $regionInfo);
                    }
                }
            }
        }
        return $result;
    }

    /**
     * Get result value in elasticsearch response.
     * @param array|null $response Elasticsearch response of indicator query.
     * @param array $years Year to get in result.
     * @return array|null Array with <result['year']['global'] = value> array.
     */
    private function getResultFromGlobalResponse(?array $response, array $years): ?array
    {
        if ($response === null)
        {
            return null;
        }

        $result = [];
        foreach ($years as $year)
        {
            $buckets = $response['aggregations']['perYear']['buckets'];
            foreach ($buckets as $bucket)
            {
                if ($bucket['key_as_string'] === $year)
                {
                    $result[$year] = $this->getResultValueFromResponse($bucket);
                }
            }
        }
        return $result;
    }


    /**
     * Search result value recursively in Elasticsearch response.
     * @param array $resultResponse Elasticsearch response in form of array key/value pair.
     * @return mixed|null Value of response : number.
     */
    private function getResultValueFromResponse(array $resultResponse)
    {
        foreach ($resultResponse as $array)
        {
            if (is_array($array))
            {
                if (array_key_exists($this->RESULT_KEY, $resultResponse))
                {
                    return $resultResponse[$this->RESULT_KEY]['value'];
                }
                else
                {
                    $result = $this->getResultValueFromResponse($array);
                    if ($result)
                    {
                        return $result;
                    }
                }
            }
        }
        return null;
    }

    /**
     * Get all data calendar year for published survey.
     * @return array Array of timestamp.
     * @throws Exception 404 : No survey found with the published state.
     */
    private function getAllDataCalendarYearPublished(): array
    {
        if ($this->dataCalendarYearPublished == null)
        {
            $this->dataCalendarYearPublished = $this->getAllDataCalendarYearByState(State::PUBLISHED);
        }
        return $this->dataCalendarYearPublished;
    }

    /**
     * Update description of indicator or create it if not exists.
     * @param Indicators $existingIndicator Indicator whose description are to be updated.
     * @param array|null $descriptions Array with descriptions values. ex : $description['fr'] = 'french description'.
     * @throws Exception 404 : If language code not found.
     *                   400 : If instruction array does not contain the default lang value.
     */
    private function updateOrCreateDescription(Indicators $existingIndicator, ?array $descriptions)
    {
        $existingDescription = $existingIndicator->getDescription();
        if ($existingDescription)
        {
            $this->updateTranslation($descriptions, $existingDescription, self::TABLE_NAME);
        }
        else
        {
            $descriptionContent = $this->addTranslation($descriptions, self::TABLE_NAME);
            $existingIndicator->setDescription($descriptionContent);
        }
    }

    /**
     * Update all display order of indicator in argument, to avoid conflicts.
     * @param Indicators $indicator Indicator with the new display order.
     */
    private function createDisplayOrderForEachIndicator(Indicators $indicator)
    {
        $indicatorSameOrder = $this->managerRegistry->getRepository(Indicators::class)
            ->findOneBy(array('displayOrder' => $indicator->getDisplayOrder()));
        if ($indicatorSameOrder && $indicatorSameOrder->getId() !== $indicator->getId())
        {
            $allIndicators = $this->managerRegistry->getRepository(Indicators::class)->findAll();
            foreach ($allIndicators as $i)
            {
                if ($i->getDisplayOrder() >= $indicator->getDisplayOrder() && $i->getId() !== $indicator->getId())
                {
                    $i->setDisplayOrder($i->getDisplayOrder() + 1);
                }
            }
        }
    }

    /**
     * Check if other indicator get display order of new indicator value, and switch value between two indicator.
     * @param Indicators $indicator Indicator new value.
     * @param Indicators $existingIndicator Indicator existing value in database.
     */
    private function updateDisplayOrderForEachIndicator(Indicators $indicator, Indicators $existingIndicator)
    {
        $indicatorSameOrder = $this->managerRegistry->getRepository(Indicators::class)
            ->findOneBy(array('displayOrder' => $indicator->getDisplayOrder()));
        if ($indicatorSameOrder && $indicatorSameOrder->getId() !== $existingIndicator->getId())
        {
            $currentDisplayOrder = $existingIndicator->getDisplayOrder();
            $indicator->setDisplayOrder($indicatorSameOrder->getDisplayOrder());
            $indicatorSameOrder->setDisplayOrder($currentDisplayOrder);
        }
    }
}
