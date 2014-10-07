<?php
namespace users;
use \Exception;
use users\User;
use Graphene\controllers\Action;
 
class Create extends Action{
 	public function run (){
 		$user = User::getByRequest();
 		if(!$user instanceof User){$this->sendError('400', 'invalid request');return;}
 		if(!$user->checkPassword()){$this->sendError('400', 'Password requires one upper and one number 6-25 chars');return;}
 		$user->encryptPassword();
 		try{
 			$created = $user->create()[0];
 			$created->unsetPassword();
 			$this->sendBean($created);
 		}catch(Exception $e){
 			if($e->getCode()=='3100')$this->sendError('400', 'user already exists');
			else $this->sendError(500, $e->getMessage());
 		}
 	}
}

class Read extends Action{
	public function run (){
		$user=new User();
		$user->setLazy(true);
		$user->setId($this->request->getPar('id'));
		$readed=$user->read();
		if(count($readed)==0){$this->sendError(404, 'user not found');return;}
		else $readed[0]->unsetPassword();
		$this->sendBean($readed[0]);
	}
}

class Update extends Action{
	public function run (){
		$user = User::getByRequest();
		if(!$user->checkPassword()){$this->sendError('400', 'Password requires one upper and one number 6-25 chars');return;}
		$user->encryptPassword();
		$updated=$user->update()[0];
		$updated->unsetPassword();	
		$this->sendBean($updated);
	}
}

class Delete extends Action{
	public function run (){
		$user = User::getByRequest();
		$user->delete();
		$this->sendMessage('User successfully deleted');
	}
}
