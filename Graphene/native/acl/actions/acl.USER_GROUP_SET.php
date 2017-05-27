<?php
namespace acl;

use Graphene\controllers\Action;

class UserGroupSet extends Action {
    public function run() {
        //Lettura gruppi utente
        $request = $this->request->getData()['UserGroup'];
        $uGroups = $this->forward('/acl/userGroup/byUser/' . $request['userId'])->getData()['UserGroups'];
        $this->doAdd($request['userId'], $this->getNotOwned($uGroups, $request['groups']));
        $this->doRemove($request['userId'], $this->getNotOwned($request['groups'], $uGroups));
        $this->send($uGroups = $this->forward('/acl/userGroup/byUser/' . $request['userId'])->getData());
    }

    private function doAdd($userId, $groups) {
        foreach ($groups as $group) {
            $this->forward('/acl/userGroup', [
                "UserGroup" => [
                    "userId" => $userId,
                    "group"  => $group
                ]
            ], 'POST');
        }
    }

    private function getNotOwned($owned, $compares) {
        $ret = [];
        foreach ($compares as $compare) {
            if (!in_array($compare, $owned)) {
                $ret[] = $compare;
            }
        }

        return $ret;
    }

    private function doRemove($userId, $groups) {
        foreach ($groups as $group) {
            if ($group != Group::$superUserGroupName) {
                try {
                    $this->forward('/acl/userGroup', [
                        "UserGroup" => [
                            "userId" => $userId,
                            "group"  => $group
                        ]
                    ], 'DELETE');
                } catch (\Exception $e) {
                    Graphene::getLogger()->error($e->getMessage());
                }
            }
        }
    }
}