<?php

namespace MvlabsPdf\Service;

class PdfService {
    
    /**
     * Directory dove si trovano i pdf con i campi modulo
     * @var type string
     */
    private $pdfFilePath;
    
    /**
     * Eseguibile di pdftk
     * @var type string
     */
    private $pdftkBinary;
    
    
    public function __construct($s_pdfFilePath,$s_pdftkBin) {
        
        $this->pdfFilePath = $s_pdfFilePath;
        
        $this->pdftkBinary = $s_pdftkBin;
        
    }
    
    public function setPdfFilePath($s_pdfFilePath){
        $this->pdfFilePath = $s_pdfFilePath;
    }
    
    public function getPdfFilePath() {
        return $this->pdfFilePath;
    }
    
    public function getPdftkBinary() {
        return $this->pdftkBinary;
    }

    public function test(){
        return 'sono nella funzione test di FdfService. Leggo pdf in '.$this->getPdfFilename().' scrivo fdf in '.$this->getPdfFilename();
    }
    
    public function writeFdf($s_masterPdfFileName, $as_values, $s_fdfFileName){
        $s_values = $this->createXfdf($s_masterPdfFileName, $as_values);
        $this->writeFile($s_values, $s_fdfFileName);
    }
    
    public function getPdf($s_masterPdfFileName, $as_values, $b_flat = true){
        
        $s_fdfTempName = basename(tempnam($this->getPdfFilePath(), ''));
        $s_pdfTempName = basename(tempnam($this->getPdfFilePath(), ''));
            
        $this->writeFdf($s_masterPdfFileName, $as_values, $s_fdfTempName);
        
        $s_cmd = $this->getPdftkBinary() . ' ' . 
                 $this->getPdfFilePath() . '/' . $s_masterPdfFileName . 
                 ' fill_form ' . 
                 $this->getPdfFilePath() . '/' . $s_fdfTempName . 
                 ' output ' . 
                 $this->getPdfFilePath() . '/' . $s_pdfTempName;
        
        if ($b_flat) {
            $s_cmd .= ' flatten';
        }
        
        exec($s_cmd);
        
        $s_result = file_get_contents($this->getPdfFilePath() . '/' . $s_pdfTempName);
        
        unlink($this->getPdfFilePath() . '/' . $s_fdfTempName);
        unlink($this->getPdfFilePath() . '/' . $s_pdfTempName);
        
        return $s_result;
        
    }
    
    /**
     * Dato un array associativo genera un file fdf
     * @param type $file nome del file contentente i campi modulo a cui si riferisce l'fdf
     * @param type $as_values array associativo con chiavi e valori da inserire nei campi modulo
     * @param type $enc encoding del file
     * @return string contenuto del file fdf
     */
    private function createXfdf($s_masterPdfFileName, $as_values, $enc='UTF-8')
    {
        $file = $s_masterPdfFileName;
        
        $data = '<?xml version="1.0" encoding="'.$enc.'"?>' . "\n" .
            '<xfdf xmlns="http://ns.adobe.com/xfdf/" xml:space="preserve">' . "\n" .
            '<fields>' . "\n";
        foreach( $as_values as $field => $val )
        {
            $data .= '<field name="' . $field . '">' . "\n";
            if( is_array( $val ) )
            {
                foreach( $val as $opt )
                    $data .= '<value>' .
                        htmlentities( $opt, ENT_COMPAT, $enc ) .
                        '</value>' . "\n";
            }
            else
            {
                $data .= '<value>' .
                    htmlentities( $val, ENT_COMPAT, $enc ) .
                    '</value>' . "\n";
            }
            $data .= '</field>' . "\n";
        }
        $data .= '</fields>' . "\n" .
            '<ids original="' . md5($file) . '" modified="' .
                time() . '" />' . "\n" .
            '<f href="' . $file . '" />' . "\n" .
            '</xfdf>' . "\n";
        return $data;
    }
    
    /**
     * Data un path e una stringa scrive la stringa sul file
     * @param type $s_filePath
     * @param type $values
     */
    private function writeFile($s_values, $s_fileName){
        
        $s_filePath = $this->getPdfFilePath() . '/' . $s_fileName;
        
        // @fixme Cosa fare in caso di errore di apertura del file?
        $handle = fopen($s_filePath,'w+');
        if (fwrite($handle, $s_values) === FALSE) {
            // @fixme Cosa fare in caso di errore di scrittura del file?
            die('Impossibile scrivere sul file ('.$s_filePath.')');
        }
        fclose($handle);
    }

}