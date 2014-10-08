<?php
use Graphene\controllers\Action;
use users\User;

class AllUsers extends Action
{

	public function run ()
	{
		$user = new user();
		$users = $user->read();
		$ret = array();
		foreach ($users as $user) {
			$user->unsetPassword();
			$ret[] = $user;
		}
		$this->sendBean($ret);
	}
}