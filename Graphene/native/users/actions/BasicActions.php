<?php
namespace users;
use \Exception;
use users\User;
use Graphene\controllers\Action;
use Graphene\controllers\exceptions\GraphException;
 
class Create extends Action{
 	public function run (){
 		$user = User::getByRequest();
 		if(!$user instanceof User) throw new GraphException('invalid user', 4001, 400);
 		if(!$user->checkPassword()){$this->sendError('400', 'Password requires one upper and one number 6-25 chars');return;}
 		$user->encryptPassword();
 		try{
 			$created = $user->create()[0];
 			$created->unsetPassword();
 			$this->sendModel($created);
 		}catch(Exception $e){
 			if($e->getCode()=='3100')throw new GraphException('User: '.$user->getEmail().', already exists',4002,400);
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
		$this->sendModel($readed[0]);
	}
}

class Update extends Action{
	public function run (){
		$user = User::getByRequest();
		if(!$user->checkPassword()){$this->sendError('400', 'Password requires one upper and one number 6-25 chars');return;}
		$user->encryptPassword();
		$updated=$user->update()[0];
		$updated->unsetPassword();	
		$this->sendModel($updated);
	}
}

class Delete extends Action{
	public function run (){
		$user = User::getByRequest();
		$user->delete();
		$this->sendMessage('User successfully deleted');
	}
}
