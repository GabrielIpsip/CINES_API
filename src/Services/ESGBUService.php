<?php
namespace App\Services;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use App\Entity\Establishments;
use App\Entity\DocumentaryStructures;
use App\Entity\Surveys;
use phpDocumentor\Reflection\Types\Integer;
use App\Entity\PhysicalLibraries;

/**
 * Classe d'acc�s au services REST expos�es dans 
 * 
 * set XDEBUG_CONFIG="idekey=eclipse"
 * @author alexandre Granier <granier@cines.fr>
 *        
 */
class ESGBUService
{
    /**
     * L'url de l'api
     * @var string
     */
    protected $urlEsgbuApi = "http://127.0.0.1:8000/api/";
    
    protected const ESTABLISHMENT_SERVICE = "establishments";
    
    protected const SURVEY_SERVICE = "surveys";
    
    protected const GROUP_SERVICE = "groups";
    
    protected const DATA_TYPE_SERVICE = "data-types";
    
    protected const TEXT_DATA_TYPE_SERVICE = "texts";
    
    protected const NUMBER_DATA_TYPE_SERVICE = "numbers";
    
    protected const OPERATION_DATA_TYPE_SERVICE = "operations";
    
    protected const SURVEY_DATA_TYPE_SERVICE = "survey-data-types";
    
    protected const ESTBLISHMENT_VALUES_SERVICE = "establishment-data-values";
    
    protected const DOCUMENTARY_STRUCTURE_DATA_SERVICE = "documentary-structures";
    
    protected const PHYSICAL_LIBRARY_DATA_SERVICE = "physical-libraries";
    
    protected const DOCUMENTARY_STRUCTURE_VALUES_SERVICE = "documentary-structure-data-values";
    
    protected const PHYSICAL_LIBRARY_VALUES_SERVICE = "physical-library-data-values";
    
    protected const GROUP_INSTRUCTIONS_SERVICE = "group-instructions";
    
    /**
     * Instance du client Http
     * @var HttpClientInterface
     */
    protected $client ;
    
    public function __construct(string $urlApi)
    {
        $this->urlEsgbuApi = $urlApi;
        $this->client = HttpClient::create();
    }
    
    /**
     * Fait appel au service de creation d'un etablissement
     * @param Establishments $establishment
     * @return integer l'identifiant de l'etablissement cree
     */
    public function createEstablishement(Establishments $establishment) {
//         echo "Traitement de ". $establishment->getOfficialName()."\n";
        
        $response = $this->client->request('POST', $this->urlEsgbuApi . self::ESTABLISHMENT_SERVICE, [
            'json' => $this->arrayEncodeEstablishment($establishment),
            'verify_peer' => false
        ]);
        if ($response->getStatusCode() != 201) {
            print($response->getContent(false));
            
            return 0;
        }
        
        $id = json_decode($response->getContent(), true)['id'];
        return $id;
    }
    
    /**
     * Fait appel au service de creation d'une structure documentaire
     * @param DocumentaryStructures $sd
     * @param Integer $idEstablishment
     * @return integer l'identifiant de la SD
     */
    public function createDocumentaryStructure(DocumentaryStructures $sd, $idEstablishment) {
//         echo "Traitement de ". $sd->getOfficialName()."-> $idEstablishment\n";
        //var_dump($this->arrayEncodeDocumentaryStructure($sd, $idEstablishment));
        $response = $this->client->request('POST', $this->urlEsgbuApi . self::DOCUMENTARY_STRUCTURE_DATA_SERVICE, [
            'json' => $this->arrayEncodeDocumentaryStructure($sd, $idEstablishment),
            'verify_peer' => false
        ]);
        if ($response->getStatusCode() != 201) {
            print($response->getContent(false));
            return 0;
        }
        
        $id = json_decode($response->getContent(), true)['id'];
        return $id;
    }
    /**
     * @var integer
     */
    static $cpt = 1;
    
    /**
     * Fait appel au service de creation d'une bibliotheque physique
     * @param PhysicalLibraries $bib
     * @param Integer $idStruct
     * @return integer l'identifiant de la BP
     */
    public function createPhysicalLibrary(PhysicalLibraries $bib, $idStruct) {
        self::$cpt++;
        $response = $this->client->request('POST', $this->urlEsgbuApi . self::PHYSICAL_LIBRARY_DATA_SERVICE, [
            'json' => $this->arrayEncodePhysicalLibrary($bib, $idStruct),
            'verify_peer' => false
        ]);
        if ($response->getStatusCode() != 201) {
            echo self::$cpt . " Erreur sur ". $bib->getOfficialName() . "\n";
            print($response->getContent(false) . "\n");
            return 0;
        }
        
        $id = json_decode($response->getContent(), true)['id'];
        return $id;
    }
    
