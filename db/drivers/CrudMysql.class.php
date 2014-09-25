<?php
namespace Graphene\db\drivers;
use Graphene\db\CrudDriver;
use \mysqli;
use \Exception;

class CrudMySql implements CrudDriver
{

	public function __construct ($dbConfig)
	{
		log_write(self::LOG_NAME . 'driver loaded: ' . self::INFO);
		$this->url = $dbConfig->host;
		$this->dbname = $dbConfig->dbName;
		$this->username = $dbConfig->username;
		$this->prefix = $dbConfig->prefix;
		$this->password = $dbConfig->password;
	}
	/*
	 * ----------------------------------------
	 * INTERFACE ROUTINES
	 * ---------------------------------------
	 */
	// Connection
	public function getConnection ()
	{
		if ($this->connection == null) {
			$this->connection = new mysqli($this->url, $this->username, 
					$this->password, $this->dbname);
			if (mysqli_connect_errno()) {
				$this->connection = null;
				log_write(self::LOG_NAME . 'mySql connection fails');
				return false;
			} else {
				log_write(self::LOG_NAME . 'mySql connection success');
				return $this->connection;
			}
		} else {
			return $this->connection;
		}
	}
	
	/* TAG Create */
	public function create ($json, $id = null)
	{
		// log_write(self::LOG_NAME.'Creation request');
		if (! $this->InitDb($json))
			throw new Exception(
					'Error when init mySql JSON-CRUD table db for:' .
							 CrudDriver::INITALIZATION_ERROR_CODE);
		if (! $db = $this->getConnection())
			throw new Exception('Connection error', 
					CrudDriver::INITALIZATION_ERROR_CODE);
		if ($id == null)
			$id = uniqid();
		try {
			$db->autocommit(false);
			$db->begin_transaction();
			$db->query('SET @new_id=\'' . $id . '\';');
			$insertq = $this->getCreationInsertQueries($json);
			foreach ($insertq as $ins) {
				$db->query($ins);
			}
			// $db->end_transaction();
			$db->commit();
			$db->autocommit(true);
			$j = json_decode($json, true);
			$j['content']['id'] = $id;
			$json = json_encode($j, JSON_PRETTY_PRINT);
			log_write(
					self::LOG_NAME . 'Creation of: ' . $this->getDomain($json) .
							 ':' . $id . ' successful');
			return $json;
		} catch (Exception $e) {
			$db->rollback();
			log_write(self::LOG_NAME . 'Exception ' . $e->getMessage());
			throw new Exception(
					'Query error when insert rollback' .
							 CrudDriver::CREATING_GENERIC_ERROR_CODE);
		}
	}
	
	/* TAG Read */
	public function read ($json)
	{
		// log_write(self::LOG_NAME.'Reading request');
		if (! $this->InitDb($json))
			throw new Exception(
					'Error when init mySql JSON-CRUD table db for:' .
							 CrudDriver::INITALIZATION_ERROR_CODE);
		if (! $db = $this->getConnection())
			throw new Exception('Connection error', 
					CrudDriver::INITALIZATION_ERROR_CODE);
		$schema = $this->jsonToPathSchema($json);
		// se e settato l'id carica solo in base all' id, altrimenti esegue la
		// query in and;
		if (isset($schema['id']))
			return $this->readFromId($json);
		else
			return $this->readFromQuery($json);
	}
	
	/* TAG Update */
	public function update ($json)
	{
		// log_write(self::LOG_NAME.'Updating request');
		if (! $this->InitDb($json))
			throw new Exception(
					'Error when init mySql JSON-CRUD table db for:' .
							 CrudDriver::INITALIZATION_ERROR_CODE);
		if (! $db = $this->getConnection())
			throw new Exception('Connection error', 
					CrudDriver::INITALIZATION_ERROR_CODE);
		if (! $id = $this->getId($json))
			throw new Exception('Storage id not found', 
					CrudDriver::EDITING_GENERIC_ERROR_CODE);
		if (! $this->readFromId($json))
			throw new Exception('Element not found', 
					CrudDriver::EDITING_GENERIC_ERROR_CODE);
		
		try {
			$this->delete($json);
			$created = $this->create($json, $id);
			log_write(
					self::LOG_NAME . 'Update of: ' . $this->getDomain($json) .
							 ':' . $id . ' successful');
			return $created;
		} catch (Exception $e) {
			log_write(self::LOG_NAME . 'Update Exception ' . $e->getMessage());
			throw new Exception(
					'Editing error: ' . $e->getMessage() .
							 CrudDriver::EDITING_GENERIC_ERROR_CODE);
		}
	}
	
