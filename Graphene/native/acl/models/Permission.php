<?php
namespace acl;

use Graphene\controllers\exceptions\GraphException;
use Graphene\models\Model;

class Permission extends Model{
    public function defineStruct(){
        return array(
            'group'   =>  Model::UID     .Model::NOT_EMPTY.Model::NOT_NULL,
            'action'  =>  Model::STRING  .Model::MAX_LEN.'200'.Model::NOT_EMPTY.Model::NOT_NULL
        );
    }

    public function standardize(){
        $this->content['group'] = Group::standardizeGroupName( $this->content['group']);
    }

    public function setContent($content){
        $this->content = array();
        if(array_key_exists ('group',   $content)) $this->setGroup($content['group']);
        if(array_key_exists ('action',  $content)) $this->content['action']  = $content['action'];
        if(array_key_exists ('id',      $content)) $this->content['id']      = $content['id'];
        if(array_key_exists ('version', $content)) $this->content['version'] = $content['version'];
    }

    public function setGroup($group){
        if( $group === null || $group === '') {$this->content['group'] = Group::$everyoneGroupName;}
        else $this->content['group'] = Group::getGroupId($group);
    }

    public function getGroup(){
        return Group::getGroupName($this->content['group']);
    }

    public function onSend(){
        $this->content['group']=Group::getGroupName($this->content['group']);
    }

    public function onCreate(){
        $this->securityChecks();
    }

    public function onDelete(){
        $prm = new Permission();
        $prm->setGroup($this->getGroup());
        $prm->setAction($this->getAction());
        $oPrm=$prm->read();
        if($oPrm === null)throw new GraphException('Permission '.$this->getAction().' not found for '.$this->getGroup(),400);
        else{
            $this->content['id']      = $oPrm->getId();
            $this->content['version'] = $oPrm->getVersion();
        }
    }

    public function securityChecks(){
        //Check if superuser already assigned
        if($this->getGroup() === Group::$superUserGroupName) throw new GraphException('cannot edit '.Group::$superUserGroupName.' group permissions',300);

        //Check if already assigned
        if($this->read() !== null)   throw new GraphException('permission '.$this->getAction().' already assigned at '.$this->getGroup(),400);
    }
}