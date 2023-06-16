<?php
namespace App;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

use App\Script\InstitutionService;
use App\Services\ESGBUService;
use App\Entity\Surveys;
use App\Entity\States;
use App\Entity\DocumentaryStructures;
use App\Script\DataDictionnaryParser;
use App\Script\InstitutionAnnualDataService;
use App\Entity\PhysicalLibraries;

class Migration extends Command {
    
    protected static $defaultName = "app:migration";
    
    /**
     * Valeur par défaut
     * @var string
     */
    protected $urlApi = "http://127.0.0.1:8000/api/";
    
    public const SD_FILE = "src/Resources/sd_info_generale.csv";
    
    public const BP_FILE = "src/Resources/Export_BP_2018_V3.csv";
    
    public const SD_DATA_VALUES_FILE_PATTERN = "sd_data_xxxx.csv";
    
    public const BIB_DATA_VALUES_FILE_PATTERN = "Export_BP_xxxx_yearlydata.csv";
    
    /**
     * 
     * @var array
     */
    protected $annualVarTab;
    
    /**
	* @return void
	*/
    protected function configure() {
        
        $this->setDescription("Lance la migration des données ESGBU");
        
        $this->addArgument('url_esgbu_api', InputArgument::OPTIONAL, 'L\'adresse de l\'API ESGBU.');    
    }
    
