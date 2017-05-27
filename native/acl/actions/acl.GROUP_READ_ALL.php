<?php
namespace acl;

use Graphene\controllers\Action;

class GroupReadAll extends Action
{
    public function run(){
        $group  = new Group();
        $groups = $this->resultsToArray($group->read(true,null,1,0));
        $ret = $this->getGroupTree($groups);
        $this->response->setBody(json_encode(array("GroupsHierarchy"=>$ret),JSON_PRETTY_PRINT));
    }
    private function getGroupTree($groups, $rootGroup = null, &$node=null){
        if($rootGroup === null ) $rootGroup = Group::$everyoneGroupName;
        if($node      === null ) $node = array();
        foreach($groups as $group){
            if (
                (!is_array($rootGroup) && $group['parent'] === $rootGroup) ||
                (is_array($rootGroup)  && ($group['parent'] === $rootGroup['id']) )
            ){
                $node[$group['name']]  = array();
                $node[$group['name']]  = $this->getGroupTree($groups,$group, $node[$group['name']]);
            }
        }
        if($rootGroup == Group::$everyoneGroupName){
            return array(Group::$everyoneGroupName=>$node);
        }else{
            return $node;
        }
    }
    private function resultsToArray($results){
        if($results===null)return array();
        else{
            $ret = array();
            foreach($results as $result){
                $ret[] = $result->getContent();
            }
            return $ret;
        }
    }
}