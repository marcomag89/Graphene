<?php
namespace Graphene\db\drivers\mysql;

use \Log;

class ConfigManager
{
    public function __construct($config)
    {
        $this->url = $config['host'];
        $this->dbName = $config['dbName'];
        $this->userName = $config['username'];
        $this->prefix = $config['prefix'];
        $this->password = $config['password'];
        Log::debug('MySql driver setup completed');
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

    private $url, $dbName, $userName, $prefix, $password;
}