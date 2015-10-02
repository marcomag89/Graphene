<?php
namespace acl;

use Graphene\controllers\Action;
use Graphene\controllers\exceptions\GraphException;

class AppReadWithPermission extends Action
{
    public function run(){
        $apiKey=$this->request->getPar('apiKey');
        $appRes = $this->forward('/apps/validate/'.$apiKey);
        if($appRes->getStatusCode() !== 200) throw new GraphException('Application not found');

        $res = $this->forward('/acl/app/permission/'.$apiKey);
        if($res->getStatusCode() !== 200) throw new GraphException('error on permissions fetch');

        $app  = json_decode($appRes->getBody(),true)['App'];
        $perm = json_decode($res->getBody(),true)['AppPermissions'];
        $app['permissions'] = $perm;
        $this->response->setBody(json_encode(["App"=>$app],JSON_PRETTY_PRINT));
    }
}