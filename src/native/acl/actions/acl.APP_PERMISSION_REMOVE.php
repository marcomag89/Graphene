<?php
namespace acl;

use Graphene\controllers\Action;
use Graphene\controllers\exceptions\GraphException;

class AppPermissionRemove extends Action {
    public function run() {
        $appPrm = $this->request->getData()['AppPermission'];
        $apiKey = $appPrm['apiKey'];
        $app = $this->forward('/apps/validate/' . $apiKey)->getData()['App'];
        $permission = new AppPermission();
        $permission->setAppId($app['id']);
        $permission->setAction($appPrm['action']);
        $rPermission = $permission->read();
        if ($rPermission !== null) {
            $rPermission->delete();
            $this->sendMessage('permission deleted successfully');
        } else {
            throw new GraphException('permission not found');
        }
    }
}