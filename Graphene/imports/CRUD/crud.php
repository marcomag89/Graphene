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
        $this->send($sModel);
    }

    public function getResponseStruct(){
        $model = new $this->pars[0]();
        return [$model->getModelName() => $model->getReadActionStruct()];
    }

    public function getRequestStruct(){
        $model = new $this->pars[0]();
        return [$model->getModelName()=> $model->getCreateActionStruct()];
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
        $this->send($readed);
    }

    public function getResponseStruct(){
        $model = new $this->pars[0]();
        return [$model->getModelName() => $model->getReadActionStruct()];
    }
}

class Update extends Action
{

    public function run()
    {
        $model = new $this->pars[0]();
        $model = $model::getByRequest();
        $uModel = $model->update();
        $this->send($uModel);
    }

    public function getResponseStruct(){
        $model = new $this->pars[0]();
        return [$model->getModelName() => $model->getReadActionStruct()];
    }

    public function getRequestStruct(){
        $model = new $this->pars[0]();
        return [$model->getModelName() => $model->getUpdateActionStruct()];
    }
}

class Delete extends Action
{

    public function run()
    {
        $model = new $this->pars[0]();
        $model = $model::getByRequest();
        $uModel = $model->delete();
        $this->send($model->getModelName() . ' ' . $model->getId() . ', successfully deleted');
    }

    public function getResponseStruct(){
        $model = new $this->pars[0]();
        return [$model->getModelName() => $model->getReadActionStruct()];
    }

    public function getRequestStruct(){
        $model = new $this->pars[0]();
        return [$model->getModelName()=>$model->getDeleteActionStruct()];
    }
}