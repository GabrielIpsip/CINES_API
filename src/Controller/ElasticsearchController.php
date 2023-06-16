<?php


namespace App\Controller;


use App\Common\Classes\Elasticsearch;
use Exception;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use stdClass;
use Swagger\Annotations as SWG;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Controller\AbstractController\ESGBUController;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * Class ElasticsearchController
 * @package App\Controller
 * @SWG\Tag(name="Elasticsearch")
 */
class ElasticsearchController extends ESGBUController
{
    /** Send query to Elasticsearch
     * @SWG\Response(
     *     response="200",
     *     description="Return elasticsearch query result."
     * )
     * @SWG\Parameter(name="body", in="body", description="Elasticsearch request",
     *     @SWG\Schema(type="object",
     *      @SWG\Property(property="elasticsearchIndex", type="string"),
     *      @SWG\Property(property="elasticsearchRequest", type="string"),
     *      @SWG\Property(property="elasticsearchBody", type="object"))
     * )
     * @Rest\Post(
     *      path = "/public/elasticsearch",
     *      name = "app_public_elasticsearch_show_post"
     * )
     * @Rest\RequestParam(name="elasticsearchIndex", nullable=true)
     * @Rest\RequestParam(name="elasticsearchRequest", nullable=true)
     * @Rest\RequestParam(name="elasticsearchBody", nullable=true)
     * @Rest\View
     * @param string|null $elasticsearchIndex Index name.
     * @param string|null $elasticsearchRequest Request into url request.
     * @param Request $request
     * @return View Elasticsearch response.
     */
    public function showActionPost(?string $elasticsearchIndex, ?string $elasticsearchRequest, Request $request): View
    {
        $response = null;
        try
        {
            $elasticsearchBody = json_decode($request->getContent())->elasticsearchBody;
            if ($elasticsearchRequest == null || $elasticsearchIndex == null) {
                throw new Exception();
            }
        }
        catch (Exception $e)
        {
            return $this->createView($this->getExplanation(), Response::HTTP_BAD_REQUEST);
        }

        try
        {
            $elasticsearch = new Elasticsearch($elasticsearchIndex);

            if ($elasticsearchRequest === Elasticsearch::MAPPING_KEY)
            {
                return $this->createView($elasticsearch->getMappingFromFile(), Response::HTTP_OK);
            }
            else
            {
                $response = $elasticsearch->sendQuery($elasticsearchRequest, $elasticsearchBody);
            }
            return $this->createView(json_decode($response->getContent(false), true), $response->getStatusCode());
        }
        catch (ClientExceptionInterface | RedirectionExceptionInterface | ServerExceptionInterface
        | TransportExceptionInterface | Exception $e) {
            return $this->createView('Error to execute query : ' . $e->getMessage(),
                Response::HTTP_BAD_REQUEST);
        }
    }

    /** Send query to Elasticsearch
     * @SWG\Response(
     *     response="200",
     *     description="Return elasticsearch query result."
     * )
     * @SWG\Parameter(name="body", in="body", description="Elasticsearch request",
     *     @SWG\Schema(type="object",
     *      @SWG\Property(property="elasticsearchIndex", type="string"),
     *      @SWG\Property(property="elasticsearchRequest", type="string"),
     *      @SWG\Property(property="elasticsearchBody", type="object"))
     * )
     * @Rest\Get(
     *      path = "/public/elasticsearch",
     *      name = "app_public_elasticsearch_show_get"
     * )
     * @param Request $request
     * @return View
     */
    public function showActionGet(Request $request): View
    {
            try
            {
                $elasticsearchIndex = json_decode($request->getContent())->elasticsearchIndex;
                $elasticsearchRequest = json_decode($request->getContent())->elasticsearchRequest;

                return $this->showActionPost($elasticsearchIndex, $elasticsearchRequest, $request);
            }
            catch (Exception $e)
            {
                return $this->createView($this->getExplanation(), Response::HTTP_BAD_REQUEST);
            }
    }

    public function getExplanation(): array
    {
        return [
            'EXAMPLE BODY' => [
                'elasticsearchIndex' => 'esgbu_institutions',
                'elasticsearchRequest' => '_search',
                'elasticsearchBody' => [
                    'query' => [
                        'match_all' => new stdClass()
                    ]
                ]
            ]
        ];
    }
}
