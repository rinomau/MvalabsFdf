<?php

namespace MvlabsPdf\Service;

use Zend\ServiceManager\FactoryInterface,
    Zend\ServiceManager\ServiceLocatorInterface;

class PdfServiceFactory implements FactoryInterface {

	public function createService(ServiceLocatorInterface $serviceLocator) {
        
        // Leggo la configurazione globale
        $as_config = $serviceLocator->get('Config');
        
        //@fixme Verificare se sono settati i due parametri e decidere cosa fare se non lo sono
        
        return new PdfService(
                $as_config['mvlabsfdf']['paths']['pdf'],
                $as_config['mvlabsfdf']['paths']['pdftk'],
                $as_config['mvlabsfdf']['paths']['gs']
                );
	}

}