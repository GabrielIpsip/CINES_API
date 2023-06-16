<?php
namespace App\Script;

/**
 * Classe pour migrer les donnees annuelles ds etablissements<br/>
 * @author alexandre Granier <granier@cines.fr>
 *        
 */
class InstitutionAnnualDataService extends OpenDataESRService
{
    public const DATA_SET = self::URL_OPEN_API . "fr-esr-sise-effectifs-d-etudiants-inscrits-esr-public";
    
    public const TEACHER_DATA_SET = self::URL_OPEN_API . "fr-esr-enseignants-titulaires-esr-public";
    
    public const NON_TITULAR_TEACHER_DATA_SET = self::URL_OPEN_API . "fr-esr-enseignants-nonpermanents-esr-public";
    
    public const URL_AGGREGATE_API = "https://data.enseignementsup-recherche.gouv.fr/api/v2/catalog/datasets/";
    
    /**
     * Valeur par defaut
     * @var string URL de l'API
     */
    protected $urlApi = "http://127.0.0.1:8000/api/";
    
    /**
     * @param string $urlApi
     */
    public function __construct($urlApi)
    {
        $this->urlApi = $urlApi;
        parent::__construct();
    }
    
    /**
     * 
     * @param string $uai
     * @param int $year
     * @return number[]|mixed[]|number[]|mixed[]
     */
    public function getData($uai, $year) {
        echo "Recuperation des donnees annuelles $year de l'etablissement $uai\n";
        $result = array();
        
        $result['students'] = $this->getNumberOfStudentsByYear();
        $result['titular'] = $this->getNumberOfTitularTeachers();
        $result['non_titular'] = $this->getNumberOfNonTitularTeachers();
        return $result;
    }
    /**
     * 
     * @param string $uai code UAI $uai
     * @param String $level
     * @param int $nbResult
     * @param int $year
     * @return int Le nombre d'etudiant
     */
     private function getNumberOfStudents(string $uai, $level, $nbResult, $year)
    {
        // https://data.enseignementsup-recherche.gouv.fr/api/v2/catalog/datasets/fr-esr-sise-effectifs-d-etudiants-inscrits-esr-public/
        //aggregates?select=count(effectif)%20as%20effectif&group_by=etablissement%2Cetablissement_lib%2Ccursus_lmd%2Crentree
        $filtres = "&refine.etablissement=$uai&refine.cursus_lmd=".$level."&refine.rentree_lib=".$year;
        
        $url = self::DATA_SET . '&rows=' . $nbResult . $filtres;

        $response = $this->client->request('GET', $url);
        //$content = $response->getContent();
        $content = $response->toArray();
        
        $sum = 0;
        
        for ($i = 0; $i < $nbResult; $i++) {
            $effectif =  $content['records'][$i]['fields']['effectif'] ;
            if (isset($effectif)) {
                $sum += $effectif;
            }
        }
        unset($content);
        return $sum;
    }
    
    /**
     * 
     * @return mixed|number|mixed
     */
    public function getNumberOfStudentsByYear()
    {
        // https://data.enseignementsup-recherche.gouv.fr/api/v2/catalog/datasets/fr-esr-sise-effectifs-d-etudiants-inscrits-esr-public/
        //aggregates?select=count(effectif)%20as%20effectif&group_by=etablissement%2Cetablissement_lib%2Ccursus_lmd%2Crentree
        $url = self::URL_AGGREGATE_API . 'fr-esr-sise-effectifs-d-etudiants-inscrits-esr-public/';
        $url .= 'aggregates?select=sum(effectif)%20as%20effectif&group_by=operateur%2Coperateur_lib%2Ccursus_lmd%2Crentree';
        $response = $this->client->request('GET', $url);
      
        $content = $response->toArray();
        return $content['aggregations'];
    }
    
    /**
     * Renvoie le nombre d'enseignant titulaire
     
     * @param string $uai code UAI $uai
     * @param int $nbResult
     * @param int $year
     * @param string $type Maitre de conference ou Professeur
     * @return number|mixed
     */
    private function getNumberOfNonPermanent($uai, $nbResult, $year, $type)
    {
        $filtres = "&refine.etablissement=$uai&refine.rentree=".$year."&refine.code_categorie_assimil=$type";
        $url = self::TEACHER_DATA_SET . '&rows=' . $nbResult . $filtres;
        $response = $this->client->request('GET', $url);
        //$content = $response->getContent();
        $content = $response->toArray();
        
        $sum = 0;
        
        for ($i = 0; $i < $nbResult; $i++) {
            $effectif =  $content['records'][$i]['fields']['effectif'] ;
            if (isset($effectif)) {
                $sum += $effectif;
            }
        }
        unset($content);
        return $sum;
    }
    
    /**
     * Renvoie le nombre d'enseignant titulaire 
     * @return mixed
     */
    public function getNumberOfTitularTeachers()
    {
        // https://data.enseignementsup-recherche.gouv.fr/api/v2/catalog/datasets/fr-esr-enseignants-titulaires-esr-public/
        //aggregates?select=count(effectif)%20as%20effectif&group_by=etablissement%2Cetablissement_lib%2Crentree%2Ccode_categorie_assimil&where=code_categorie_assimil%3D%22MCF%22%20or%20code_categorie_assimil%3D%22PR%22
        $url = self::URL_AGGREGATE_API . 'fr-esr-enseignants-titulaires-esr-public/';
        $url .= 'aggregates?select=sum(effectif)%20as%20effectif&group_by=etablissement%2Cetablissement_lib%2Crentree&where=code_categorie_assimil%3D%22MCF%22%20or%20code_categorie_assimil%3D%22PR%22';
        $response = $this->client->request('GET', $url);
        $content = $response->toArray();
        return $content['aggregations'];
    }
    
    /**
     * @return mixed
     */
    public function getNumberOfNonTitularTeachers()
    {
        
        $url = self::URL_AGGREGATE_API . 'fr-esr-enseignants-nonpermanents-esr-public/';
        $url .= 'aggregates?select=sum(effectif)%20as%20effectif&group_by=etablissement%2Crentree&where=categorie_personnels%3D%22Ma%C3%AEtre%20de%20conf%C3%A9rences%20associ%C3%A9%20ou%20invit%C3%A9%22%20%20or%20categorie_personnels%3D%22Professeur%20des%20unversit%C3%A9s%20associ%C3%A9%20ou%20invit%C3%A9%22';
        
        $response = $this->client->request('GET', $url);
        $content = $response->toArray();
        
        return $content['aggregations'];
    }
    
    
    /**
     * Renvoie le nombre de ligne
     * @param string $query
     * @return integer Le nombre de resultat
     */
    public function getNumberOfResults($query) {
        $response = $this->client->request('GET', $query);
        
        $statusCode = $response->getStatusCode();
        
        $content = $response->getContent();
        $content = $response->toArray();
        $res = $content["nhits"];
        unset($content);
        return $res;
    }
}