	/* TAG Delete */
	public function delete ($json)
	{
		// log_write(self::LOG_NAME.'Deletion request');
		if (! $this->InitDb($json))
			throw new Exception(
					'Error when init mySql JSON-CRUD table db for:' .
							 CrudDriver::INITALIZATION_ERROR_CODE);
		if (! $db = $this->getConnection())
			throw new Exception('Connection error', 
					CrudDriver::INITALIZATION_ERROR_CODE);
		if (! $id = $this->getId($json))
			throw new Exception('Storage id not found', 
					CrudDriver::DELETION_GENERIC_ERROR_CODE);
		$delQ = $this->getDeleteQuery($json);
		try {
			$db->autocommit(false);
			$db->begin_transaction();
			$db->query('SET SQL_SAFE_UPDATES = 0;');
			$db->query($delQ);
			$db->query('SET SQL_SAFE_UPDATES = 1;');
			$db->commit();
			$db->autocommit(true);
			log_write($delQ);
			log_write(
					self::LOG_NAME . 'Deletion of: ' . $this->getDomain($json) .
							 ':' . $id . ' successful');
			return true;
		} catch (Exception $e) {
			log_write(self::LOG_NAME . 'Delete Exception: ' . $e->getMessage());
			throw new Exception('error on deletion query: ' . $e->getMessage(), 
					CrudDriver::DELETION_GENERIC_ERROR_CODE);
		}
	}

	public function getSettings ()
	{}

	public function getInfos ()
	{
		return self::INFO;
	}
	/*
	 * ----------------------------------------
	 * CLASS ROUTINES
	 * ---------------------------------------
	 *
	 */
	// TAG read from id
	private function readFromId ($json)
	{
		if (! $this->InitDb($json))
			throw new Exception(
					'Error when init mySql JSON-CRUD table db for:' .
							 CrudDriver::INITALIZATION_ERROR_CODE);
		if (! $db = $this->getConnection())
			throw new Exception('Connection error', 
					CrudDriver::INITALIZATION_ERROR_CODE);
		$result = $db->query($this->getReadIdQuery($json));
		$jsonRet = $this->resultSetToJson($json, $result);
		log_write(
				self::LOG_NAME . 'Read of: ' . $this->getDomain($json) . ':' .
						 $this->getId($json) . ' successful');
		return $jsonRet;
	}
	// TAG read from query
	private function readFromQuery ($json)
	{
		log_write(
				self::LOG_NAME . 'Reading from query: ' .
						 json_encode(json_decode($json, true)));
		if (! $this->InitDb($json))
			throw new Exception(
					'Error when init mySql JSON-CRUD table db for:' .
							 CrudDriver::INITALIZATION_ERROR_CODE);
		if (! $db = $this->getConnection())
			throw new Exception('Connection error', 
					CrudDriver::INITALIZATION_ERROR_CODE);
			/*
		 * leggi tutto in base alle condizioni
		 * conta le condizioni soddisfatte dai vari nodi
		 * leggi gli id che soddisfano tutte le condizioni
		 * invia i risultati come array json
		 */
		$schema = $this->jsonToPathSchema($json);
		unset($schema['id']);
		$result = $db->query($this->getReadListQuery($json));
		$counts = array();
		// Sfoglio i risultati
		while ($row = $result->fetch_array()) {
			if (! isset($counts[$row['id']]))
				$counts[$row['id']] = 1;
			else
				$counts[$row['id']] ++;
		}
		$result->close();
		// Cerco i risultati esatti
		$results = array();
		foreach ($counts as $id => $count) {
			if ($count >= count($schema)) {
				/* Creazione json solo id */
				$q = array(
					'domain' => $this->getDomain($json),
					'type' => $this->getType($json),
					'content' => array()
				);
				$q['content']['id'] = $id;
				$loaded = $this->readFromId(json_encode($q));
				$loadedArr = json_decode($loaded, true);
				$results[] = $loadedArr['content'];
			}
		}
		$return = array(
			'domain' => $this->getDomain($json),
			'type' => $this->getType($json),
			'collection' => $results
		);
		log_write(
				self::LOG_NAME . 'Readed ' . count($results) .
						 ' element/s from query');
		return json_encode($return);
	}

