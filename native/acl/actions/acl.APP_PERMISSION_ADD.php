<?php
namespace acl;

use Graphene\controllers\Action;
use Graphene\controllers\exceptions\GraphException;

class AppPermissionAdd extends Action {
    public function run() {
        $appPrm = $this->request->getData()['AppPermission'];
        $apiKey = $appPrm['apiKey'];
        $res = $this->forward('/apps/validate/' . $apiKey);
        $appId = $res->getData()['App']['id'];

        $permission = new AppPermission();
        $permission->setAppId($appId);
        $permission->setAction($appPrm['action']);

        if ($permission->read() === null) {
            $this->sendModel($permission->create());
        } else {
            throw new GraphException('permission already assigned');
        }
    }
}