<?php
namespace acl;

use Graphene\controllers\Action;
use Graphene\controllers\exceptions\GraphException;

class AppPermissionsSet extends Action {
    public function run() {
        $appProto = $this->request->getData()['App'];
        $apiKey = $appProto['apiKey'];
        $permissions = $appProto['permissions'];
        $res = $this->forward('/acl/app/withPermission/' . $apiKey);
        $pRes = json_decode($res->getBody(), true);
        if ($res->getStatusCode() !== 200) {
            throw new GraphException('App info error: ' . $pRes['error']['message'], $pRes['error']['code'], 400);
        }
        $app = $pRes['App'];
        $rPermissions = $app['permissions'];
        $doAdd = [];
        $doRemove = [];

        Graphene::getLogger()->info($permissions);
        //controllo permessi da aggiungere
        foreach ($permissions as $permission) {
            if (!in_array($permission, $rPermissions)) {
                $doAdd[] = $permission;
            }
        }

        //controllo permessi da rimuovere
        foreach ($rPermissions as $permission) {
            if (!in_array($permission, $permissions)) {
                $doRemove[] = $permission;
            }
        }

        foreach ($doRemove as $prm) {
            $req = ["AppPermission" => ["apiKey" => $apiKey, "action" => $prm]];
            try {
                $this->forward('/acl/app/permission', $req, 'DELETE');
            } catch (\Exception $e) {
                $errs[] = $e->getMessage();
            }
        }

        foreach ($doAdd as $prm) {
            $req = ["AppPermission" => ["apiKey" => $apiKey, "action" => $prm]];
            try {
                $this->forward('/acl/app/permission', $req, 'POST');
            } catch (\Exception $e1) {
                $errs[] = $e1->getMessage();
            }
        }

        $app = $this->forward('/acl/app/withPermission/' . $apiKey)->getData();
        $app['errors'] = $errs;
        $this->send($app);
    }
}