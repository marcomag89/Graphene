<?php
namespace system;

use Graphene\controllers\Action;

class GetClientStatus extends Action {

    public function run() {
        $app = $this->request->getContextPar('acl-app-info');

        $ret = [
            "user"        => [
                "name"        => $this->request->getContextPar('user')['username'],
                "groups"      => $this->request->getContextPar('acl-groups'),
                "permissions" => $this->request->getContextPar('acl-permissions')
            ],
            "application" => [
                "name"        => $app['appName'],
                "author"      => $app['appAuthor'],
                "permissions" => $app['permissions']
            ],
            "aclEnabled"  => $this->request->getContextPar('acl-enabled') !== null ? $this->request->getContextPar('acl-enabled') : false
        ];

        foreach ($this->request->getContextPars() as $key => $value) {
            if (Strings::startsWith($key, 'client_') && $value !== null) {
                $ret[str_replace('client_', '', $key)] = $this->request->getContextPar($value);
            }
        }

        if ($ret['aclEnabled']) {
            if ($ret['application']['name']) {
                if ($ret['user']['name']) {
                    $ret['statusLabel'] = 'logged-in';
                } else {
                    $ret['statusLabel'] = 'no-user';
                }
            } else {
                $ret['statusLabel'] = 'app-unauthorized';
            }
        } else {
            $ret['statusLabel'] = 'no-acl';
        }

        $this->response->setBody(json_encode(["ClientInfo" => $ret], JSON_PRETTY_PRINT));
    }
}