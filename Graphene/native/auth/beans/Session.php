<?php
namespace auth;
use Graphene\models\Bean;
use Graphene\controllers\bean\BeanController;

class Session extends Bean{
	public function defineStruct (){
		$lazy = array();
		$lazy['hostAddress']  = Bean::STRING       .Bean::NOT_EMPTY;
		$lazy['hostAgent']    = Bean::STRING       .Bean::NOT_EMPTY;
		$lazy['apiKey']       = Bean::UID          .Bean::NOT_EMPTY;
		$lazy['enabled']      = Bean::BOOLEAN      .Bean::NOT_EMPTY;
		$lazy['timeStamp']    = Bean::INTEGER      .Bean::NOT_EMPTY;
		$lazy['accessToken']  = Bean::MATCH."/^[A-Za-z0-9]+$/" .Bean::NOT_EMPTY.Bean::MAX_LEN.'120'.Bean::UNIQUE;
		$lazy['user']         = Bean::UID          .Bean::NOT_EMPTY;
		return $lazy;
	}
	public function unsetForLogout(){
		unset($this->content['hostAddress']);
		unset($this->content['hostAgent']);
		unset($this->content['apiKey']);
		unset($this->content['enabled']);
		unset($this->content['timeStamp']);
		unset($this->content['user']);	
	}
	public function createAccessToken (){
		$this->content['accessToken'] = md5(md5($this->content['hostAddress']).md5($this->content['apiKey'])).
										 md5(md5($this->content['hostAgent']).md5($this->content['timeStamp']));
	}
	public function createTimestamp (){
		$this->content['timeStamp'] = round(microtime(true) * 1000);
	}
}