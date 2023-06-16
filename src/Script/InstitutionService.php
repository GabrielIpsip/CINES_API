<?php
namespace App\Script;

use App\Entity\Establishments;
use App\Services\ESGBUService;

/**
 * Wrapper vers le service opendata ESR: <br />
 * Principaux �tablissements d'enseignement sup�rieur
 * @author alexandre Granier <granier@cines.fr>
 *        
 */
class InstitutionService extends OpenDataESRService
{
    public const DATA_SET = self::URL_OPEN_API . "fr-esr-principaux-etablissements-enseignement-superieur";
   
    public const SD_FILE = "src/Resources/sd_info_generales.csv";
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
     * @return string[]|\App\Entity\Establishments[]|mixed[]
     */
    public function getEstablishments() {
        $urlOpenDataEstablishment = self::DATA_SET . '&rows=266&facet=uai&facet=type_d_etablissement&facet=com_nom&facet=dep_nom&facet=aca_nom&facet=reg_nom&facet=pays_etranger_acheminement';
        echo "Requete vers : ". $urlOpenDataEstablishment . "\n";
        $response = $this->client->request('GET', $urlOpenDataEstablishment);
        
        $statusCode = $response->getStatusCode();
       
        $contentType = $response->getHeaders()['content-type'][0];
        $content = $response->getContent();
        $content = $response->toArray();
        
        $totalInstitution = $content["nhits"];
        echo "Nombre d'institution total : " . $totalInstitution . "\n";
        $nbResult = $content["parameters"]["rows"];
        echo "Nombre de lignes : " . $nbResult. "\n";
        
        $establishments = array();
        for ($i = 0; $i < $nbResult; $i++) {
            $currentInstitution = $content["records"][$i]["fields"];
//             var_dump($currentInstitution);
            $establishment = new Establishments();
            $establishment->setOfficialName($currentInstitution['uo_lib']);
            if (isset($currentInstitution['sigle'])) $establishment->setAcronym($currentInstitution['sigle']); 
            else {
                echo "WARN : La sigle n'existe pas pour  ".$establishment->getOfficialName()."\n";
            }
            
            if (isset($currentInstitution['url']) && filter_var($currentInstitution['url'], FILTER_VALIDATE_URL)) $establishment->setWebsite($currentInstitution['url']);
            else {
                echo "WARN : Le site web n'existe pas pour  ".$establishment->getOfficialName()."\n";
                $establishment->setWebsite("http://etablissement.fr");
            }
            
            if (isset($currentInstitution['adresse_uai'])) $establishment->setAddress($currentInstitution['adresse_uai']);
            else {
                echo "WARN : L'adresse n'est pas renseign� pas pour  ".$establishment->getOfficialName()."\n";
                $establishment->setAddress("-");
            }
            if (isset($currentInstitution['code_postal_uai'])) $establishment->setPostalCode($currentInstitution['code_postal_uai']);
            else {
                echo "WARN : Le code postal n'est pas renseign� pas pour  ".$establishment->getOfficialName()."\n";
                $establishment->setPostalCode("00000");
            }
            
            if (isset($currentInstitution['localite_acheminement_uai'])) $establishment->setCity($currentInstitution['localite_acheminement_uai']);
            else {
                echo "WARN : La ville n'est pas renseign�e pas pour  ".$establishment->getOfficialName()."\n";
                $establishment->setCity("-");
            }
            $establishments[$i]['establishment'] = $establishment;
            if (isset($currentInstitution['uai'])) $establishments[$i]['uai'] = $currentInstitution['uai'];
            else $establishments[$i]['uai'] = "";
        }
        
        
        $esgbuService = new ESGBUService($this->urlApi);
        
        $institutionAnnualDataService = new InstitutionAnnualDataService($this->urlApi);
        
        $ESGBUEstablishments = array();
        
        foreach ($establishments as $e) {
            echo "Etablissement " . $e['establishment']->getOfficialName().'  UAI : ' . $e['uai'] . "\n";
            $id = $esgbuService->createEstablishement($e['establishment']);
            $ESGBUEstablishments[$id] = $e; 
        }
        
        return $ESGBUEstablishments;    
    }
}

