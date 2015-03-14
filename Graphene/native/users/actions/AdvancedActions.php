<?php
namespace users;
use \Exception;
use Graphene\controllers\Action;

class ReadAll extends Action{
	public function run(){
		$user = new user();
		$users = $user->read();
		$ret = array();
		foreach ($users as $user) {
			$user->unsetPassword();
			$ret[] = $user;
		}
		$this->sendModel($ret);
	}
}