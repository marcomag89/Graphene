<?php
namespace imports;

use Graphene\controllers\Action;
use Graphene\controllers\interfaces\StdCreate;
use Graphene\controllers\interfaces\StdDelete;
use Graphene\controllers\interfaces\StdRead;
use Graphene\controllers\interfaces\StdUpdate;
use Graphene\models\Model;

class Create extends StdCreate
{
    function getModelInstance() {
        return new $this->pars[0]();
    }
}

class Read extends StdRead {
    function getModelInstance() {
        return new $this->pars[0]();
    }
}

class Update extends StdUpdate
{
    function getModelInstance() {
        return new $this->pars[0]();
    }
}

class Delete extends StdDelete
{
    function getModelInstance() {
        return new $this->pars[0]();
    }
}