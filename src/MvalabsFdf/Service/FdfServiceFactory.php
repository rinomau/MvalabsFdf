<?php

namespace MvalabsFdf\Service;

use Zend\ServiceManager\FactoryInterface,
    Zend\ServiceManager\ServiceLocatorInterface;

class FdfServiceFactory implements FactoryInterface {

	public function createService(ServiceLocatorInterface $serviceLocator) {
        
        // Leggo la configurazione globale
        $as_config = $serviceLocator->get('Config');
        
        //@fixme Verificare se sono settati i due parametri e decidere cosa fare se non lo sono
        
        return new FdfService(
                $as_config['fdf_paths']['fdf_file_path'],
                $as_config['fdf_paths']['fdf_file_name'],
                $as_config['fdf_paths']['pdf_file_path'],
                $as_config['fdf_paths']['pdf_file_name'],
                $as_config['pdftk_paths']['bin']
                );
	}

}