<?php
namespace Graphene\db\drivers;


use Graphene\db\drivers\mysql\ConfigManager;
use Graphene\db\drivers\mysql\RequestModel;
use Graphene\db\drivers\mysql\StorageRequest;

class MySqlQuery
{
    public static function getDbTableIdentifier($settings,$model){
        $q = self::$TABLE_IDENTIFIER_MODEL;
        $q = str_replace('<dbname>',    $settings->getDbname(), $q);
        $q = str_replace('<tableName>', $settings->getPrefix().$model->getModelTableName(), $q);
        return $q;
    }

    public static function getTableExistsQuery($settings,$model){
        return str_replace('<identifier>',self::getDbTableIdentifier($settings,$model),self::$TABLE_EXISTS_QUERY_MODEL);
    }

    /**
     * @param ConfigManager $settings
     * @param RequestModel $model
     * @return string
     */
    public static function getTableCreateQuery($settings,$model){
        $cols    = $model->getFlatColumnsDbTypes();
        $uniqArr = $model->getUniques();
        $uniColsStr = '';
        foreach($uniqArr as $uniKey){
            $uniColsStr = $uniColsStr . ', UNIQUE INDEX `' . $uniKey . '_UNIQUE` (`' . $uniKey . '` ASC)';
        }
        $colsStr = '';
        foreach ($cols as $col => $type) {
            $colsStr = $colsStr . '`' . $col . '` ' . $type . ',';
        }
        $q = self::$CREATE_TABLE_QUERY_MODEL;
        $q = str_replace('<identifier>', self::getDbTableIdentifier($settings,$model), $q);
        $q = str_replace('<fields>', $colsStr, $q);
        $q = str_replace('<uniqueIndexes>', $uniColsStr, $q);
        return $q;
    }

    /**
     * @param ConfigManager $settings
     * @param StorageRequest $request
     * @return string
     */
    public static function getCreateQuery($settings, $request){
        $cols = $request->getModel()->getFlatDbValues();
        $colNames = '';
        $colValues = '';
        foreach ($cols as $name => $value) {
            $colNames  .= '`'.$name .'`,';
            $colValues .= $value.',';
        }

        $colNames  = rtrim($colNames,',');
        $colValues = rtrim($colValues,',');
        $q = self::$CREATE_QUERY_MODEL;
        $q = str_replace('<identifier>', self::getDbTableIdentifier($settings,$request->getModel()), $q);
        $q = str_replace('<fields>',     $colNames, $q);
        $q = str_replace('<values>',     $colValues, $q);
        return $q;
    }

    /**
     * @param ConfigManager $settings
     * @param StorageRequest $request
     * @return string
     * @throws GraphException
     */
    public static function getReadQuery($settings, $request){
        $q = self::$SELECT_QUERY_MODEL;
        $select    = self::getSelect($request,$settings);
        $condition = self::getCondition($request);
        $paging    = self::getPaging($request);
        $q = str_replace('<identifier>', $select,    $q);
        $q = str_replace('<cond>',       $condition, $q);
        $q = str_replace('<paging>',     $paging,    $q);
        //echo $q."\n\n";
        return $q;
    }


    /**
     * @param ConfigManager $settings
     * @param StorageRequest $request
     * @return string
     * @throws GraphException
     */
    public static function getUpdateQuery($settings, $request){
        $q = self::$UPDATE_QUERY_MODEL;
        $cols = $request->getModel()->getFlatDbValues();
        $kv = ' ';
        foreach ($cols as $label => $value) {
            if (! strcasecmp($label, 'id') == 0) {
                $kv .= '`' . $label . '`=' .$value . ',';
            }
        }
        $kv=rtrim($kv,',');
        $q = str_replace('<identifier>', self::getDbTableIdentifier($settings,$request->getModel()), $q);
        $q = str_replace('<kv>', $kv, $q);
        $q = str_replace('<id>', $cols['id'], $q);
        return $q;
    }

    /**
     * @param ConfigManager $settings
     * @param StorageRequest $request
     * @return string
     * @throws GraphException
     */
    public static function getDeleteQuery($settings, $request){
        $q = self::$DELETE_QUERY_MODEL;
        $cols = $request->getModel()->getFlatDbValues();
        $q = str_replace('<identifier>', self::getDbTableIdentifier($settings,$request->getModel()), $q);
        $q = str_replace('<id>', $cols['id'], $q);
        return $q;
    }

