<?php

namespace customer;
use Graphene\models\Model;

class Customer extends Model
{
	
	public function defineStruct() {
		
		return array(
			'ragioneSociale'			=> Model::STRING.Model::MAX_LEN.'200'.Model::NOT_NULL,
            'partitaIva'			    => Model::STRING.Model::MAX_LEN.'13'.Model::NOT_NULL.Model::UNIQUE,
            'riferimentoInterno'		=> Model::STRING.Model::NOT_NULL,
            'codiceEsterno'			    => Model::STRING.Model::MAX_LEN.'50',
            'contatto'                  => array('mobile'=>Model::STRING.Model::MAX_LEN.'20',
                                                'fisso'=>Model::STRING.Model::MAX_LEN.'20',
                                                'email'=>Model::STRING.Model::MAX_LEN.'250')

/*
				'puntoAccesso' 					=> Model::ENUM.'UO,MMG,SS'.Model::NOT_NULL,
				'puntoAccessoSpec' 				=> Model::STRING.Model::MAX_LEN.'250',
				'riferimentoInterno'			=> Model::STRING.Model::NOT_NULL,
				'bisognoRilevato'				=> Model::STRING.Model::MAX_LEN.'2000',
				'motRichiesta'					=> Model::ENUM.'AN,DPF,MA,ISF,SO,ANI,DMI,TSC,X'.Model::NOT_NULL,

*/
		);
		
	}// end defineStruct()
	
}//end class
