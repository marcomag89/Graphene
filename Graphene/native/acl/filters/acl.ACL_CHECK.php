<?php
namespace acl;

use Graphene\controllers\Filter;
use Log;

class AclCheck extends Filter {
    private static $cache = null;
    private static $sysAllow = false;
    private $aclExceptions = [
        'SYSTEM.GET_CLIENT_STATUS',
        'SYSTEM.DOC_ACTION_BY_NAME',
        'SYSTEM.STAT',
    ];

    public function run() {
        if (self::$cache == null) {
            self::$cache = [
                'app-info'    => [],
                'matches'     => [],
                'permissions' => [],
                'groups'      => [],
                'enabled'     => [],
                'allows'      => []
            ];
        }

        $actionName = $this->action->getUniqueActionName();
        $user = $this->request->getContextPar('user');
        $apiKey = $this->request->getHeader('api-key');
        $appInfo = $this->loadAppInfo($apiKey);
        $permissions = $this->loadPermissions($user);
        $groups = $this->loadGroups($user);

        if (array_key_exists('permissions', $appInfo))
            $appPermissions = $appInfo['permissions'];
        else $appPermissions = [];
        $filterEnabled = $this->isAclEnabled();

        if (!self::$sysAllow && //Filtro gia superato
            $filterEnabled && //Filtro abilitato
            (!$this->enabledTo($actionName, $appPermissions) || //Permesso applicazione non trovato
             (array_search(Group::$superUserGroupName, $groups) === false && //Utente non super_user
              !$this->enabledTo($actionName, $permissions)                   //Permesso utente non trovato
                )
            )
        ) {
            $this->status = 403;
            $this->message = 'Access denied to action: ' . $this->action->getUniqueActionName();
        } else {
            self::$sysAllow = true;
        }
        $this->request->setContextPar(ACL::ENABLED, $filterEnabled);
        $this->request->setContextPar(ACL::APP_INFO, $appInfo);
        $this->request->setContextPar(ACL::PERMISSIONS, $permissions);
        $this->request->setContextPar(ACL::GROUPS, $groups);
    }

    private function loadAppInfo($apiKey) {
        if (array_key_exists($apiKey, self::$cache['app-info'])) {
            return self::$cache['app-info'][$apiKey];
        }
        self::$cache['app-info'][$apiKey] = [];
        if ($apiKey !== null) {
            self::$cache['app-info'][$apiKey] = $this->forward('/acl/app/withPermission/' . $apiKey)->getData()['App'];
        }
        return self::$cache['app-info'][$apiKey];
    }

    private function loadPermissions($user) {
        if ($user === null) $userId = 'anonimous'; else $userId = $user['id'];
        if (array_key_exists($userId, self::$cache['permissions'])) return self::$cache['permissions'][$userId];
        $permissions = [];

        if ($user !== null) {
            $res = $this->forward('/acl/permission/byUser/' . $user['id']);
            if ($res->getStatusCode() === 200) {
                $permissions = json_decode($res->getBody(), true)['PermissionSet'];
                self::$cache['permissions'][$userId] = $permissions;
            } else {
                Log::err('error when loading user permissions');
            }
        } else {
            $permissions = $this->forward('/acl/permission/' . Group::$everyoneGroupName)->getData()['PermissionSet'];
            self::$cache['permissions'][$userId] = $permissions;
        }
        return self::$cache['permissions'][$userId];
    }

    private function loadGroups($user) {
        if ($user === null) {
            $userId = 'anonimous';
        } else {
            $userId = $user['id'];
        }
        if (array_key_exists($userId, self::$cache['groups']))
            return self::$cache['groups'][$userId];

        if ($user !== null) {
            $res = $this->forward('/acl/userGroup/byUser/' . $user['id']);
            if ($res->getStatusCode() === 200) {
                $groups = json_decode($res->getBody(), true)['UserGroups'];
                self::$cache['groups'][$userId] = $groups;
            } else {
                Log::err('error when loading user groups');
                self::$cache['groups'][$userId] = [];
            }
        } else {
            self::$cache['groups'][$userId] = [Group::$everyoneGroupName];
        }
        return self::$cache['groups'][$userId];
    }

    private function isAclEnabled() {
        if (array_key_exists('bool', self::$cache['enabled'])) return self::$cache['enabled']['bool'];
        else self::$cache['enable']['bool'] = false;
        $res = $this->forward('/acl/userGroup/byGroup/' . Group::$superUserGroupName);
        if ($res->getStatusCode() === 200) {
            $users = json_decode($res->getBody(), true)['GroupUsers']['users'];
            self::$cache['enabled']['bool'] = (count($users) > 0);
        }
        return self::$cache['enabled']['bool'];
    }

    private function enabledTo($actionName, $permissionList) {
        foreach ($this->aclExceptions as $exc) {
            if ($this->matches($exc, $actionName)) {
                return true;
            }
        }
        foreach ($permissionList as $prm) {
            if ($this->matches($prm, $actionName)) {
                return true;
            }
        }
        return false;
    }

    private function matches($needle, $action) {
        if (array_key_exists($needle . '_' . $action, self::$cache['matches'])) return self::$cache['matches'][$needle . '_' . $action];
        self::$cache['matches'][$needle . '_' . $action] = false;
        $tneedle = strtoupper($needle);
        $taction = strtoupper($action);

        if (str_ends_with($tneedle, '.*') && str_starts_with($taction, substr($tneedle, 0, strlen($tneedle - 1))))
            self::$cache['matches'][$needle . '_' . $action] = true;
        else if ($tneedle === $taction) self::$cache['matches'][$needle . '_' . $action] = true;
        else self::$cache['matches'][$needle . '_' . $action] = false;
        return self::$cache['matches'][$needle . '_' . $action];
    }
}

class ACL {
    const ENABLED = 'acl-enabled';
    const APP_INFO = 'acl-app-info';
    const PERMISSIONS = 'acl-permissions';
    const GROUPS = 'acl-groups';
}
