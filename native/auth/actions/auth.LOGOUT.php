<?php
namespace auth;
use Graphene\controllers\Action;
use Graphene\controllers\exceptions\GraphException;

class Logout extends Action{

    public function run (){
		$accessToken = $this->request->getPar('at');
		if($accessToken === null){$accessToken = $this->request->getHeader('access-token');}
        if($accessToken === null)throw new GraphException('access token not sent',400);

		$session =  new Session();
		$session -> setAccessToken($accessToken);
		$readed  = $session->read();

		if ($readed === null)                { throw new GraphException('Access token not valid',404); }
		if ($readed->getEnabled() === false) { throw new GraphException('Session already closed',400); }

		$readed -> setEnabled(false);
		$readed -> update();

		$this   -> sendMessage('Logout successful');
	}
}