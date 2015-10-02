<?php
namespace system;
use acl\Group;
use Graphene\controllers\Action;
use Graphene\controllers\exceptions\GraphException;

class Config extends Action {

    public function run() {
        $req=json_decode($this->request->getBody(),true)['Config'];

        //Creating user
        $res = $this->forward('/users/user',json_encode(["User"=>$req['admin']]));
        if($res->getStatusCode() !== 200){throw new GraphException($res->getBody(),$res->getStatusCode());}
        $userId = json_decode($res->getBody(),true)['User']['id'];

        //Enabling public to basic permissions
        foreach($this->basicPermissions as $permission){
            $pRes = $this->forward('/acl/permission',json_encode(["Permission"=>["action"=>$permission,"group"=>Group::$everyoneGroupName]]));
            if($pRes->getStatusCode()!==200){throw new GraphException($pRes->getBody(),$pRes->getStatusCode());}
        }
        //Create admin application
        $appCreateRes = $this->forward('/apps',json_encode(["App"=>["appName"=>'GrapheneAdmin',"appAuthor"=>'Graphene team']]));
        if($appCreateRes->getStatusCode() !== 200)throw new GraphException('Error when creating management app');

        $appCreated=json_decode($appCreateRes->getBody(),true);
        $apiKey=$appCreated['App']['apiKey'];
        $appPermissionsRes=$this->forward('/acl/app/permission/',json_encode([
            "App"=>[
                "apiKey"=>$apiKey,
                "permissions"=>$this->adminAppPermission
            ]
        ]),'PUT');
        if($appPermissionsRes->getStatusCode() !== 200)throw new GraphException('Error when creating management app permissions');
        $appInfo=json_decode($appPermissionsRes->getBody(),true)['App'];

        foreach($this->adminAppPermission as $admPermission){
            $pRes = $this->forward('/acl/permission',json_encode(["Permission"=>["action"=>$permission,"group"=>Group::$everyoneGroupName]]));
            //if($pRes->getStatusCode()!==200){throw new GraphException($pRes->getBody(),$pRes->getStatusCode());}
        }

        //Adding user to SUPER_ADMIN group [Enabling ACL]
        $groupRes = $this->forward('/acl/userGroup',json_encode(["UserGroup"=>["userId"=>$userId,"group"=>Group::$superUserGroupName]]));
        if($groupRes->getStatusCode() !== 200){throw new GraphException($groupRes->getBody(),$groupRes->getStatusCode());}

        $baseConfig=["Configuration"=>[
            "administrator" =>$req['admin'],
            "adminApp"      =>$appInfo
        ]];
        $this->response->setBody(json_encode($baseConfig,JSON_PRETTY_PRINT));
    }
    private $basicPermissions =[
        'AUTH.LOGIN',
        'AUTH.VALIDATE"',
        'AUTH.LOGOUT',
        'USERS.VALIDATE',
        'ACL.PERMISSION_BY_USER',
        'ACL.PERMISSION_GROUP_READ',
        'ACL.GROUP_READ'
    ];

    private $adminAppPermission =[
        "SYSTEM.*",
        "USERS.*",
        "AUTH.*",
        "APPS.*",
        "ACL.*"
    ];
}