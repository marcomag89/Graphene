<?php
namespace ACL\db;
use Graphene\controllers\exceptions\GraphException;
use Graphene\db\CrudDriver;
use \Exception;
use Graphene\Graphene;
use \mysqli;

class AclDb implements CrudDriver
{

	public function __construct ($dbConfig)
	{
		$this->url = $dbConfig->host;
		$this->dbname = $dbConfig->dbName;
		$this->username = $dbConfig->username;
		$this->prefix = $dbConfig->prefix;
		$this->password = $dbConfig->password;
	}

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

	public function create ($json)
	{
		if (! ($db = $this->getConnection()))
			throw new Exception('connection error');
		$arr = json_decode($json, true);
		$gid = uniqid();
		$qrs = $this->getSqlPermissions($gid, $arr);
		/* db al lavoro */
		try {
			$db->autocommit(false);
			$db->begin_transaction();
			$qr = 'INSERT INTO ' . $this->getTableNameGroup($arr['domain']) .
					 ' SET  id="' . $gid . '", name="' . $arr['content']['name'] .
					 '", version=' . $arr['content']['version'];
			$db->query($qr);
			foreach ($qrs as $q) {
				//echo $q . "\n";
				$db->query($q);
			}
			$db->commit();
			$db->autocommit(true);
		} catch (Exception $e) {
			$db->rollback();
			throw new GraphException('Error while creating group check if group already exists: ' .$e->getMessage(),400,400);
		}
		$arr[''];
	}

	public function read ($json,$query=null)
	{}

	public function update ($json)
	{}

	public function delete ($json)
	{}

	private function getSqlPermissions ($uniqId, $arr)
	{
		$queries = array();
		$queries[] = 'DELETE FROM ' . $this->getTableNameAction($arr['domain']) .
				 ' WHERE id="' . $uniqId . '"';
		$actions = array();
		if (isset($arr['content']['actions']))
			$actions = $arr['content']['actions'];
		foreach ($actions as $act) {
			$queries[] = 'INSERT INTO ' .
					 $this->getTableNameAction($arr['domain']) .
					 ' SET id_group="' . $uniqId . '", action="' . $act . '"';
		}
		return $queries;
	}

	private function getTableNameGroup ($ModelDomain)
	{
		return $this->prefix . '_' . str_replace('.', '_', $ModelDomain) . '_model';
	}

	private function getTableNameAction ($ModelDomain)
	{
		return $this->prefix . '_' . str_replace('.', '_', $ModelDomain) . '_' .
				 self::TBL_ACTION_X_GROUP;
	}

	public function getSettings ()
	{}

	public function getInfos ()
	{}

	const LOG_NAME = '[ACL group storage driver] ';

	const TBL_GROUP = 'Group_model';

	const TBL_ACTION_X_GROUP = 'x_action';

	private $url, $username, $password, $dbname, $prefix;

	private $connection;
}