    /**
     * Cr�ation des enqu�tes annuelles <br />
     * @return integer Identifiant de l'ann�e
     */
    public function createSurvey(Surveys $survey) {
        echo "Creation de l'enquete : " . $survey->getName() . " ";
        $response = $this->client->request('POST', $this->urlEsgbuApi . self::SURVEY_SERVICE, [
            'json' => $this->arrayEncodeSurvey($survey),
            'verify_peer' => false
        ]);
        if ($response->getStatusCode() != 201)
            print($response->getContent(false));
            
        $id = json_decode($response->getContent(), true)['id'];
        echo " ........ Ok\n ";
        return $id;
    }
    
    /**
     * Un groupe de variable
     * @param array<mixed> $group
     * @return integer $idGroup
     */
    public function createGroup($group) {
        echo "Creation du groupe : " . iconv("CP1256", "UTF-8", $group['name']) . " ";
        
        $response = $this->client->request('POST', $this->urlEsgbuApi . self::GROUP_SERVICE, [
            'json' => $this->arrayEncodeGroup($group),
            'verify_peer' => false
        ]);
        
        
        if ($response->getStatusCode() != 201) {
            print($response->getContent(false));
            return 0;
        }
        echo " ........ Ok\n ";
        $id = json_decode($response->getContent(), true)['id'];
        
        return $id;
    }
    
    /**
     * Un groupe de variable
     * @param array<mixed> $groupInstructions
     * @return void
     */
    public function createGroupInstructions($groupInstructions) {
        echo "Ajout des explications pour le groupe : ". $groupInstructions['groupId'] . "\n ";
        
        $response = $this->client->request('POST', $this->urlEsgbuApi . self::GROUP_INSTRUCTIONS_SERVICE, [
            'json' => $this->arrayEncodeGroupInstructions($groupInstructions),
            'verify_peer' => false
        ]);
        
        
        if ($response->getStatusCode() != 201) {
            print($response->getContent(false));
            return ;
        }
        echo " ........ Ok\n ";
        
        return ;
    }
    
    /**
     * Cree un type de donnee<br/>
     * @param array<mixed> $dataType
     * @param Integer $groupId
     * @param Integer $groupOrder
     * @return Integer L'identifiant du type de donnees cree
     */
    public function createDataType($dataType, $groupId, $groupOrder) {
        echo "Creation du type de donnee : " . $dataType['code'] . " ";
        $response = $this->client->request('POST', $this->urlEsgbuApi . self::DATA_TYPE_SERVICE, [
            'json' => $this->arrayEncodeDataType($dataType, $groupId, $groupOrder),
            'verify_peer' => false
        ]);
        
        
        if ($response->getStatusCode() != 201) {
            print($response->getContent(false));
            return 0;
        }
        
        $id = json_decode($response->getContent(), true)['id'];
        echo " ........ Ok\n ";
        
        switch (mb_strtolower($dataType['type'])) {
            case "texte":
                $this->createTextDataType($id, $dataType);
            break;
            case "regex":
                $this->createTextDataType($id, $dataType);
            break;
            case "entier":
                $this->createNumberDataType($id, $dataType);
            break;
            case "dec2":
                $this->createNumberDataType($id, $dataType);
            break;
        }
        
//         if ($dataType['calcule'] != "Saisie")
//             $this->createOperationDataType($id, $dataType['formule']);
        
        return $id;
    }
    
    /**
     * 
     * @param integer $id
     * @param array<mixed> $dataType
     * @return void
     */
    public function createTextDataType($id, $dataType) {
        echo "Ajout de regles pour le type de donnee texte : " . $dataType['code'] . "\n";
        $response = $this->client->request('POST', $this->urlEsgbuApi . self::TEXT_DATA_TYPE_SERVICE, [
            'json' => $this->arrayEncodeTextDataType($id, $dataType),
            'verify_peer' => false
        ]);
        
        if ($response->getStatusCode() != 201) {
            print($response->getContent(false));
            return;
        }
        echo " ........ Ok\n ";
    }
    
    /**
     * 
     * @param integer $id
     * @param array<mixed> $dataType
     * @return void
     */
    public function createNumberDataType($id, $dataType) {
        echo "Ajout de regles pour le type de number : " . $dataType['code'] . "\n";
        $response = $this->client->request('POST', $this->urlEsgbuApi . self::NUMBER_DATA_TYPE_SERVICE, [
            'json' => $this->arrayEncodeNumberDataType($id, $dataType),
            'verify_peer' => false
        ]);
       if ($dataType['code'] == "ColNTheseNum") {
var_dump($this->arrayEncodeNumberDataType($id, $dataType));  
var_dump($dataType);
}
        if ($response->getStatusCode() != 201) {
            print($response->getContent(false));
            var_dump( $this->arrayEncodeNumberDataType($id, $dataType));
            return;
        }
        echo " ........ Ok\n ";
    }
    
