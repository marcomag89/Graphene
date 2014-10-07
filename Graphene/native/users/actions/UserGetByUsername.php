<?php
use Graphene\controllers\Action;
use users\User;

class UserByUsername extends Action
{

	public function checkHandled ()
	{
		$username=$this->request->GetPar('username');
		if ($username != null) {
			$user = new User();
			$user->setUsername($username);
			$results=$user->read();
			if(count($results)>0){
				$this->readed=$results[0];
				return ! $this->readed->isEmpty();
			}else return false;
		} else
			return false;
	}

	public function run ()
	{
		$this->readed->unsetPassword();
		$this->sendBean($this->readed);
	}

	private $readed = null;
}