<?php
namespace users;

use Graphene\controllers\interfaces\GCIAction;
use Graphene\models\Model;

class ReadCollection extends GCIAction {

    function getModelInstance() {
        return new User();
    }

    protected function formatItem($item) {
        unset($item['password']);
        unset($item['editingKey']);
        return $item;
    }
}