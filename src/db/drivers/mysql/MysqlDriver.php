<?php

namespace Graphene\db\drivers\mysql;

use Graphene\db\CrudDriver;
use Graphene\db\drivers\mysql\controllers\ConfigManager;
use Graphene\db\drivers\mysql\controllers\ConnectionManager;
use Graphene\db\drivers\mysql\controllers\CoreManager;
use Graphene\db\drivers\mysql\models\StorageRequest;
use Graphene\db\drivers\mysql\utils\MySqlQuery;


class MysqlDriver implements CrudDriver
{

    const INFO = 'mySql driver 0.2.3, for Graphene 0.3.x';
    protected

        /**
         * @var ConnectionManager
         */
        $connectionManager,
        /**
         * @var ConfigManager
         */
        $configManager,
        /**
         * @var CoreManager
         */
        $coreManager;

    public function __construct($dbConfig)
    {
        $this->configManager = new ConfigManager($dbConfig);
        $this->connectionManager = new ConnectionManager($this->configManager);
        $this->coreManager = new CoreManager($this->configManager, $this->connectionManager);
    }

    public function getConnection()
    {
        return $this->connectionManager->getConnection();
    }

    public function getSettings()
    {
        return [];
    }

    public function getInfos()
    {
        return self::INFO;
    }

    public function create($json)
    {
        $req = new StorageRequest($json, $this->connectionManager);
        $this->coreManager->init($req->getModel());
        $query = MySqlQuery::getCreateQuery($this->configManager, $req);
        $this->connectionManager->query($query);

        return $this->read($req->cloneForSingleRead());
    }

    public function read($json, $query = null)
    {
        $req = new StorageRequest($json, $this->connectionManager, $query);
        $this->coreManager->init($req->getModel());
        $query = MySqlQuery::getReadQuery($this->configManager, $req);
        //Graphene::getLogger()->info($query);
        $res = $this->connectionManager->query($query);

        return $req->serializeResponse($res);
    }

    public function update($json)
    {
        $req = new StorageRequest($json, $this->connectionManager);
        $this->coreManager->init($req->getModel());
        $query = MySqlQuery::getUpdateQuery($this->configManager, $req);
        $this->connectionManager->query($query);

        return $this->read($req->cloneForSingleRead());
    }

    public function delete($json)
    {
        $req = new StorageRequest($json, $this->connectionManager);
        $this->coreManager->init($req->getModel());
        $query = MySqlQuery::getDeleteQuery($this->configManager, $req);
        $this->connectionManager->query($query);

        return true;
    }

    public function beginTransaction()
    {
        if ($this->transactionCount == 0) {
            $this->getConnection()->beginTransaction();
        }
        $this->transactionCount++;
    }

    public function commit()
    {
        if ($this->transactionCount == 1) {
            $this->getConnection()->commit();
        }
        $this->transactionCount--;
    }

    public function rollback()
    {
        if ($this->transactionCount == 1) {
            $this->getConnection()->rollBack();
        }
        $this->transactionCount--;
    }

    private $transactionCount = 0;
}