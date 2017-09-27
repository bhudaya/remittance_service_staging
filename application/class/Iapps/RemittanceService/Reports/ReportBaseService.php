<?php

namespace Iapps\RemittanceService\Reports;

use Iapps\Common\Core\IappsBasicBaseService;

abstract class ReportBaseService extends IappsBasicBaseService{

    protected $option;

    public function setOption($param, $value)
    {
        $this->option[$param] = $value;
        return $this;
    }

    public function getOption($param)
    {
        if( isset($this->option[$param]) )
            return $this->option[$param];

        return false;
    }

    /*
     * return array
     * field name => value
     */
    abstract protected function getData();

    public function generateCSV($fileName)
    {
        if( $dataArray = $this->getData() )
        {
            if( is_array($dataArray) and count($dataArray) > 0 )
            {
                $outPath = './upload/document/';
                $outFile = $outPath . $fileName;

                if( !file_exists($outPath) )
                    return false;

                //get header
                $headers = array_keys(array_values($dataArray)[0]);
                if( is_array($headers) )
                {
                    if( $handle = fopen($outFile, 'w') )
                    {
                        //put header
                        fputcsv($handle, $headers);
                        //put data
                        foreach ($dataArray as $data)
                        {
                            $line = array();
                            foreach( $headers AS $header )
                            {
                                if( array_key_exists($header, $data) AND ($data[$header] !== NULL OR $data[$header] !== FALSE) )
                                    $line[] = $data[$header];
                                else
                                    $line[] = '-';
                            }

                            fputcsv($handle, $line);
                        }

                        fclose($handle);
                        
                        return true;
                    }
                }
            }
        }

        return false;
    }
    
    public function generateCSVBase64($fileName)
    {
        if( $this->generateCSV($fileName) )
        {
            $outPath = './upload/document/';
            $outFile = $outPath . $fileName;
            $content = file_get_contents($outFile);
            unlink($outFile);
            return base64_encode($content);            
        }
                
        return false;
    }
}