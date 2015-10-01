<?php
namespace acl;

use Graphene\models\Model;
use Graphene\controllers\exceptions\GraphException;

class Group extends Model{
    public function defineStruct(){
        return array(
            'name'   =>  Model::STRING .Model::MAX_LEN.'200'.Model::NOT_EMPTY.Model::NOT_NULL.Model::UNIQUE,
            'parent' =>  Model::UID    .Model::NOT_NULL     .Model::NOT_EMPTY
        );
    }
    public function setContent($content){
        $this->content = array();
        if(array_key_exists ('name',    $content)) $this->setName ($content['name'] );
        if(array_key_exists ('parent',  $content)) $this->setParent ($content['parent'] );
        if(array_key_exists ('id',      $content)) $this->content['id']      = $content['id'];
        if(array_key_exists ('version', $content)) $this->content['version'] = $content['version'];
    }

    public function setParent($parent){
        if($parent === self::$superUserGroupName)throw new GraphException('cannot set system group '.self::$superUserGroupName.' as parent');
        if( $parent === null || $parent === ''){
            $this->content['parent'] = self::$everyoneGroupName;
        }
        else{
            $this->content['parent'] = self::getGroupId($parent);
        }
    }

    public function setName($name){
        $this->content['name'] = self::standardizeGroupName($name);
    }
    public function getParent(){
        return self::getGroupName($this->content['parent']);
    }

    public function getName(){
        return $this->content['name'];
    }

    public static function getGroupId($groupName){
        if(str_starts_with($groupName,self::$idPrefix)) return $groupName;
        $name  = self::standardizeGroupName($groupName);
        if($groupName === self::$superUserGroupName || $groupName===self::$everyoneGroupName){
            return $groupName;
        }else{
            $group = new Group();
            $group->setName($name);
            $rGroup = $group->read();
            if($rGroup === null)throw new GraphException('group '.$name.' not found',400);
            return $rGroup->getId();
        }
    }

    public static function getGroupName($groupId){
        if(!str_starts_with($groupId,self::$idPrefix)) return $groupId;

        if($groupId === self::$superUserGroupName || $groupId===self::$everyoneGroupName){
            return $groupId;
        }else{
            $group = new Group();
            $group->setId($groupId);
            $rGroup=$group->read();
            if($rGroup === null) throw new GraphException('group id '.$groupId.' not found',400);
            return $rGroup->getName();
        }
    }

    public static function standardizeGroupName($groupName){
        return strtoupper(str_replace(' ','_',$groupName));
    }
    public function onCreate(){
        $this->securityChecks();
    }
    public function onUpdate(){
        $this->securityChecks();
    }
    /*
    public function onRead(){
        $this->securityChecks();
    }
    */
    public function onDelete(){
        $this->standardize();
        //Controllo gruppi di sistema
        if($this->getName()   === Group::$superUserGroupName )  throw new GraphException('cannot delete system group: '.self::$superUserGroupName,400);
        if($this->getName()   === Group::$everyoneGroupName )   throw new GraphException('cannot delete system group: '.self::$everyoneGroupName,400);

        //Controllo esistenza gruppo
        $fGroup =  new Group();
        $fGroup -> setName($this->getName());
        $readedGroup = $fGroup->read();
        if($readedGroup === null) throw new GraphException('Group '.$this->getName().' not found');

        //Controllo gruppi figlio di questo gruppo
        $cGroup = new Group();
        $cGroup->setParent($this->getName());
        if($cGroup->read(true) !== null) throw new GraphException('Cannot delete group '.$this->getName().' with child groups');

        //Controllo esistenza utenti assegnati al gruppo
        $uGroup = new UserGroup();
        $uGroup->setGroup($this->getName());
        if($uGroup ->read(true) !== null) throw new GraphException('Cannot delete group '.$this->getName().' with users');

        $this->setContent($readedGroup->getContent());
    }

    public function securityChecks(){
        $this->standardize();
        $parentGroup = self::getGroupName($this->getParent());
        if($this->getName()  === Group::$superUserGroupName )  throw new GraphException('cannot use system group name: '.self::$superUserGroupName.' for group',400);
        if($this->getName()  === Group::$everyoneGroupName  )  throw new GraphException('cannot create system group: '.Group::$everyoneGroupName.' for group name',400);
        if($parentGroup      === Group::$superUserGroupName )  throw new GraphException('cannot use system group: '.Group::$superUserGroupName.' as parent',400);

        $fGroup = new Group();
        $fGroup->setName($this->getName());
        if($fGroup->read() !== null) throw new GraphException('group '.$this->getName().' already exists',400);

        if($this->getParent() !== Group::$everyoneGroupName){
            $fGroup = new Group();
            $fGroup->setName($this->getParent());
            if($fGroup->read() === null) throw new GraphException('parent group: '.$this->getParent().' does not exists',400);
        }
    }

    public function onSend(){
        $this->content['parent']=self::getGroupName($this->content['parent']);
    }
    public function getIdPrefix(){
        return self::$idPrefix;
    }
    public static $idPrefix ='IDGR_';
    public static $superUserGroupName = 'SUPER_USER';
    public static $everyoneGroupName  = 'EVERYONE';
}