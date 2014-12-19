<?php

namespace MvlabsPdf\Service;

use MvlabsPdf\Exception;

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
    
    /**
     * Eseguibile di ghostscript
     * @var type string
     */
    private $gsBinary;
    
    public function __construct($s_pdfFilePath,$s_pdftkBin, $s_ghostscriptBin) {
        
        $this->pdfFilePath = $s_pdfFilePath;
        
        $this->pdftkBinary = $s_pdftkBin;
        
        $this->gsBinary = $s_ghostscriptBin;
        
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

    public function getGsBinary() {
        return $this->gsBinary;
    }
    
    public function test(){
        return 'sono nella funzione test di FdfService. Leggo pdf in '.$this->getPdfFilename().' scrivo fdf in '.$this->getPdfFilename();
    }
    
    public function writeFdf($s_masterPdfFileName, $as_values, $s_fdfFileName){
        $s_values = $this->createXfdf($s_masterPdfFileName, $as_values);
        $this->writeFile($s_values, $s_fdfFileName);
    }
    
    public function getPartialPdfFile($s_masterPdfFileName, $i_pageFrom, $i_pagesNum, $as_values = array(), $b_flat = true) {
        
        $s_pdfTempName = $this->extractPages(
            $s_masterPdfFileName,
            array($i_pageFrom.'-'.($i_pageFrom + $i_pagesNum -1))
        );
        
        return $this->getPartialPdfFileWithData($s_pdfTempName, $as_values, $b_flat);
    }

    public function getPartialPdfFilePages($s_masterPdfFileName, $ai_pages, $as_values = array(), $b_flat = true)
    {
        $s_pdfTempName = $this->extractPages($s_masterPdfFileName, $ai_pages);

        return $this->getPartialPdfFileWithData($s_pdfTempName, $as_values, $b_flat);
    }

    private function getPartialPdfFileWithData($s_pdfTempName, $as_values, $b_flat)
    {
        if (!empty($as_values)) {
            $s_pdf = $this->getPdfFile($s_pdfTempName, $as_values, $b_flat);
            unlink($this->getPdfFilePath() . '/' . $s_pdfTempName);
        } else {
            $s_pdf = $this->getPdfFilePath() . '/' . $s_pdfTempName;
        }

        return $s_pdf;
    }
    
    public function getMergedPdfFile(array $as_files, $s_fileWatermark=null) {
    
        $s_pdfTempName = basename(tempnam($this->getPdfFilePath(), ''));
        $s_pdfFileName = $this->getPdfFilePath() . '/' . $s_pdfTempName;
        $s_pdfFileNameResult = $s_pdfFileName;
    
        $s_cmd = $this->getPdftkBinary() . ' ' .
            implode(' ', $as_files) .
            ' cat ' .
            ' output ' .
            $s_pdfFileName;
    
        exec($s_cmd);
    
        //run ghostscript file size optimization.
        $s_cmdCompress = $this->getGsBinary() . ' -sDEVICE=pdfwrite -dCompatibilityLevel=1.4 -dPDFSETTINGS=/screen -dNOPAUSE -dBATCH -dQUIET -sOutputFile='  . $s_pdfFileName . '.compress ' . $s_pdfFileName;
        exec($s_cmdCompress);
        $s_pdfFileNameResult = $s_pdfFileName . '.compress';
    
        if (!empty($s_fileWatermark) ){
            //run command for add a waterkmark
            $s_cmd = $this->getPdftkBinary() . ' ' . $s_pdfFileName . '.compress multistamp ' . $this->getPdfFilePath(). '/' .$s_fileWatermark . '  output ' . $s_pdfFileName . '.watermarked';
            exec($s_cmd);
            $s_pdfFileNameResult = $s_pdfFileName . '.watermarked';
        }
    
        //cancello il pdf originale dopo la generazione del nuovo pdf compresso
        unlink($s_pdfFileName);
    
        return $s_pdfFileNameResult;
    
    }
    
    /**
     * @param string file name
     * @param array of strings representing page numbers or intervals of page,
     * see https://www.pdflabs.com/docs/pdftk-man-page/#dest-op-cat for details
     */
    private function extractPages($s_masterPdfFileName, $as_pages)
    {
        
        $s_pdfTempName = basename(tempnam($this->getPdfFilePath(), ''));
        
        $s_pages = implode(' ', $as_pages);

        $s_cmd = $this->getPdftkBinary() . ' ' . 
                 $this->getPdfFilePath() . '/' . $s_masterPdfFileName . 
                 ' cat ' . 
                 $s_pages .
                 ' output ' . 
                 $this->getPdfFilePath() . '/' . $s_pdfTempName;

        exec($s_cmd);
        
        return $s_pdfTempName;
        
    }
    
    public function getPdfFile($s_masterPdfFileName, $as_values, $b_flat = true) {
        
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
        
        unlink($this->getPdfFilePath() . '/' . $s_fdfTempName);
        
        return $this->getPdfFilePath() . '/' . $s_pdfTempName;
        
    }
    
    public function getPdf($s_masterPdfFileName, $as_values, $b_flat = true){
        
        $s_pdfTempName = $this->getPdfFile($s_masterPdfFileName, $as_values, $b_flat);
        
        $s_result = file_get_contents($s_pdfTempName);
         
        unlink($s_pdfTempName);
        
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