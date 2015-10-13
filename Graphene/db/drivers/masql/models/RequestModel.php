<?php
namespace Graphene\db\drivers\mysql;


use Graphene\db\drivers\MySqlTypes;
use Graphene\models\Model;

class RequestModel{
    /**
     * @param $structure
     * @param $content
     * @param $domain
     * @param ConnectionManager $connection
     */
    public function __construct($structure,$content,$domain,$connection){
        $this -> connectionManager  = $connection;
        $this -> structure          = $structure;
        $this -> content            = $content;
        $this -> domain             = $domain;
        $this -> flatValues         = $this->contentToFlatArray ($this->content);
        $this -> flatTypes          = $this->contentToFlatArray ($this->structure);
        $this -> convertedFlatTypes = MySqlTypes::convertFlatStructTypes($this->flatTypes);
        $this -> modelTableName     = strtolower(str_replace('.', '_', $domain) . '_model');
    }

    public function getStructure(){
        return $this->struct;
    }

    public function getContent(){
        return $this->content;
    }

    public function getDomain(){
        return $this->domain;
    }

    public function getFlatValues(){
        return $this->flatValues;
    }

    public function getFlatDbValues(){
        $ret=[];
        foreach($this->getFlatValues() as $key=>$value){
            $ret[$key]=$this->connectionManager->getConnection()->quote($value);
        }
        return $ret;
    }

    public function getFlatTypes(){
        return $this->flatTypes;
    }

    public function getFlatColumnsDbTypes(){
        return $this->convertedFlatTypes;
    }

    public function getModelTableName(){
       return $this->modelTableName;
    }

    public function getSearchableFields(){
        return $this->getFieldsByType(Model::SEARCHABLE);
    }

    public function getUniques(){
        return $this->getFieldsByType(Model::UNIQUE);
    }
    public function haveField($needle){
        $vals = $this->getFlatTypes();
        foreach($vals as $field=>$value){
            if(strtolower($needle) === strtolower($field)){return true;}
        }
        return false;
    }

    public function getFieldsByType($fieldType){
        $cols=$this->getFlatTypes();
        $ret=[];
        foreach ($cols as $col => $type) {
            if (in_array(substr($fieldType, strlen(Model::CHECK_SEP)), explode(Model::CHECK_SEP, $type)))
                $ret[]=$col;
        }
        return $ret;
    }

    private function contentToFlatArray($content, &$path = '', &$schema = null){
        if ($schema == null) $schema = array();
        foreach ($content as $key => $value) {
            if (strcmp($path, '') == 0) $tmpPath = $key;
            else $tmpPath = $path . '_' . $key;

            if (is_array($value) && $content != NULL) $this->contentToFlatArray($value, $tmpPath, $schema);
            else {$schema[$tmpPath] = $value;}
        }
        return $schema;
    }

    private
        $flatValues,
        $flatTypes,
        $modelTableName,
        $structure,
        $content,
        $domain,
        $convertedFlatTypes,
        /**
         * @var ConnectionManager
         */
        $connectionManager;
}