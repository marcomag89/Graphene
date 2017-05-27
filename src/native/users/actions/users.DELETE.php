<?php
namespace users;

use Graphene\controllers\interfaces\StdDelete;

class Delete extends StdDelete {
    function getModelInstance() {
        return new User();
    }
}