    /**
     * Traite les variables calculees pour l'instant seulement la somme.
     * @param integer $id l'identifiant du DataTypes
     * @param string $formule la formule
     * @return void
     */
    public function createOperationDataType($id, $formule) {
        $formule = preg_replace('/[^0-9\.a-z_\+]+/i', '', $formule);
        $formulaSum = "sum("; 
        $tabVar = array();
        if ($formule != null) $tabVar = explode("+", $formule);
        for ($i = 0; $i < count($tabVar) ; $i++) {
            $formulaSum .= $tabVar[$i];
            if (($i + 1) < count($tabVar)) $formulaSum .= ",";
        }
        
        $formulaSum .= ")";
        echo "Creation de l'operation : " .$formulaSum . " pour la variable " . $id . "\n";
        $response = $this->client->request('POST', $this->urlEsgbuApi . self::OPERATION_DATA_TYPE_SERVICE, [
            'json' => [
                "formula" => trim($formulaSum),
                "dataTypeId" => $id
            ],
            'verify_peer' => false
        ]);
        
        
        if ($response->getStatusCode() != 200) {
            print("\n".$response->getContent(false). " Code : " . $response->getStatusCode());
            print ("\nEchec sur operation : ".$id."\n\n");
            print(trim($formulaSum));
            return ;
        }
        
        echo " ........ Ok\n ";
    }
    
    /**
     * 
     * @param integer $idVar
     * @param integer $idSurvey
     * @return void
     */
    public function createSurveyDataType($idVar, $idSurvey) {
        echo "Ajout du type de donnees : " . $idVar . " a l'enquete " . $idSurvey."\n";
        
        $response = $this->client->request('POST', $this->urlEsgbuApi . self::SURVEY_DATA_TYPE_SERVICE, [
            'json' => [
                "active" => "true",
                "dataTypeId" => $idVar,
                "surveyId" => $idSurvey
            ],
            'verify_peer' => false
        ]);
        
        if ($response->getStatusCode() != 201) {
            print($response->getContent(false));
            return ;
        }
    }
    
    /**
     * Ajoute une donnee annuelle d'etablissement
     * @param integer $idEtablissement
     * @param integer $idSurvey
     * @param integer $idDataType
     * @param mixed $value
     * @return void
     */
    public function createEstablishmentDataValue($idEtablissement, $idSurvey, $idDataType, $value) {
        echo "Ajout de la valeur : " . $value . " a l'enquete " . $idSurvey . " pour l'etablissement " . $idEtablissement . " et le type de donnees " . $idDataType;
        $response = $this->client->request('PUT', $this->urlEsgbuApi . self::ESTBLISHMENT_VALUES_SERVICE, [
            'json' => [
                "value" => $value,
                "establishmentId" => $idEtablissement,
                "surveyId" => $idSurvey,
                "dataTypeId" => $idDataType
            ],
            'verify_peer' => false
        ]);
        
        if ($response->getStatusCode() != 201) {
            print($response->getContent(false));
            return ;
        }
        echo " ........ Ok\n ";
    }
    
    /**
     * 
     * @param integer $idDocStruct
     * @param integer $idSurvey
     * @param integer $idDataType
     * @param mixed $value
     * @return void
     */
    public function createDocumentaryStructureDataValue($idDocStruct, $idSurvey, $idDataType, $value) {
        echo "Ajout de la valeur : " . $value . " a l'enquete " . $idSurvey . " pour la structure documentaire " . $idDocStruct . " et le type de donnees " . $idDataType;
        $response = $this->client->request('PUT', $this->urlEsgbuApi . self::DOCUMENTARY_STRUCTURE_VALUES_SERVICE, [
            'json' => [
                "value" => $value,
                "docStructId" => $idDocStruct,
                "surveyId" => $idSurvey,
                "dataTypeId" => $idDataType
            ],
            'verify_peer' => false
        ]);
        
        if ($response->getStatusCode() != 201) {
            print($response->getContent(false));
            return ;
        }
        echo " ........ Ok\n ";
    }
    
