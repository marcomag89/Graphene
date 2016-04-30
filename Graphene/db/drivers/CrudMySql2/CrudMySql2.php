<?php
namespace Graphene\db\drivers;

use Graphene\db\drivers\mysql\ConfigManager;
use Graphene\db\drivers\mysql\ConnectionManager;
use Graphene\db\drivers\mysql\CoreManager;
use Graphene\db\drivers\mysql\ModelManager;
use Graphene\db\drivers\mysql\StorageRequest;
use Graphene\db\CrudDriver;

class CrudMySql2 implements CrudDriver{

    const INFO = 'mySql driver 0.2.1, for Graphene 0.2.x';
    protected $connectionManager;
    protected $configManager;

    public function __construct($dbConfig){
        $this->configManager     = new ConfigManager($dbConfig);
        $this->connectionManager = new ConnectionManager($this->configManager);
        $this->coreManager       = new CoreManager($this->configManager,$this->connectionManager);
    }

    public function getConnection(){
        return $this->connectionManager->getConnection();
    }

    public function getSettings(){return [];}

    public function getInfos(){
        return self::INFO;
    }

    public function create($json){
        $req   = new StorageRequest($json,$this->connectionManager);
        $this->coreManager->init($req->getModel());
        $query = MySqlQuery::getCreateQuery($this->configManager,$req);
        $this->connectionManager->query($query);
        return $this->read($req->cloneForSingleRead());
    }

    public function read($json, $query = null){
        $req   = new StorageRequest($json,$this->connectionManager,$query);
        $this->coreManager->init($req->getModel());
        $query = MySqlQuery::getReadQuery($this->configManager,$req);
        //\Log::debug($query);
        $res = $this->connectionManager->query($query);
        return $req->serializeResponse($res);
    }

    public function update($json){
        $req   = new StorageRequest($json,$this->connectionManager);
        $this->coreManager->init($req->getModel());
        $query = MySqlQuery::getUpdateQuery($this->configManager,$req);
        $this->connectionManager->query($query);
        return $this->read($req->cloneForSingleRead());
    }

    public function delete($json){
        $req   = new StorageRequest($json,$this->connectionManager);
        $this->coreManager->init($req->getModel());
        $query = MySqlQuery::getDeleteQuery($this->configManager,$req);
        $this->connectionManager->query($query);
        return true;
    }
}