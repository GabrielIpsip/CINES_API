<?php

namespace App\Utils;

use App\Common\Enum\Lang;
use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CsvViewHandler
{
    /**
     * Create CSV file for documentary structure or physical library.
     * @param ViewHandler $handler
     * @param View $view
     * @param Request $request
     * @param $format
     * @return Response
     */
    public function createResponse(ViewHandler $handler, View $view, Request $request, $format)
    {
        $data = $view->getData();
        $values = $data['values'];
        $lang = $data['lang'];
        $encoding = $data['encoding'];

        $yearWord = ($lang === Lang::fr) ? 'Année' : 'Year';

        $codeArray = array();
        $idx = 1;

        foreach ($values as $year => $couples)
        {
            if (!preg_match('/^\d{4}$/', $year))
            {
                continue;
            }

            foreach ($couples as  $code => $value)
            {
                if (!array_key_exists($code, $codeArray))
                {
                    $codeArray[$code] = $idx++;
                }
            }
        }
        
        $resultArray = array();
        foreach ($values as $year => $couples)
        {
            if (!preg_match('/\d{4}/', $year))
            {
                continue;
            }

            $lineArray = array();
            
            // l'annee sur la premiere colonne
            $lineArray[0] = $year;
            $idx = 0;
            foreach ($couples as $code => $value) {
                $lineArray[$codeArray[$code]] = $this->formatValue($value);
                $idx++;
            }

            // On rempli le reste des valeur avec la valeur vide
            for ($i=0; $i < count($codeArray); $i++) {
                if (!isset($lineArray[$i]))
                    $lineArray[$i] = '';
            }
            ksort($lineArray);
            
            array_push ($resultArray, $lineArray);
        }

        if (array_key_exists('sortOrder', $values))
        {
            $header = $values['sortOrder'] . ';"' . $values['name'] . '";"'
                . $values['address'] . '";' . $values['postalCode'] . ';"' . $values['city'] . '";' . $values['id'] . "\n";
        }
        else
        {
            $header = $values['id'] . ';"' . $values['name'] . '";"' . $values['address']
                . '";' . $values['postalCode'] . ';"' . $values['city'] . "\"\n";
        }
        $header .= $yearWord . ';' . implode(';', array_flip($codeArray));

        $body = $header . "\n";
        foreach ($resultArray as $lineArray) {
            $body .= implode(';', $lineArray) . "\n"; 
        }

        if (array_key_exists('associatedPhysicalLibraries', $values)) {
            $body .= $this->extractData($values['associatedPhysicalLibraries'], $yearWord);
        }

        $filename = $values['id'] . '_' . StringTools::replaceWhiteCharByUnderscore($values['name']);
        $view->setHeader('Content-type', 'text/csv');
        $view->setHeader('Content-Disposition' ,"attachment; filename=$filename.csv");
        $view->setHeader('Pragma', 'no-cache');
        
        StringTools::encodeString($body, $encoding);
        return new Response($body, 200, $view->getHeaders());
    }

    /**
     * Extract data from associated physical libraries.
     * @param $data
     * @param string $yearWord
     * @return string
     */
    public function extractData($data, string $yearWord) {
        
        $result = '';
        
        foreach ($data as $lib) {
            $codeArray = array();
            $idx = 1;
            
            $result .= "\n" . $lib['physicLibSortOrder'] . ';"' . $lib['physicLibName'] . '";"'
                . $lib['physicLibAddress'] . '";' . $lib['physicLibPostalCode'] . ';"'
                . $lib['physicLibCity'] . '";' . $lib['physicLibId'];

            foreach ($lib['values'] as $year => $couples) {
                foreach ($couples as  $code => $value)
                {
                    if (!array_key_exists($code, $codeArray))
                    {
                        $codeArray[$code] = $idx++;
                    }
                }
            }
            $header = $yearWord . ';' . implode(';', array_flip($codeArray));
            $result .= "\n" . $header . "\n";
            foreach ($lib['values'] as $year => $values) {
                // l'annee sur la premiere colonne
                $resultArray = array();
                $lineArray = array();
                
                $lineArray[0] = $year;
                $idx = 0;
                foreach ($values as $code => $value) {
                    $lineArray[$codeArray[$code]] = $this->formatValue($value);
                    $idx++;
                }
                // On rempli le reste des valeur avec la valeur vide
                for ($i=0; $i < count($codeArray); $i++) {
                    if (!isset($lineArray[$i]))
                        $lineArray[$i] = '';
                }
                
                ksort($lineArray);
                array_push ($resultArray, $lineArray);

                foreach ($resultArray as $lineArray) {
                    $result .= implode(';', $lineArray) . "\n";
                }
            }
            
        }
         
        return $result;
    }

    private function formatValue($value)
    {
        if ($value && strlen($value) > 0 && str_starts_with($value, '"') && str_ends_with($value, '"')) {
            $value = trim($value, '"');
            $value = str_replace('"', '""', $value);
            return '"' . $value . '"';
        }
        return $value;
    }

}
