<?php


namespace App\Common\Classes;

use Exception;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class Elasticsearch
{

    const PHYSIC_LIB_INDEX_NAME = 'esgbu_physical_libraries';
    const DOC_STRUCT_INDEX_NAME = 'esgbu_documentary_structures';
    const ESTABLISHMENT_INDEX_NAME = 'esgbu_institutions';

    const AUTHORIZED_REQUEST = ['_search'];
    public const MAPPING_KEY = '_mapping';

    /**
     * @var string Elasticsearch url.
     */
    public $url;
    /**
     * @var array Http request option for admin user.
     */
    private $adminOptions;
    /**
     * @var array Http request option for client user.
     */
    private $clientOptions;
    /**
     * @var HttpClientInterface Symfony http client to send request to elasticsearch.
     */
    private $httpClient;

    /**
     * @var string Name of current index.
     */
    private $index;

    /**
     * Elasticsearch constructor.
     * @param string $index Index name to use.
     * @throws Exception 404 : Index not found.
     */
    public function __construct(string $index)
    {
        $this->initIndex($index);
        $this->initElasticsearchUrl();
        $this->initElasticsearchClientOption();
        $this->initElasticsearchAdminOption();
        $this->initElasticsearchHttpClient();
    }

    /**
     * Return elasticsearch Url from .env.local with slash at the end.
     */
    private function initElasticsearchUrl()
    {
        $elasticsearchUrl = $_ENV['ELASTICSEARCH_URL'];
        $this->url = str_ends_with('/', $elasticsearchUrl)
            ? $elasticsearchUrl
            : $elasticsearchUrl . '/';
    }

    /**
     * Elasticsearch options to add for admin request.
     */
    private function initElasticsearchAdminOption()
    {
        $this->adminOptions =  [
            'auth_basic' => [
                $_ENV['ELASTICSEARCH_USERNAME_ADMIN'],
                $_ENV['ELASTICSEARCH_PASSWORD_ADMIN']
            ],
            'verify_peer' => false
        ];
    }

    /**
     * Elasticsearch options to add for client request.
     */
    private function initElasticsearchClientOption()
    {
        $this->clientOptions =  [
            'auth_basic' => [
                $_ENV['ELASTICSEARCH_USERNAME_CLIENT'],
                $_ENV['ELASTICSEARCH_PASSWORD_CLIENT']
            ],
            'verify_peer' => false
        ];
    }

    /**
     * Httpclient to send request to elasticsearch.
     */
    private function initElasticsearchHttpClient()
    {
        $this->httpClient = HttpClient::create(['headers' => [
            'Content-Type' => 'application/json'
        ]]);
    }

    /**
     * Initialize index to use.
     * @param string $index Index name.
     * @throws Exception 404 : Index not found.
     */
    private function initIndex(string $index)
    {
        if ($index === self::ESTABLISHMENT_INDEX_NAME ||
            $index === self::DOC_STRUCT_INDEX_NAME ||
            $index === self::PHYSIC_LIB_INDEX_NAME)
        {
            $this->index = $index;
        }
        else
        {
            throw new Exception('Index not found.', Response::HTTP_NOT_FOUND);
        }
    }

    /**
     * Delete index in elasticsearch.
     * @return ResponseInterface Http client response.
     * @throws TransportExceptionInterface Error to delete index.
     */
    public function deleteIndex(): ResponseInterface
    {
        $response = $this->httpClient->request(
            'DELETE', $this->url . $this->index,
            $this->adminOptions
        );
        if ($response->getStatusCode() === Response::HTTP_NOT_FOUND) {
            return $response;
        }
        return $response;
    }

    /**
     * Insert mapping into elasticsearch database.
     * @param string $mappingPath Path to file that contains mapping.
     * @return ResponseInterface Http client response.
     * @throws TransportExceptionInterface Error to insert mapping for this index.
     */
    public function setMapping(string $mappingPath): ResponseInterface
    {
        $mappingFile = fopen($mappingPath, 'r');
        $response = $this->httpClient->request(
            'PUT', $this->url . $this->index,
            array_merge(['body' => $mappingFile], $this->adminOptions)
        );

        if ($response->getStatusCode()) // Wait response.
        {
            fclose($mappingFile);
        }
        return $response;
    }

    /**
     * Feed index with data. Data must be match with index mapping.
     * @param string $indexValueFilePath Path to file that contains data.
     * @return ResponseInterface Http client response.
     * @throws TransportExceptionInterface Error to insert data.
     */
    public function feedIndex(string $indexValueFilePath): ResponseInterface
    {
        $indexFile = fopen($indexValueFilePath, 'r');
        $response = $this->httpClient->request(
            'PUT', $this->url . '_bulk',
            array_merge(['body' => $indexFile], $this->adminOptions)
        );

        if ($response->getStatusCode()) // Wait response.
        {
            fclose($indexFile);
        }
        return $response;
    }

    /**
     * To send query to elasticsearch and get response.
     * @param string $request Request into url.
     * @param object|null $body Body of the request.
     * @return ResponseInterface Http client response. Data corresponding to query.
     * @throws TransportExceptionInterface Error to execute the query.
     * @throws Exception 403 : Request not authorized.
     */
    public function sendQuery(string $request, $body): ResponseInterface {
        if ($body == null)
        {
            $body = '{}';
        }
        else
        {
            $body = json_encode($body);
        }

        $request = trim($request, '/');
        $isAuthorized = false;

        foreach (self::AUTHORIZED_REQUEST as $authorizedRequest)
        {
            if (str_starts_with($request, $authorizedRequest))
            {
                $isAuthorized = true;
                break;
            }
        }

        if (!$isAuthorized)
        {
            throw new Exception('Request not authorized', Response::HTTP_FORBIDDEN);
        }

        return $this->httpClient->request(
            'GET', $this->url . $this->index . '/' . $request,
            array_merge(['body' => $body], $this->clientOptions)
        );
    }

    /**
     * Get mapping from database export mapping file.
     * @return array Key/value array representing the mapping.
     */
    public function getMappingFromFile(): array
    {
        $mapping = file_get_contents("database_export/elasticsearch/mapping_$this->index.json");
        return json_decode($mapping, true);
    }
}
