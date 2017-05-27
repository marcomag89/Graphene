<?php
namespace Graphene\db\drivers\mysql;

use \Log;

class ConfigManager
{
    private $url, $port, $dbName, $userName, $prefix, $password;

    public function __construct($config)
    {
        $this->url = $config['host'];
        $this->port = array_key_exists('port', $config) ? $config['port'] : '3306';
        $this->dbName = $config['dbName'];
        $this->userName = $config['username'];
        $this->prefix = $config['prefix'];
        $this->password = $config['password'];
        //Log::debug('MySql driver setup completed');
    }

    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @return mixed
     */
    public function getPrefix()
    {
        return $this->prefix;
    }

    /**
     * @return mixed
     */
    public function getUserName()
    {
        return $this->userName;
    }

    /**
     * @return mixed
     */
    public function getDbName()
    {
        return $this->dbName;
    }

    public function getDbPort() {
        return $this->port;
    }
}