    protected function execute(InputInterface $input, OutputInterface $output) {
        $output->writeln("Début de la migration");
       
        $urlArg =  $input->getArgument('url_esgbu_api') ? $input->getArgument('url_esgbu_api') : $this->urlApi;
        if (is_string($urlArg)) {
            $urlApi = $urlArg; 
        } else {
            echo "Veuillez indiquer une URL pour l'API\n";
            exit;
        }
        
        $esgbuService = new ESGBUService($urlApi);
        
//         $dataTypes = $esgbuService->getDataTypes();
//         for ($i = 0; $i < count($dataTypes); $i++)
//             $this->annualVarTab[$dataTypes[$i]['code']] = $dataTypes[$i]['id'];
//         $this->importBibData(array('2018' => 6), $esgbuService);
        
//         exit;

        $institutionService = new InstitutionService($input->getArgument('url_esgbu_api'));
        
        $establishments = $institutionService->getEstablishments();
        
       
        echo "Chargement du fichier structure documentaire ".self::SD_FILE."\n";
        
        $documentaryStructures = array();
        
        $i = 0;
        if (($handle = fopen(self::SD_FILE, "r")) !== FALSE) {
            while (($line = stream_get_line($handle, 4096, "\n")) !== FALSE) {
                $data = str_getcsv($line, ";");
                $uaiEtab = $data[12];
                $sd = new DocumentaryStructures();
                $sd->setOfficialName($data[2]);
                $sd->setUseName($data[2]);
                if ($data[3] == "") $data[3] = "--";
                $sd->setAddress($data[3]);
                if ($data[4] == "") $data[4] = "75000";
                $sd->setPostalCode($data[4]);
                if ($data[5] == "") $data[5] = "Paris";
                $sd->setCity($data[5]);
                $sd->setWebsite("http://www.fr");
                $sd->setActive(true);
                $documentaryStructures[$i++] = array("sd" =>$sd, "uaiEtab" => $uaiEtab);
                
            }
        }
        
        
        
        $cpt = 0;
        foreach ($documentaryStructures as $idYear => $sd) {
            echo "Structure documentaire : ". $documentaryStructures[$idYear]['sd']->getOfficialName() . "\n";
            // Recherche de l'etablissement associe
            $idEtab = 1000;
            foreach ($establishments as $idE =>$e) {
                //var_dump($e);exit;
                if ($e['uai'] == $documentaryStructures[$idYear]['uaiEtab']) {
                    $idEtab = $idE;
                    break;
                }
            }
            if ($idEtab == 1000) $idEtab = 1;
            $esgbuService->createDocumentaryStructure($documentaryStructures[$idYear]['sd'], $idEtab);
        }
        
        $physicalLibraries = array();
        $i = 0;
        if (($handle = fopen(self::BP_FILE, "r")) !== FALSE) {
            while (($line = stream_get_line($handle, 4096, "\n")) !== FALSE) {
                $data = str_getcsv($line, ";");
                if ($i++ ==0) continue; // On saute la premiere ligne
                $bib = new PhysicalLibraries();
                $bib->setOfficialName($data[8]);
                $bib->setUseName($data[8]);
                if ($data[11] == "") $data[11] = "--";
                $bib->setAddress($data[11]);
                if ($data[12] == "" || !preg_match ("/^[0-9]{5,5}$/ " , $data[12])) $data[12] = "00000";
                $bib->setPostalCode($data[12]);
                if ($data[13] == "") $data[13] = "Paris";
                $bib->setCity($data[13]);
                if ($data[9] != "" && $data[9] == "active") $data[9] = true; else $data[9] = false;
                $bib->setActive($data[9]);
                $physicalLibraries[$i++] = array("bib" =>$bib, "docStructId" => $data[1], "type" => $data[3]);
                
            }
        }
        $i=0;
        foreach ($physicalLibraries as $idYear => $bib) {
            echo "Bibliotheque physique: ". $physicalLibraries[$idYear]['bib']->getOfficialName() . "\n";
            $esgbuService->createPhysicalLibrary($physicalLibraries[$idYear]['bib'], $physicalLibraries[$idYear]['docStructId']);
        }
        
        $output->writeln("Création des enquêtes annuelles");
        $annualSurveys = array();
        // Création des enquêtes annuelles
        for ($year = 2013; $year <= 2019; $year++) {
            $survey = new Surveys();
            $name = 'Enquête ';
            if ($year == 2019) $name .= 'de test ';
            $survey->setName($name . $year);
            $survey->setCalendarYear(new \DateTime(($year + 1).'-01-01T00:00:00'));
            $survey->setDataCalendarYear(new \DateTime($year.'-01-01T00:00:00'));
            $survey->setState(new States());
            $survey->setUTCStart(new \DateTime($year.'-06-01T00:00:00'));
            $survey->setUTCEnd(new \DateTime($year.'-09-01T00:00:00'));
            
            $idYear = $esgbuService->createSurvey($survey);
            
            $annualSurveys[$year] = $idYear;
            sleep(1);
        }
        
        $output->writeln("Chargement du dictionnaire des données");
        $dataDictionnaryParser = new DataDictionnaryParser();
        $groups = $dataDictionnaryParser->parse();
        
        for ($i = 1; $i <= count($groups); $i++) {
            $idGroup =  $esgbuService->createGroup($groups[$i]);
            
            // Boucle sur les variables
            for ($j = 0; $j < count($groups[$i]['vars']); $j++) {
                $idVar = $esgbuService->createDataType($groups[$i]['vars'][$j], $idGroup, $j + 1);
                $groups[$i]['vars'][$j]['id'] = $idVar;
                $code = $groups[$i]['vars'][$j]['code'];
                $this->annualVarTab[$code] = $idVar;
                foreach ($annualSurveys as $year => $idSurvey) { // A conditionner
//                     var_dump($groups[$i]['vars'][$j]);
                    $y = 'y'.$year;
                    if (!isset($groups[$i]['vars'][$j][$y]) || $groups[$i]['vars'][$j][$y] != "FAUX") {
                        
                        $esgbuService->createSurveyDataType($idVar, $idSurvey);
                    }
                }
            }
            
        }
        
        // Traitement des operations 
        for ($i = 1; $i <= count($groups); $i++) {
            for ($j = 0; $j < count($groups[$i]['vars']); $j++) {
                if ($groups[$i]['vars'][$j]['calcule'] != "Saisie") {
                    $esgbuService->createOperationDataType( $groups[$i]['vars'][$j]['id'],  $groups[$i]['vars'][$j]['formule']);
                }
            }
        }
        
        // Importation des données annuelles des établissements
       
        $institutionAnnualDataService = new InstitutionAnnualDataService(null);
        $data = $institutionAnnualDataService->getData(null, $year );
        foreach ($annualSurveys as $year => $idYear) {
            if ($year == 2019) {
                foreach ($establishments as $idEstablishment => $establishment) {
                    $esgbuService->createEstablishmentDataValue($idEstablishment, $idYear, $this->annualVarTab['EtabUAI'], $establishment['uai']);
                }
                continue;
            }
            echo "year : $year - id $idYear \n";
            
            foreach ($establishments as $idEstablishment => $establishment) {
                for ($i = 1; $i < count($data['students']); $i++) {
                    
                    if ($establishment['uai'] == $data['students'][$i]['etablissement'] && $year == $data['students'][$i]['rentree']) {
                        if ($data['students'][$i]['cursus_lmd'] == 'L')
                            $esgbuService->createEstablishmentDataValue($idEstablishment, $idYear, $this->annualVarTab['EtabSiseL'], $data['students'][$i]['effectif']);
                            if ($data['students'][$i]['cursus_lmd'] == 'M')
                                $esgbuService->createEstablishmentDataValue($idEstablishment, $idYear, $this->annualVarTab['EtabSiseM'], $data['students'][$i]['effectif']);
                            if ($data['students'][$i]['cursus_lmd'] == 'D')
                                $esgbuService->createEstablishmentDataValue($idEstablishment, $idYear, $this->annualVarTab['EtabSiseD'], $data['students'][$i]['effectif']);
                    }
                }
                
                for ($i = 1; $i < count($data['titular']); $i++) {
                    if ($establishment['uai'] == $data['titular'][$i]['etablissement'] && $year == $data['titular'][$i]['rentree']) {
                        $esgbuService->createEstablishmentDataValue($idEstablishment, $idYear, $this->annualVarTab['EtabECTit'], $data['titular'][$i]['effectif']);
                    }
                }
                
                for ($i = 1; $i < count($data['non_titular']); $i++) {
                    if ($establishment['uai'] == $data['non_titular'][$i]['etablissement'] && $year == $data['non_titular'][$i]['rentree']) {
                        $esgbuService->createEstablishmentDataValue($idEstablishment, $idYear, $this->annualVarTab['EtabECNTit'], $data['non_titular'][$i]['effectif']);
                    }
                }
                $esgbuService->createEstablishmentDataValue($idEstablishment, $idYear, $this->annualVarTab['EtabUAI'], $establishment['uai']);
            }
        }
        
        // Importation des données annuelles des SD
        
//         var_dump($this->annualVarTab);exit;

        foreach ($annualSurveys as $year => $idYear) {
            if ($year < 2017 || $year > 2018) continue;
            $bibFileName = preg_replace("/sd_data_(x{4})\.csv/", 'sd_data_'.$year.'.csv', self::SD_DATA_VALUES_FILE_PATTERN);
            $filePath = "src/Resources/".$bibFileName;
            echo "Ouverture du fichier de donnees SD ".$filePath."\n";
            if (($handle = fopen($filePath, "r")) !== FALSE) {
                $row = 0;
                $colVar = array();
                while (($line = stream_get_line($handle, 4096, "\n")) !== FALSE) {
                    // tableay associant le code d'un variable a son numero de colonne
                    
                    
                    $data = str_getcsv($line, ";");
                    
                    // traitement de la premiere ligne
                    if ($row == 0) {
                        var_dump($data);
                        for ($i = 0; $i < count($data); $i++) {
                            $colVar[$i] = $data[$i];
                        }
                        $row++;
                        continue;
                    }
                    
                    // Traitement des autres lignes
                    // A chaque colonne une valeur
                    for ($i = 3; $i < count($data); $i++) {
                        //echo "Variable : " . $colVar[$i]. " id : ". $this->annualVarTab[$colVar[$i]] ."\n";
                        if (isset($data[$i]) && $data[$i] != "")
                            if (isset($this->annualVarTab[$colVar[$i]])) {
                                $esgbuService->createDocumentaryStructureDataValue($data[0], $idYear, $this->annualVarTab[$colVar[$i]], $data[$i]);
                            } else {
                                echo 'le code '.$colVar[$i]."n'existe pas \n";
                            }
                    }
                    $row++;
                }
            }
            if ($handle) fclose($handle);
        }
        
        foreach ($annualSurveys as $year => $idYear) {
            if ($year < 2017 || $year > 2018) continue;
            $bibFileName = preg_replace("/Export_BP_(x{4})_yearlydata\.csv/", 'Export_BP_'.$year.'_yearlydata.csv', self::BIB_DATA_VALUES_FILE_PATTERN);
            $filePath = "src/Resources/".$bibFileName;
            echo "Ouverture du fichier de donnees Bib ".$filePath."\n";
            if (($handle = fopen($filePath, "r")) !== FALSE) {
                $row = 0;
                $colVar = array();
                while (($line = stream_get_line($handle, 4096, "\n")) !== FALSE) {
                    // tableau associant le code d'un variable a son numero de colonne
                    
                    
                    $data = str_getcsv($line, ";");
                    
                    // traitement de la premiere ligne
                    if ($row == 0) {
                        for ($i = 0; $i < count($data); $i++) {
                            $colVar[$i] = $data[$i];
                        }
                        $row++;
                        continue;
                    }
                    
                    // Traitement des autres lignes
                    // A chaque colonne une valeur
                    for ($i = 3; $i < count($data)-5; $i++) {
                        //echo "Variable : " . $colVar[$i]. " id : ". $this->annualVarTab[$colVar[$i]] ."\n";
                        if (isset($data[$i]) && $data[$i] != "")
                            if (isset($this->annualVarTab[$colVar[$i]])) {
                                $esgbuService->createPhysicalLibraryDataValue($data[0], $idYear, $this->annualVarTab[$colVar[$i]], $data[$i]);
                            } else {
                                echo 'le code '.$colVar[$i]."n'existe pas \n";
                            }
                    }
                    $row++;
                }
            }
            if ($handle) fclose($handle);
        }
        
        
        return 0;
    }
}
?>