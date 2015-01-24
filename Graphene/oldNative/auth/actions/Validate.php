<?php
namespace auth;
use Graphene\controllers\Action;
use auth\Session;

class Validate extends Action{
	public function run (){
		$token = $this->request->getPar('at');
		$session = new Session();
		$session->setAccessToken($token);
		$results = $session->read();
		if (count($results) == 0) {$this->sendError(404, 'Session not found');return;}
		$readed = $results[0];
		if ($readed->getEnabled() == false) {$this->sendError(403, 'Session was closed');return;}
		$this->sendBean($readed);
	}
}
