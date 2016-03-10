<?php
namespace imports;

use Graphene\controllers\Action;
use Graphene\controllers\interfaces\GCIAction;
use Graphene\Graphene;
use Graphene\models\Model;
use Graphene\models\ModelCollection;

class ReadCollection extends GCIAction
{
    public function getModelInstance()
    {
        return new $this->pars[0]();
        // TODO: Implement getModelInstance() method.
    }
}

class ReadAllCollection extends Action
{
    public function run()
    {
        $model = new $this->pars[0]();
        $model->setLazy(true);

        $readed = $model->read(true,null,1,0);
        $this->sendModel($readed);
    }
}