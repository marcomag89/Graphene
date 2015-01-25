<?php
namespace auth;
use Graphene\models\Bean;
use Graphene\controllers\bean\BeanController;
use users\User;
use Graphene\controllers\exceptions\GraphException;
use Graphene\controllers\exceptions\ExceptionCodes;

class AuthRequest extends Bean{
	public function defineStruct(){
		return array(
			'apiKey' 	=>	Bean::MATCH."/^[A-Za-z0-9]+$/".Bean::NOT_NULL.Bean::MAX_LEN.'150',
			'apiSecret' =>	Bean::MATCH."/^[A-Za-z0-9]+$/".Bean::NOT_NULL.Bean::MAX_LEN.'80',
			'user'	 => array(
				'email'=>Bean::STRING.Bean::NOT_NULL,
				'password'=>Bean::STRING.Bean::NOT_NULL,
			)
		);
	}
	public function getUser(){
		$user=new User();
		$user->setEmail($this->content['user']['email']);
		$user->setPassword($this->content['user']['password']);
		if(!$user->isValid())throw new GraphException('syntax of email or password is invalid',ExceptionCodes::BEAN_CONTENT_VALID , 400);
		return $user;
	}
}