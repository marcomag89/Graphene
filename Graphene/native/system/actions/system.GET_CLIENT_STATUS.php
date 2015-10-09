<?php
namespace system;
use Graphene\controllers\Action;

class GetClientStatus extends Action {

    public function run() {
        $app = $this->request->getContextPar('acl-app-info');
        $ret=[
            "user"=>[
                "mail"        => $this->request->getContextPar('user')['email'],
                "groups"      => $this->request->getContextPar('acl-groups'),
                "permissions" => $this->request->getContextPar('acl-permissions')
            ],
            "application"=>[
                "name"=>$app['appName'],
                "author"=>$app['appAuthor'],
                "permissions"=>$app['permissions']
            ],
            "aclEnabled" => $this->request->getContextPar('acl-enabled')
        ];

        $this->response->setBody(json_encode(["ClientInfo"=>$ret],JSON_PRETTY_PRINT));
    }
}