    /**
     * @param StorageRequest $request
     * @return string
     */
    private static function getPaging($request){
        $pageSize  = $request->getRequestSettings()['pageSize'];
        $page      = $request->getRequestSettings()['page'];
        if($pageSize>0){
            $limit  = 'LIMIT '.$pageSize;
            $offset = 'OFFSET '.(($page-1) * $pageSize);
        }else{
            $limit  = '';
            $offset = '';
        }

        //ORDER BY `field` DESC | ASC
        $sort='';
        if($request->hasSort()){
            $sortBy   = $request->getRequestSettings()['sort']['by'];
            $sortMode = $request->getRequestSettings()['sort']['mode'];

            switch (strtoupper($sortMode)) {
                case 'ASC': {$sortMode='ASC';  break;}
                case 'DSC': {$sortMode='DESC'; break;}
                default   : {$sortMode='ASC';  break;}
            }
            $sort='ORDER BY `'.$sortBy.'` '.strtoupper($sortMode);
        }

        return $sort.' '.$limit.' '.$offset;
    }


        /**
     * @param StorageRequest $request
     * @return string
     */
    private static function getCondition($request){
        $where = '\'1\'=\'1\'';
        // where custom
        if($request->hasWhere()){
            $where.= $request->getRequestSettings()['where'];
        }

        //where in AND per operatori definiti
        if(!$request->hasSearch() && !$request->hasWhere()){
            $clauses =' AND';
            $fields  = $request->getModel()->getFlatDbValues();
            foreach($fields as $field=>$value){
                $op = $request->getOperator($field);
                $clauses .=' `'.$field.'` '.$op.' '.$value.' AND';
            }
            $clauses = rtrim($clauses,'AND');
            $where.=$clauses;
        }

        //Where per ricerche selvagge
        if($request->hasSearch() && count($request->getModel()->getSearchableFields()) === 0){
            $likes = '';
            $searchTherms = $request->getSearchTherms();
            $fields       = $request->getModel()->getFlatValues();
            foreach($fields as $field=>$value){
                if($field !== 'id' and $field !== 'version'){
                    foreach($searchTherms as $therm){
                        $likes.=' `'.$field.'` LIKE \'%'.$therm.'%\' OR';
                    }
                }
            }
            $likes = rtrim($likes,'OR');
            $where = '( '.$where.' ) AND ( '.$likes.' ) ';
        }

        return $where;
    }

    /**
     * @param  StorageRequest $request
     * @param  ConfigManager $settings
     * @return string
     */
    private static function getSelect($request,$settings){
        /* select base */
        $select = '';
        $searchableFields =$request->getModel()->getSearchableFields();
        if($request->hasSearch() && count($searchableFields)>0){
            $select.='( ';
            foreach($searchableFields as $field){
                $toAdd = self::$SELECT_QUERY_MODEL;
                $likes = '';
                $searchTherms= $request->getSearchTherms();
                foreach($searchTherms as $therm) {
                    $likes .= ' `' . $field . '` LIKE \'%' . $therm . '%\' OR';
                }
                $likes = rtrim($likes,'OR');
                $toAdd = str_replace('<identifier>', self::getDbTableIdentifier($settings,$request->getModel()), $toAdd);
                $toAdd = str_replace('<cond>',   '('.self::getCondition($request).') AND ('.$likes.')', $toAdd);
                $toAdd = str_replace('<paging>',  '', $toAdd);
                $toAdd.=' UNION ';
                $select .= $toAdd;
            }
            $select = rtrim($select,'UNION ');
            $select.=') AS search_scores';
        }else{
            $select = self::getDbTableIdentifier($settings,$request->getModel());
        }
        return $select;
    }

    private static $TABLE_IDENTIFIER_MODEL   = ' `<dbname>`.`<tableName>` ';
    private static $TABLE_EXISTS_QUERY_MODEL = 'SELECT 1 FROM <identifier> LIMIT 1;';
    private static $UPDATE_QUERY_MODEL       = 'UPDATE <identifier> SET <kv>  WHERE `id`=<id>';
    private static $SELECT_QUERY_MODEL       = 'SELECT * FROM <identifier> WHERE <cond> <paging>';
    private static $CREATE_TABLE_QUERY_MODEL = 'CREATE TABLE IF NOT EXISTS <identifier> ( <fields> PRIMARY KEY(`id`) <uniqueIndexes> ) ENGINE = InnoDB DEFAULT CHARACTER SET = utf8;';
    private static $CREATE_QUERY_MODEL       = 'INSERT INTO <identifier> (<fields>) VALUES (<values>);';
    private static $DELETE_QUERY_MODEL       = 'DELETE FROM <identifier> WHERE `id`=<id>;';

}