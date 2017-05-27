<?php
namespace system;

use acl\Group;
use Graphene\controllers\Action;
use Graphene\controllers\exceptions\GraphException;

class Config extends Action {

    private $errors;
    private $log;
    private $basicPermissions = [
        'AUTH.LOGIN',
        'AUTH.VALIDATE',
        'AUTH.LOGOUT',
        'USERS.VALIDATE',
        'USERS.VALIDATE_EDITING_KEY',
        'USERS.UPDATE_USER_BY_KEY',
        'ACL.PERMISSION_BY_USER',
        'ACL.PERMISSION_GROUP_READ',
        'ACL.GROUP_READ'
    ];
    private $adminAppPermission = ['SYSTEM.*', 'USERS.*', 'AUTH.*', 'APPS.*', 'ACL.*'];

    public function run() {
        $this->errors = [];
        $this->log = [];
        $configRequest = $this->checkRequest();

        //Creating groups
        $groups = $this->createGroups($configRequest['groups']);

        //Creating admin App
        $app = $this->createApp($configRequest['app']);

        //Creating user
        $admin = $this->createAdmin($configRequest['admin']);

        $baseConfig = [
            "Configuration" => [
                "admin"  => $admin,
                "groups" => $groups,
                "app"    => $app,
                "errors" => $this->errors,
                "logs"   => $this->log
            ]
        ];

        $this->send($baseConfig);
    }

    private function checkRequest() {
        $configRequest = $this->request->getData();
        if (!array_key_exists('Config', $configRequest)) throw new GraphException('Config request is not valid');
        if (!array_key_exists('admin', $configRequest['Config'])) throw new GraphException('cannot config application without admin user');
        if (!array_key_exists('app', $configRequest['Config'])) throw new GraphException('cannot config application without admin application');
        return $configRequest = $this->request->getData()['Config'];
    }

    private function createGroups($groups) {
        $createdGroups = [Group::$everyoneGroupName => ['permissions' => $this->basicPermissions]];
        //Set basic user permissions
        $this->forward('/acl/permission/', [
            "Permission" => [
                "group"       => Group::$everyoneGroupName,
                "permissions" => $this->basicPermissions
            ]
        ], 'PUT');

        if ($groups !== null) {
            foreach ($groups as $grName => $grSettings) {
                try {
                    $this->forward('/acl/group', [
                        'Group' => [
                            'name'   => $grName,
                            'parent' => $grSettings['parent']
                        ]
                    ]);
                    $this->log[] = 'group ' . $grName . ' created successfully';
                    $createdGroups[$grName] = [
                        'parent' => array_key_exists('parent', $grSettings) ?
                            $grSettings['parent'] :
                            Group::$everyoneGroupName
                    ];
                } catch (\Exception $e) {
                    $this->errors[] = 'error when creating group ' . $grName . ': ' . $e->getMessage();
                }
                try {
                    $this->forward('/acl/permission', [
                        'Permission' => [
                            'group'       => $grName,
                            'permissions' => $grSettings['permissions']
                        ]
                    ], 'PUT');
                    $this->log[] = 'perrmissions ' . join(', ', $grSettings['permissions']) . ' assigned successfully at group ' . $grName;
                    $createdGroups[$grName] = [
                        'permissions' => array_key_exists('permissions', $grSettings) ?
                            $grSettings['permissions'] :
                            []
                    ];
                } catch (\Exception $e1) {
                    $this->errors[] = 'error when assign perrmissions ' . join(', ', $grSettings['permissions']) . ' at group ' . $grName . ': ' . $e1->getMessage();
                }
            }
        }
        return $createdGroups;
    }

    private function createApp($app) {
        //Create admin application
        $appCreateRes = $this->forward('/apps', [
            "App" => [
                'appName'   => $app['name'],
                'appAuthor' => $app['author'],
                'apiKey'    => $app['apiKey']
            ]
        ]);
        $createdApp = $appCreateRes->getData()['App'];

        foreach ($app['permissions'] as $permission) {
            $this->adminAppPermission[] = $permission;
        }
        $appPermissionsRes = $this->forward('/acl/app/permission/', [
            "App" => [
                "apiKey"      => $createdApp['apiKey'],
                "permissions" => $this->adminAppPermission
            ]
        ], 'PUT');
        return $createdApp;
    }

    private function createAdmin($admin) {
        $res = $this->forward('/users/external', ["UserExternal" => $admin]);
        //Adding user to SUPER_ADMIN group [Enabling ACL]
        $user = $res->getData()['User'];
        $this->forward('/acl/userGroup', [
            "UserGroup" => [
                "userId" => $user['id'],
                "group"  => Group::$superUserGroupName
            ]
        ]);
        return $user;
    }
}