	private function getId ($json)
	{
		$decoded = json_decode($json, true);
		if (isset($decoded['content']['id']))
			return $decoded['content']['id'];
		else
			return null;
	}

	private function getDomain ($json)
	{
		$decoded = json_decode($json, true);
		if (isset($decoded['domain']))
			return $decoded['domain'];
		else
			return null;
	}

	private function getType ($json)
	{
		$decoded = json_decode($json, true);
		if (isset($decoded['type']))
			return $decoded['type'];
		else
			return null;
	}

	private function isGenericClass ($json)
	{
		$decoded = json_decode($json, true);
		return count($decoded['content']) == 0;
	}

	private function initDb ($json)
	{
		if ($db = $this->getConnection()) {
			if (! $db->query($this->getTableCreationQuery($json)))
				return false;
			else
				return true;
		}
		return false;
	}

	private function jsonToPathSchema ($json)
	{
		$json = json_decode($json, true);
		$path = '';
		$schema = array();
		return $this->recContentJsonToPathSchema($json['content'], $path, 
				$schema);
	}

	private function recContentJsonToPathSchema ($parsed_content, &$path, 
			&$schema)
	{
		foreach ($parsed_content as $key => $value) {
			if (strcmp($path, '') == 0)
				$tmpPath = $key;
			else
				$tmpPath = $path . "." . $key;
			if (is_array($value) && $parsed_content != NULL)
				$this->recContentJsonToPathSchema($value, $tmpPath, $schema);
			else {
				$schema[$tmpPath] = $value;
			}
		}
		return $schema;
	}

	private function getTableName ($json)
	{
		$jsn = json_decode($json, true);
		return str_replace(".", "_", $jsn['domain']) . "_" . $jsn['type'];
	}

	private function resultSetToJson ($json, $rs)
	{
		$j = json_decode($json, true);
		$mainData = array();
		$mainData['domain'] = $j['domain'];
		$mainData['type'] = $j['type'];
		// $mainData['content']['id']=$j['content']['id'];
		$count = 0;
		$v = null;
		$result = array();
		while ($resArr = $rs->fetch_array()) {
			$result[$count]['path'] = $resArr['path'];
			$result[$count]['value'] = $resArr['value'];
			// $result [$count] ['version'] = $resArr ['version'];
			$result[$count]['id'] = $resArr['id'];
			$count ++;
		}
		$rs->close();
		$mainData['content'] = $this->getContentByResultSet($result);
		$ret = json_encode($mainData, JSON_PRETTY_PRINT);
		return $ret;
	}