    /**
     *
     * @param integer $idBib
     * @param integer $idSurvey
     * @param integer $idDataType
     * @param mixed $value
     * @return void
     */
    public function createPhysicalLibraryDataValue($idBib, $idSurvey, $idDataType, $value) {
        echo "Ajout de la valeur : " . $value . " a l'enquete " . $idSurvey . " pour la bibliotheque physique" . $idBib . " et le type de donnees " . $idDataType;
        $response = $this->client->request('PUT', $this->urlEsgbuApi . self::PHYSICAL_LIBRARY_VALUES_SERVICE, [
            'json' => [
                "value" => $value,
                "physicLibId" => $idBib,
                "surveyId" => $idSurvey,
                "dataTypeId" => $idDataType
            ],
            'verify_peer' => false
        ]);
        
        if ($response->getStatusCode() != 201) {
            print($response->getContent(false));
            return ;
        }
        echo " ........ Ok\n ";
    }
    /**
     * Renvoie une representation
     * @param Establishments $establishment
     * @return string[]|boolean[]|number[]|NULL[]
     */
    public function arrayEncodeEstablishment(Establishments $establishment) {
//         $useName = iconv("UTF-8", "CP1256", $establishment->getOfficialName());
//         $useName = iconv("CP1256", "UTF-8", substr($useName, 0, 50));
        $useName = mb_substr($establishment->getOfficialName(), 0, 50);
        return [
            "officialName"=> $establishment->getOfficialName(),
            "useName"=>   $useName,
            "acronym"=> $establishment->getAcronym(),
            "brand"=> "",
            "active"=> true,
            "address"=> $establishment->getAddress(),
            "city"=> $establishment->getCity(),
            "postalCode"=> $establishment->getPostalCode(),
            "website"=> $establishment->getWebsite(),
            "typeId"=> 1
        ];
    }
    
    
    /**
     * Renvoie une representation 
     * @param DocumentaryStructures $sd
     * @param integer $idEstablishment
     * @return string[]|boolean[]|number[]|NULL[]
     */
    public function arrayEncodeDocumentaryStructure(DocumentaryStructures $sd, $idEstablishment) {
        return [
          "officialName"=> $sd->getOfficialName(),
          "useName"=>  $sd->getOfficialName(),
          "active"=> true,
          "address"=> $sd->getAddress(),
          "city"=> $sd->getCity(),
          "postalCode"=> $sd->getPostalCode(),
          "website"=> $sd->getWebsite(),
          "establishmentId"=> $idEstablishment
         ];
    }
    
    /**
     * Renvoie une representation
     * @param DocumentaryStructures $sd
     * @param integer $idStruct
     * @return string[]|boolean[]|number[]|NULL[]
     */
    public function arrayEncodePhysicalLibrary(PhysicalLibraries $sd, $idStruct) {
        return [
            "officialName"=> $sd->getOfficialName(),
            "useName"=>  $sd->getOfficialName(),
            "active"=> true,
            "fictitious"=> true,
            "address"=> $sd->getAddress(),
            "city"=> $sd->getCity(),
            "postalCode"=> $sd->getPostalCode(),
            "docStructId"=> $idStruct,
            "typeId"=> 1
        ];
    }
    
    /**
     * 
     * @param Surveys $survey
     * @return string[]|number[]
     */
    private function arrayEncodeSurvey(Surveys $survey) {
        return [
            "name" => $survey->getName(),
            "calendarYear" => $survey->getCalendarYear()->format("Y"),
            "dataCalendarYear" => $survey->getDataCalendarYear()->format("Y"),
            "start" => $survey->getStart()->format("Y-m-d H:i:s T"),
            "end" => $survey->getEnd()->format("Y-m-d H:i:s T"),
            "instruction" => "",
            "stateId" => 2 // open
        ];
    }
    
    /**
     * Encode un groupe en un tableau
     * {
     * "parentGroupId": 0,
     * "administrationTypeId": 1,
     * "titles": [
     *  {
     *  "lang": "fr",
     *  "value": "test"
     * }
     * ]
     * } 
     * @param mixed $group
     * @return number[]|string[][][]
     */
    public function arrayEncodeGroup($group) {
        if (!isset($group['parentId'])) $parent = 0;
        else $parent = $group['parentId'];
        
        $type = 0;
        
        if ($group['type'] == "ETAB") {
            $type = 1;
        } else if ($group['type'] == "SD") $type = 3;
        else if ($group['type'] == "Bib") $type = 2;
        
        return [
            "parentGroupId" => $parent,
            "administrationTypeId" => $type,
            "titles" => [[
                "lang" => "fr",
//                 "value"=> iconv("CP1256", "UTF-8", $group['name'])
                "value"=> $group['name']
            ],[
                "lang" => "en",
                "value" => $group['name_en']
            ]]
        ];
    }
    
