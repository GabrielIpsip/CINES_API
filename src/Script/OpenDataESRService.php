<?php
namespace App\Script;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Super-classe des services appelant une API Open data de l'ESR
 * @author alexandre Granier <granier@cines.fr>
 *        
 */
abstract class OpenDataESRService
{

    protected const URL_OPEN_API = "https://data.enseignementsup-recherche.gouv.fr/api/records/1.0/search/?dataset=";
    
    /**
     * Interface d'acces a HttpClient
     * @var HttpClientInterface 
     */
    protected $client;
    
    public function __construct()
    {
        $this->client = HttpClient::create();
    }

}

