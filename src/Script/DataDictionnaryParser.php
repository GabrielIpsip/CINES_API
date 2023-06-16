<?php
namespace App\Script;

/**
 * Clase pour analyser la dictionnaire le donnees
 * @author alexandre Granier <granier@cines.fr>
 *        
 */
class DataDictionnaryParser
{
    protected const PATH = "src/Resources/data_types-1.5.2.csv";
    
    /**
     * @return array|null
     */
    public function parse(): ?array
    {
        $groups = array();
        array_push($groups, 1);
        $treeGroupIndex = array();
        $row = 0;
        if (($handle = fopen(self::PATH, "r")) !== FALSE) {
            while (($line = stream_get_line($handle, 4096, "\n")) !== FALSE) {
                $row++;
                if ($row < 6) continue;
//                 if ($row > 30) break;
                $data = str_getcsv($line, ";");
                $num = count($data);
                    
                // On saute les lignes incompletes (par exemple les entetes
                if ($num < 5) continue;
                if ($row > 429) break;
                if ($row == 30 || $row == 382 || $row == 383
                    || $data['0'] == "(sous-sous-groupe)\"" || $data[0] == "Utilisateurs") { // On vide treeGroupIndex pour �viter d'ajouter les variables associ�es
                    $treeGroupIndex = array();
                    continue;
                }
                // Groupe de premier niveau
                if ($data[0] != "" && $row != 6 &&  $row != 382) { // On saute les donn�es permanentes
                   echo "Groupe " . $data[0] . "\n"; 
                    
                    $typeGroup = $data[count($data)-3];
                    $group = array();
                    $group['name'] = $data[0];
                    $group['name_en'] = $data[8];
                    $group["consigne"] = $data[11];
                    $group['type'] = $typeGroup;
                    $group['parent'] = null;
                    $group['vars'] = array();
                    
                    array_push($groups, $group);
                   $treeGroupIndex[0] = array_key_last($groups);
                    
                } 
                
                // Groupe d'autres niveaux niveau
                for ($i = 1; $i < 3; $i++)
                    if ($data[$i] != "") {
//                         var_dump($data);
                        $typeGroup = $data[count($data)-3];
                        $group = array();
                        $group['name'] = $data[$i];
                        $group['name_en'] = $data[8];
                        $group["consigne"] = $data[11];
                        $group['type'] = $typeGroup;
                        
                        $group['vars'] = array();
                        $group['parentId'] = $treeGroupIndex[$i-1];
                        array_push($groups, $group);
                        $treeGroupIndex[$i] = array_key_last($groups);
                    }
                
                // Variable 
                
                if ($data[4] != "" && !empty($treeGroupIndex)) {
                    $currentGroup = array_pop($groups);
                    if ($currentGroup == null) continue;
                   echo "Traitement ".$data[4]."\n"; 
                    $var['code'] = $data['4'];
                    $var['libelle_fr'] = $data['7'];
                    $var['libelle_en'] = $data['8'];
                    $var['explicatif_fr'] = $data['9'];
                    $var['explicatif_en'] = $data['10'];
                    $var['consigne_fr'] = $data['11'];
                    $var['calcule'] = $data['13'];
                    $var['formule'] = $data['14'];
                    $var['y2013'] = $data[17];
                    $var['y2014'] = $data[18];
                    $var['y2015'] = $data[19];
                    $var['y2016'] = $data[20];
                    $var['y2017'] = $data[21];
                    $var['y2018'] = $data[22];
                    $var['y2019'] = $data[23];
                    $var['type'] = $data[24]; 
                    $var['unite'] = $data[25];
                    $var['date'] = $data[26];
                    $var['min'] = $data[27];
                    $var['max'] = $data[28];
                    $var['alert'] = $data[29];
                    $var['evolutionMin'] = $data[31];
                    $var['evolutionMax'] = $data[32];
                    $var['regex'] = $data[35];
                    array_push($currentGroup['vars'],$var);
                    array_push($groups, $currentGroup);
                }
                
            }
            fclose($handle);
            unset($groups[0]);
             var_dump($groups);
             //exit;
            return $groups;
        }
        return null;
    }
}

