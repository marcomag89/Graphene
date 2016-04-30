<?php
namespace users;

use Graphene\controllers\exceptions\GraphException;
use Graphene\controllers\interfaces\StdUpdate;
use Graphene\models\Model;

class Update extends StdUpdate {
    function getModelInstance() {
        return new User();
    }

    function getModelFromRequest() {
        //\Log::debug($this->request->getData());
        if (!array_key_exists('User', $this->request->getData())) {
            throw new GraphException('Sent model is not valid User');
        }
        $userData = $this->request->getData()['User'];
        $user = new User();
        $user->setContent($userData);
        return $user;
    }

    protected function updateModel(Model $model) {
        if ($model instanceof User) {
            $tUser = new User();
            $tUser->setContent([
                'id'      => $model->getId(),
                'version' => $model->getVersion(),
            ]);
            $rUser = $tUser->read();
            if ($rUser === null) throw new GraphException('Invalid user id or version');
            if (!array_key_exists('password', $model->getContent())) {
                $model->setPassword($rUser->getPassword());
            } else {
                $model->encryptPassword();
            }
            return $model->update();
        } else {
            throw new GraphException('Invalid user');
        }
    }

    protected function formatUpdatedModel(Model $user) {
        $userC = $user->getContent();
        unset($userC['password']);
        unset($userC['editingKey']);
        $user = new User();
        $user->setContent($userC);
        return $user;
    }
}