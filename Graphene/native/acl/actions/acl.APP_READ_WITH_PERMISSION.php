<?php
namespace acl;

use Graphene\controllers\Action;

class AppReadWithPermission extends Action {
    public function run() {
        $apiKey = $this->request->getPar('apiKey');
        $app = $this->forward('/apps/validate/' . $apiKey)->getData()['App'];
        $permissions = $this->forward('/acl/app/permission/' . $apiKey)->getData()['AppPermissions'];
        $app['permissions'] = $permissions;
        $this->send(['App' => $app]);
    }
}