    /**
     * Encode un groupe en un tableau
     * {
     * "parentGroupId": 0,
     * "administrationTypeId": 1,
     * "titles": [
     *  {
     *  "lang": "fr",
     *  "value": "test"
     * }
     * ]
     * }
     * @param mixed $group
     * @return number[]|string[][][]
     */
    public function arrayEncodeGroupInstructions($groupInstructions) {
        return [
            "groupId" => $groupInstructions['groupId'],
            "surveyId" => $groupInstructions['surveyId'],
            "instructions" => [[
                "lang" => "fr",
                "value"=> $groupInstructions['consigne']
            ]]
        ];
    }
    
    /**
     * 
     * @param array<mixed> $dataType
     * @param integer $groupId
     * @param integer $groupOrder
     * @return string[]|number[]|string[][][]
     */
    public function arrayEncodeDataType($dataType, $groupId, $groupOrder) {
        echo "type : ". $dataType['type'] . " ";
        $idType = 0;
        if ($dataType['type'] == "Texte" || $dataType['type'] == "regex")
            $idType = 1;
            else if ($dataType['type'] == "entier" || $dataType['type'] == "dec2") {
                if ($dataType['calcule'] == "Saisie")
                    $idType = 2;
                    else $idType = 3;
            } else if ($dataType['type'] == "boolean")
                $idType = 4;
            
                if (iconv("CP1256", "UTF-8", $dataType['calcule']) == "Calculée")   
            $idType = 3;
        if ($dataType['calcule'] == "Calculée") $idType = 3;
        return [
                "code" => $dataType['code'],
            "date" => substr($dataType['date'],0, 30),
                "groupOrder" => $groupOrder,
                "groupId" => $groupId,
                "typeId" => $idType,
                "names" => [[
                    "lang" => "fr",
                    "value" =>  $dataType['libelle_fr']
                ],
                    [
                    "lang" => "en",
                        "value" =>  $dataType['libelle_en']
                    ]
                ],
                "definitions" =>[[
                    "lang" => "fr",
                    "value" => $dataType['explicatif_fr']
                ],
                    [
                        "lang" => "en",
                        "value" => $dataType['explicatif_en']
                    ]
                ],
                "instructions" =>[[
                    "lang" => "fr",
                    "value" => $dataType['consigne_fr']
                ]
                ],
                "measureUnits" => [[
                    "lang" => "fr",
                    "value" => $dataType['unite']
                ]]
        ];
    }
    
    /**
     *  
     * @param integer $id l'identifiant du dataType
     * @param mixed $dataType les donnees du dataType
     * @return array<mixed>
     */
    public function arrayEncodeTextDataType($id, $dataType) {
        $textDataType = [];
        foreach (['maxLength', 'minLength', 'regex'] as $field) {
            if (isset($dataType[$field]) && $dataType[$field] != "")
                $textDataType[$field] = $dataType[$field];
        }
       
        $textDataType['dataTypeId'] = $id;
        return $textDataType;
    }
    
    /**
     * 
     * @param integer $id
     * @param array<mixed> $dataType
     * @return boolean[]|string[]
     */
    public function arrayEncodeNumberDataType($id, $dataType) {
        $numberDataType = [];
        foreach(['evolutionMin', 'evolutionMax'] as $field) {
            if (isset($dataType[$field]) && $dataType[$field] != "") {
                
                $numberDataType[$field] = trim($dataType[$field], '-%');
            }
        }
        foreach (['minAlert', 'maxAlert'] as $field) {
            if (isset($dataType[$field]) && $dataType[$field] != "")
                if (isset($dataType['alert']) && $dataType['alert'] == 'N-Bl') {
                    $numberDataType[$field] = str_replace(' ', '', trim($dataType[$field]));
                } 
        }
        
        foreach (['min', 'max'] as $field) {
            if (isset($dataType[$field]) && $dataType[$field] != "") {
                if (isset($dataType['alert']) && $dataType['alert'] == 'Bl') {
                    $numberDataType[$field] = trim($dataType[$field]);
                } 
		if (isset($dataType['alert']) && $dataType['alert'] == 'N-Bl') {
			$numberDataType[$field."Alert"] = preg_replace('/[^0-9\.]+/', '', $dataType[$field]);
		}
	    }
        }
        
        
        if ($dataType['type'] == "dec2") $numberDataType['isDecimal'] = true;
        $numberDataType['dataTypeId'] = $id;
        return $numberDataType;
    }
    
    public function getDataTypes() {
        $response = $this->client->request('GET', $this->urlEsgbuApi . "data-types", ['verify_peer' => false]);
        return $response->toArray();
    }
}

