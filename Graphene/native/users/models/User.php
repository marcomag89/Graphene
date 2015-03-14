<?php
namespace users;
use Graphene\models\Model;
use Graphene\controllers\model\ModelController;
use Graphene\controllers\exceptions\GraphException;

class User extends Model{
	public function defineStruct(){
		return array(
				'email' => 	Model::MATCH."/^[a-z0-9!#$%&'*+\/=?^_`{|}~-]+(?:\.[a-z0-9!#$%&'*+\/=?^_`{|}~-]+)*@(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?$/" . 
							Model::NOT_NULL. Model::UNIQUE. Model::MAX_LEN.'130',
				'password' => Model::STRING . Model::NOT_NULL
		);
	}
	public function onCreate(){
		if(!$this->checkPassword())throw new GraphException('Password requires one upper and one number 6-25 chars', 4001, 400);
		$this->encryptPassword();
	}
	public function onSend(){
		$this->unsetPassword();
	}
	/**
	 * Regole password
	 * - Almeno di 8 caratteri
	 * - Contiene almeno una lettera maiuscola
	 * - Contiene almeno una lettera minuscola
	 * - Contiene almeno un numero
	 *
	 */
	public function checkPassword(){
		$pwd=$this->content['password'];
		return preg_match("/^(?=.*[^a-zA-Z])(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])\w{8,32}$/", $pwd); 
	}
	public function unsetPassword (){
		if (isset($this->content['password']))
			unset($this->content['password']);
	}
	public function encryptPassword (){
		if (isset($this->content['password']))
			$this->content['password'] = md5($this->content['password']);
	}
}