	private function getContentByResultSet ($result)
	{
		$content = array();
		foreach ($result as $res) {
			$temp = &$content;
			$exploded = explode(".", $res['path']);
			foreach ($exploded as $k) {
				$temp = &$temp[$k];
			}
			$temp = $res['value'];
			unset($temp);
			$content['id'] = $res['id'];
			// $content ['version'] = $res ['version'];
		}
		return $content;
	}
	/*
	 * ----------------------------------------
	 * QUERIES GENERATORS
	 * ---------------------------------------
	 *
	 */
	/* Delete query generator */
	private function getDeleteQuery ($json)
	{
		$id = $this->getId($json);
		$retQ = $this->getBaseQuery($json, self::DELETE_QPT);
		$retQ = str_replace('<id>', $id, $retQ);
		return $retQ;
	}
	/* ReadFromId query generator */
	private function getReadIdQuery ($json)
	{
		$id = $this->getId($json);
		$retQ = $this->getBaseQuery($json, self::READ_QPT);
		$retQ = str_replace('<id>', $id, $retQ);
		return $retQ;
	}
	/* ReadFromQuery, query generator */
	private function getReadListQuery ($json)
	{
		$kv = $this->jsonToPathSchema($json);
		$tmpQ = $this->getBaseQuery($json, self::READ_CLAUSE_OPEN_QPT);
		if ($this->isGenericClass($json)) {
			$tmpQ = $tmpQ . " or 1=1";
		} else {
			foreach ($kv as $k => $v) {
				if ($k != null && ! strcmp($k, 'id') == 0) {
					$clause = self::READ_CLAUSE_QPT;
					$clause = str_replace('<pathname>', "'" . $k . "'", $clause);
					$clause = str_replace('<value>', "'" . $v . "'", $clause);
					$tmpQ = $tmpQ . $clause;
				}
			}
		}
		$tmpQ = $tmpQ . ";";
		return $tmpQ;
	}
	/* Create query generator */
	private function getCreationInsertQueries ($json)
	{
		$queryes = array();
		$kv = $this->jsonToPathSchema($json);
		foreach ($kv as $k => $v) {
			if ($k != null && ! strcmp($k, 'id') == 0) {
				$tmpQ = $this->getBaseQuery($json, self::INSERT_QPT);
				$tmpQ = str_replace('<nid>', '\'' . uniqid('') . '\'', $tmpQ);
				$tmpQ = str_replace('<id>', '@new_id', $tmpQ);
				$tmpQ = str_replace('<pathname>', "'" . $k . "'", $tmpQ);
				$tmpQ = str_replace('<value>', "'" . $v . "'", $tmpQ);
				$queryes[] = $tmpQ;
			}
		}
		return $queryes;
	}
	/* Table creation query generator */
	private function getTableCreationQuery ($json)
	{
		return $this->getBaseQuery($json, self::CREATE_TABLE_QPT);
	}
	/* Base query generator */
	private function getBaseQuery ($json, $query)
	{
		$query = str_replace('<dbname>', $this->dbname, $query);
		$query = str_replace('<prefix>', $this->prefix, $query);
		$query = str_replace('<table_name>', $this->getTableName($json), $query);
		return $query;
	}

	private $connection;

	private $url, $username, $password, $dbname, $prefix;
	
	/*
	 * ---------------------
	 * COSTANTI
	 * --------------------
	 */
	const LOG_NAME = '[CRUD mySql Driver] ';

	const INFO = 'mySql CRUD-JSON driver v.1b, for Graphene 0.1b';
	
	const CREATE_TABLE_QPT = 'CREATE TABLE IF NOT EXISTS `<dbname>`.`<prefix>_<table_name>` (`node_id` VARCHAR(30) NOT NULL ,`id` VARCHAR(30) NOT NULL ,`node_path` VARCHAR(256) NOT NULL ,`node_value` TEXT NULL DEFAULT NULL,PRIMARY KEY (`node_id`));';
	// Create queries
	const INSERT_QPT = "INSERT INTO `<dbname>`.`<prefix>_<table_name>` (`node_id`,`id`, `node_path`, `node_value`) VALUES (<nid>,<id>, <pathname>, <value>);";
	// update queries
	const DELETE_QPT = "DELETE FROM `<dbname>`.`<prefix>_<table_name>` WHERE `id`='<id>';";
	// read queries
	const READ_QPT = "SELECT id as id ,node_path as path ,node_value as value FROM `<dbname>`.`<prefix>_<table_name>` where id='<id>';";

	const READ_CLAUSE_OPEN_QPT = "SELECT id FROM `<dbname>`.`<prefix>_<table_name>` where 1=2 ";

	const READ_CLAUSE_QPT = " or (node_path=<pathname> and BINARY(node_value)=<value>) ";
}