<?php
namespace acl;

use Graphene\controllers\Action;

class AppPermissionsByApp extends Action {
    public function run() {
        $apiKey = $this->request->getPar('apiKey');
        $app = $this->forward('/apps/validate/' . $apiKey)->getData()['App'];
        $appPerm = new AppPermission();
        $appPerm->setAppId($app['id']);
        $rdd = $appPerm->read(true);
        $ret = [];
        foreach ($rdd as $appPermission) {
            //TODO process real permissions
            $ret[] = $appPermission->getAction();
        }
        $this->send(["AppPermissions" => $ret]);
    }
}