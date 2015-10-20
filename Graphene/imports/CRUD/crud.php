<?php
namespace imports;

use Graphene\controllers\Action;

class Create extends Action
{

    public function run()
    {
        $model = new $this->pars[0]();
        $model = $model::getByRequest();
        $sModel = $model->create();
        $this->sendModel($sModel);
    }
    public function getRequestStruct(){
        $model = new $this->pars[0]();
        return [$model->getModelName()=> $model->defineStruct()];
    }
}

class Read extends Action{
    public function run()
    {
        $model = new $this->pars[0]();
        $model->setLazy(true);
        $id = $this->request->getPar('id');
        $model->setId($id);
        $readed = $model->read();
        $this->sendModel($readed);
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

    public function getRequestStruct(){
        $model = new $this->pars[0]();
        return [$model->getModelName()=>$model->getStruct()];
    }
}

class Delete extends Action
{

    public function run()
    {
        $model = new $this->pars[0]();
        $model = $model::getByRequest();
        $uModel = $model->delete();
        $this->sendMessage($model->getModelName() . ' ' . $model->getId() . ', successfully deleted');
    }
    public function getRequestStruct(){
        $model = new $this->pars[0]();
        return [$model->getModelName()=>$model->getStruct()];
    }
}