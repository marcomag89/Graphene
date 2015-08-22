<?php
namespace auth;
use Graphene\controllers\Action;
use auth\Session;

class Logout extends Action{

    public function run (){
		$session=new Session();
		$session->setLazy(true);
		$session->setAccessToken($this->request->getPar('at'));
		$readed = $session->read();
		if(count($readed)==0){$this->sendError(400, 'Session not found' );return;}
		if ($readed[0]->getEnabled() == false) {$this->sendError(400, 'Session already closed');return;}
		$readed[0]->setEnabled('0');
		$readed[0]->update();
		$this->sendMessage('Logout successful');
	}
}