<?php
namespace system;
use acl\Group;
use Graphene\controllers\Action;
use Graphene\controllers\exceptions\GraphException;

class Config extends Action {

    public function run() {
        $req=json_decode($this->request->getBody(),true)['Config'];

        //Creating user
        $res    = $this->forward('/users/user',["User"=>$req['admin']]);
        $userId = $res->getData()['User']['id'];

        //Set basic user permissions
        $everyonePermissionRes = $this->forward('/acl/permission/',[
            "Permission"=>[
                "group"       => Group::$everyoneGroupName,
                "permissions" => $this->basicPermissions
            ]
        ],'PUT');
        $groupInfo = $everyonePermissionRes->getData()['PermissionSet'];


        //Create admin application
        $appCreateRes = $this->forward('/apps',[
            "App"=>[
                "appName"   => 'GrapheneAdmin',
                "appAuthor" => 'Graphene team'
            ]
        ]);

        $appCreated        = $appCreateRes->getData();
        $apiKey            = $appCreated['App']['apiKey'];
        $appPermissionsRes = $this->forward('/acl/app/permission/',[
            "App"=>[
                "apiKey"      => $apiKey,
                "permissions" => $this->adminAppPermission
            ]
        ], 'PUT');
        $appInfo=$appPermissionsRes->getData()['App'];

        //Adding user to SUPER_ADMIN group [Enabling ACL]
        $groupRes = $this->forward('/acl/userGroup',[
            "UserGroup"=>[
                "userId"=>$userId,
                "group"=>Group::$superUserGroupName
            ]
        ]);

        $baseConfig=[
            "Configuration"=>[
                "administrator"     => $req['admin'],
                "defaultUserGroup"  => $groupInfo,
                "adminApp"          => $appInfo
            ]
        ];

        $this->response->setData($baseConfig);
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