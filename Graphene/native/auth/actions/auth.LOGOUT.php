<?php
namespace auth;
use Graphene\controllers\Action;
use Graphene\controllers\exceptions\GraphException;

class Logout extends Action{

    public function run (){
		$session =  new Session();
		$session -> setLazy(true);
		$session -> setAccessToken($this->request->getPar('at'));
		$readed  = $session->read();

		if ($readed !== null)                { throw new GraphException('Access token not valid',404); }
		if ($readed->getEnabled() === false) { throw new GraphException('Session already closed',400); }

		$readed -> setEnabled(false);
		$readed -> update();
		$this   -> sendMessage('Logout successful');
	}
}