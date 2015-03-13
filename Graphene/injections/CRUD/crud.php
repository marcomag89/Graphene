<?php
namespace injection;

use Graphene\controllers\Action;
use Graphene\controllers\bean\BeanFactory;
use Graphene\controllers\exceptions\GraphException;

class Create extends Action
{

    public function run()
    {
        $bean = new $this->pars[0]();
        $bean = $bean::getByRequest();
        $sBean = $bean->create();
        $this->sendBean($sBean);
    }
}

class Read extends Action
{

    public function run()
    {
        $bean = new $this->pars[0]();
        $bean->setLazy(true);
        $id = $this->request->getPar('id');
        if ($id == null)
            throw new GraphException('Invalid id for ' . $bean->getName(), 4002, 400);
        $bean->setId($id);
        $readed = $bean->read();
        if (count($readed) == 0)
            throw new GraphException($bean->getName() . ' not found', 4041, '404');
        $this->sendBean($readed[0]);
    }
}

class Update extends Action
{

    public function run()
    {
        $bean = new $this->pars[0]();
        $bean = $bean::getByRequest();
        $uBean = $bean->update();
        $this->sendBean($uBean);
    }
}

class Delete extends Action
{

    public function run()
    {
        $bean = new $this->pars[0]();
        $bean = $bean::getByRequest();
        $uBean = $bean->delete();
        $this->sendMessage($bean->getName() . ' ' . $bean->getId() . ', successfully deleted');
    }
}