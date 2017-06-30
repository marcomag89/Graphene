<?php
namespace Graphene\db\drivers\mysql\controllers;

use Exception;
use Graphene\db\drivers\mysql\utils\ExceptionConverter;
use Graphene\Graphene;
use Graphene\controllers\exceptions\GraphException;
use Graphene\controllers\exceptions\ExceptionCodes;
use \PDO;
use \PDOStatement;

class ConnectionManager {
    private $queryCounter = 0;
    /**
     * @var PDO
     */
    private $connection = null;
    /**
     * @var ConfigManager
     */
    private $configManager = null;

    private $logger = null;

    public function __construct($configManager) {
        $this->configManager = $configManager;
        $this->logger = Graphene::getLogger(ConnectionManager::class);
    }

    /**
     * @return PDO|null
     */
    public function getConnection() {
        if ($this->connection === null) {
            $this->connection = $this->connect();
        }

        return $this->connection;
    }

    private function connect() {

        try {
            try {
                $conString = 'mysql:host=' . $this->configManager->getUrl() . ';port=' . $this->configManager->getDbPort() . '; dbname=' . $this->configManager->getDbName();
                $user = $this->configManager->getUserName();
                $pwd = $this->configManager->getPassword();
                $this->connection = new PDO($conString, $user, $pwd);
            } catch (\Exception $e) {
                $conString = 'mysql:host=' . $this->configManager->getUrl() . ':' . $this->configManager->getDbPort() . '; dbname=' . $this->configManager->getDbName();
                $user = $this->configManager->getUserName();
                $pwd = $this->configManager->getPassword();
                $this->connection = new PDO($conString, $user, $pwd);
            }

            //Log::debug('mySql connection success as: '.$this->configManager->getUserName());
            return $this->connection;
        } catch (Exception $e) {
            $this->logger->error('mySql connection fails: ' . $e->getMessage());
            $this->connection = null;
            $ex = new GraphException('Error on mysql connection: ' . $e->getMessage(), ExceptionCodes::DRIVER_CONNECTION, 500);
            ExceptionConverter::throwException($ex);
        }
    }

    public function getPrefix() {
        return $this->configManager->getPrefix();
    }

    /**
     * @param $query
     *
     * @return array
     * @throws GraphException
     */
    public function query($query) {
        $this->queryCounter++;
        //echo $query."\n";
        $res = $this->connection->query($query);
        $err = $this->connection->errorInfo();
        if (strcasecmp($err[0], '00000') != 0) {
            $this->logger->error("\n" . 'MySql exception: ' . $err[2] . "\n" . 'Query no' . $this->queryCounter . "\n__________\n" . $query . "\n");
            ExceptionConverter::throwException($err);
            //throw new GraphException('mySql exception on query no.' . $this->queryCounter . ', see log for more info', ExceptionCodes::DRIVER_CREATE, 500);
        } else if ($res instanceof PDOStatement) {
            $results = [];
            $i = 0;
            while ($row = $res->fetch(PDO::FETCH_ASSOC)) {
                foreach ($row as $rk => $rv) {
                    $results[$i][$rk] = $rv;
                }
                $i++;
            }

            return $results;
        } else {
            $this->logger->error('Unexpected result for query ' . $this->queryCounter . "\n__________\n" . $query . "\n");
            throw new GraphException('mySql exception on query no.' . $this->queryCounter . ', see log for more info', ExceptionCodes::DRIVER_CREATE, 500);
        }
    }
}