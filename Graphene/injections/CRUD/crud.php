<?php
namespace injection;

use Graphene\controllers\Action;
use Graphene\controllers\model\ModelFactory;
use Graphene\controllers\exceptions\GraphException;

class Create extends Action
{

    public function run()
    {
        $model = new $this->pars[0]();
        $model = $model::getByRequest();
        $sModel = $model->create();
        $this->sendModel($sModel);
    }
}

class Read extends Action
{

    public function run()
    {
        $model = new $this->pars[0]();
        $model->setLazy(true);
        $id = $this->request->getPar('id');
        if ($id == null)
            throw new GraphException('Invalid id for ' . $model->getName(), 4002, 400);
        $model->setId($id);
        $readed = $model->read();
        if (count($readed) == 0)
            throw new GraphException($model->getName() . ' not found', 4041, '404');
        $this->sendModel($readed[0]);
    }
}

class Update extends Action
{

    public function run()
    {
        $model = new $this->pars[0]();
        $model = $model::getByRequest();
        $uModel = $model->update();
        $this->sendModel($uModel);
    }
}

class Delete extends Action
{

    public function run()
    {
        $model = new $this->pars[0]();
        $model = $model::getByRequest();
        $uModel = $model->delete();
        $this->sendMessage($model->getName() . ' ' . $model->getId() . ', successfully deleted');
    }
}