<?php
namespace users;
use Graphene\controllers\Action;
use Graphene\models\Model;
class Validate extends Action{
	public function run(){
		$user=User::getByRequest();
		$user->encryptPassword();
		$readed=$user->read();
		if(count($readed)>0){
			$readed[0]->unsetPassword();
			$this->sendModel($readed[0]);
		}else $this->sendError(404, 'invalid user');	
	}
}