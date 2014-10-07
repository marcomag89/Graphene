<?php
use Graphene\controllers\Action;
use users\User;

class UserPatch extends Action
{

	public function run ()
	{
		$user = User::getByRequest();
		$patched = $user->patch();
		$patched->unsetPassword();
		$this->sendBean($patched);
	}

	public function getErrorCode ($e)
	{
		return $this->getClientSideErrorCode($e);
	}
}