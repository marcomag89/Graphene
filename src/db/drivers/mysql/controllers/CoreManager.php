<?php
namespace Graphene\db\drivers\mysql\controllers;
use Graphene\db\drivers\mysql\utils\MySqlQuery;
use \PDOStatement;

class CoreManager {
    public function __construct($configManager,$connectionManager){
        $this->configManager     = $configManager;
        $this->connectionManager = $connectionManager;
    }

    public function init($model){
        if(!$this->modelTableExists($model)){$this->createModelTable($model);}
    }

    /**
     * @param RequestModel $model
     * @throws GraphException
     */
    public function createModelTable($model){
        $q = MySqlQuery::getTableCreateQuery($this->configManager,$model);
        $this->connectionManager->getConnection()->query($q);
    }

    public function modelTableExists($model){
        $dbName = $this->configManager->getDbName();
        $q = MySqlQuery::getTableExistsQuery($this->configManager,$model);
        $exists=$this->connectionManager->getConnection()->query($q);
        if ($exists instanceof PDOStatement){
            $exists->fetchAll();
            return true;
        }
        else return false;
    }

    public function getTableName($model){
        return $this->connectionManager->getPrefix() . '_' . str_replace('.', '_', $model->getDomain()) . '_model';
    }

    /**
     * @var ConfigManager
     */
    private $configManager;

    /**
     * @var ConnectionManager
     */
    private $connectionManager;
}