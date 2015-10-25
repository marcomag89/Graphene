<?php
namespace system;
use acl\Group;
use Graphene\controllers\Action;
use Graphene\controllers\exceptions\GraphException;

class Config extends Action {

    public function run() {
        $req=json_decode($this->request->getBody(),true)['Config'];

        //Creating user
        $res  = $this->forward('/users/user',json_encode(["User"=>$req['admin']]));
        $parsedUserResponse = json_decode($res->getBody(),true);
        if($res->getStatusCode() !== 200){throw new GraphException('User creation error: '.$parsedUserResponse['error']['message'],$res->getStatusCode());}
        $userId=$parsedUserResponse['User']['id'];
        //Set basic user permissions
        $everyonePermissionRes = $this->forward('/acl/permission/',json_encode([
            "Permission"=>[
                "group"       => Group::$everyoneGroupName,
                "permissions" => $this->basicPermissions
            ]
        ]),'PUT');
        if($everyonePermissionRes->getStatusCode() !== 200)throw new GraphException('Error when creating default user permissions');
        $groupInfo = json_decode($everyonePermissionRes->getBody(),true)['PermissionSet'];

        //Create admin application
        $appCreateRes = $this->forward('/apps',json_encode(["App"=>["appName"=>'GrapheneAdmin',"appAuthor"=>'Graphene team']]));
        $appCreated = json_decode($appCreateRes->getBody(),true);
        if($appCreateRes->getStatusCode() !== 200)
            throw new GraphException('Error when creating management app: '.$appCreated['error']['message'],$appCreated['error']['errorCode']); $apiKey=$appCreated['App']['apiKey'];
        $appPermissionsRes=$this->forward('/acl/app/permission/',json_encode([
            "App"=>[
                "apiKey"=>$apiKey,
                "permissions"=>$this->adminAppPermission
            ]
        ]),'PUT');
        if($appPermissionsRes->getStatusCode() !== 200)throw new GraphException('Error when creating management app permissions');
        $appInfo=json_decode($appPermissionsRes->getBody(),true)['App'];

        //Adding user to SUPER_ADMIN group [Enabling ACL]
        $groupRes = $this->forward('/acl/userGroup',json_encode(["UserGroup"=>["userId"=>$userId,"group"=>Group::$superUserGroupName]]));
        if($groupRes->getStatusCode() !== 200){throw new GraphException($groupRes->getBody(),$groupRes->getStatusCode());}

        $baseConfig=["Configuration"=>[
            "administrator"     => $req['admin'],
            "defaultUserGroup"  => $groupInfo,
            "adminApp"          => $appInfo
        ]];
        $this->response->setBody(json_encode($baseConfig,JSON_PRETTY_PRINT));

    }
    private $basicPermissions =[
        'AUTH.LOGIN',
        'AUTH.VALIDATE',
        'AUTH.LOGOUT',
        'USERS.VALIDATE',
        'ACL.PERMISSION_BY_USER',
        'ACL.PERMISSION_GROUP_READ',
        'ACL.GROUP_READ'
    ];

    private $adminAppPermission =['SYSTEM.*', 'USERS.*', 'AUTH.*', 'APPS.*', 'ACL.*'];
}