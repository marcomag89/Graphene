<?php
namespace Graphene\db\drivers;
use Graphene\db\CrudDriver;
use \mysqli;
use \Exception;
use Graphene\controllers\exceptions\GraphException;
use Graphene\controllers\exceptions\ExceptionCodes;
use Graphene\models\Bean;
use \PDO;
use \PDOStatement;
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
	public function getConnection (){
		if ($this->connection == null) {
			try{
				$this->connection = new PDO('mysql:host='.$this->url.';dbname='.$this->dbname, $this->username, $this->password);
				log_write(self::LOG_NAME . 'mySql connection success');
				return $this->connection;
			}catch (Exception $e){
				log_write(self::LOG_NAME . 'mySql connection fails: '.$e->getMessage());
				$this->connection = null;
				throw new GraphException('Error on mysql connection: '.$e->getMessage(), ExceptionCodes::DRIVER_CONNECTION, 500);
			}
		} else return $this->connection;
		
	}

	/* TAG Create */
	public function create ($json){
		$this->init($json);
		$decoded=json_decode($json,true);
		$cols=$this->columnsByStruct($decoded['content']);
		$colNames='';
		$colValues='';
		foreach($cols as $name=>$value){
			$colNames=$colNames.'`'.$name.'`,';
			if($value===false)$colValues = $colValues.'\'0\',';
			else if($value===true)$colValues = $colValues.'\'1\',';
			else $colValues=$colValues.'\''.$value.'\',';
		}
		$colNames=	substr($colNames, 0,-1);
		$colValues=	substr($colValues, 0,-1);
		$q=self::INSERT_PTT;
		$q=str_replace('<fields>',$colNames, $q);
		$q=str_replace('<values>',$colValues, $q);
		$q=str_replace('<dbname>',$this->dbname, $q);
		$q=str_replace('<tableName>',$this->prefix.'_'.str_replace('.','_',$decoded['domain']).'_bean', $q);
		//Query
		$res=$this->connection->query($q);
		$err=$this->connection->errorInfo();
		if(strcasecmp($err[0], '00000')!=0)	throw new GraphException('mySql CREATE exception '.$err[2], ExceptionCodes::DRIVER_CREATE, 500);
		if($res instanceof PDOStatement)$res->fetchAll();
		$tmpJ=$decoded;
		unset($tmpJ['content']);
		$tmpJ['content']['id']=$decoded['content']['id'];
		return $this->read(json_encode($tmpJ));

		//print_r($cols);
	}

	/* TAG Read */
	public function read ($json){
		$this->init($json);
		$decoded=json_decode($json,true);
		$q=self::SELECT_PTT;
		$cols=$this->columnsByStruct($decoded['content']);
		$cond='\'1\'=\'1\'';
		foreach($cols as $name=>$value){$cond=$cond.' AND `'.$name.'`=\''.$value.'\'';}
		$q=str_replace('<dbname>',$this->dbname, $q);
		$q=str_replace('<tableName>',$this->prefix.'_'.str_replace('.','_',$decoded['domain']).'_bean', $q);
		$q=str_replace('<cond>',$cond, $q);
		$return=array();
		//Exec query
		$res=$this->connection->query($q);
		$err=$this->connection->errorInfo();
		if(strcasecmp($err[0], '00000')!=0)	throw new GraphException('mySql driver READ exception '.$err[2], ExceptionCodes::DRIVER_READ, 500);		
		if($res instanceof PDOStatement){
			$results=array();
			$i=0;
			while ($row = $res->fetch(PDO::FETCH_ASSOC)) {
				foreach($row as $rk=>$rv){$results[$i][$rk]=$rv;}
				$i++;
			}
			foreach($results as $res){
				$return[]=$this->colsToJsonArr($res);
			}
		}
		$retJson=$decoded;
		unset($retJson['content']);
		$retJson['collection']=$return;
		return json_encode($retJson,JSON_PRETTY_PRINT);
	}

	/* TAG Update */
	public function update ($json)	{
		$this->init($json);
		$decoded=json_decode($json,true);
		$cols=$this->columnsByStruct($decoded['content']);
		$q=self::UPDATE_PTT;
		$kv=' ';
		foreach($cols as $label=>$value){
			if(!strcasecmp($label, 'id')==0){
				$kv=$kv.'`'.$label.'`=\''.$value.'\',';
			}
		}
		$kv=substr($kv, 0,-1);
		$q=str_replace('<dbname>',$this->dbname, $q);
		$q=str_replace('<tableName>',$this->prefix.'_'.str_replace('.','_',$decoded['domain']).'_bean', $q);
		$q=str_replace('<kv>',$kv, $q);
		$q=str_replace('<id>',$cols['id'],$q);
		$res=$this->connection->query($q);
		$err=$this->connection->errorInfo();
		if(strcasecmp($err[0], '00000')!=0)	throw new GraphException('mySql driver UPDATE exception '.$err[2], ExceptionCodes::DRIVER_UPDATE, 500);
		if($res instanceof PDOStatement)$res->fetchAll();
		$tmpJ=$decoded;
		unset($tmpJ['content']);
		$tmpJ['content']['id']=$decoded['content']['id'];
		return $this->read(json_encode($tmpJ));
	}

	/* TAG Delete */
	public function delete ($json){
		$this->init($json);
		$decoded=json_decode($json,true);
		$q=self::DELETE_PTT;
		$q=str_replace('<dbname>',$this->dbname, $q);
		$q=str_replace('<tableName>',$this->prefix.'_'.str_replace('.','_',$decoded['domain']).'_bean', $q);
		$q=str_replace('<id>',$decoded['content']['id'], $q);
		$res=$this->connection->query($q);
		$res=$this->connection->query($q);
		$err=$this->connection->errorInfo();
		if(strcasecmp($err[0], '00000')!=0)	throw new GraphException('mySql driver DELETE exception '.$err[2], ExceptionCodes::DRIVER_UPDATE, 500);	
		if($res instanceof PDOStatement)$res->fetchAll();
		return true;
	}
	
	private function init($json){
		$this->getConnection();
		$decoded=json_decode($json,true);
		$tableName=$this->prefix.'_'.str_replace('.','_',$decoded['domain']).'_bean';
		$exists=$this->connection->query('SELECT 1 FROM `'.$this->dbname.'`.`'.$tableName.'` LIMIT 1;');
		if($exists instanceof PDOStatement)$exists->fetchAll();
		else $this->createTable($tableName, $decoded);
	}
	private function createTable($tableName,$decodedJson){
		$struct=$decodedJson['struct'];
		$creationQ=self::TBL_CREATION_PTT;
		$flatArray=$this->columnsByStruct($struct);
		$cols=$this->convertTypes($flatArray);
		$colsStr='';		
		foreach($cols as $col=>$type){
			$colsStr=$colsStr.'`'.$col.'` '.$type.',';
		}
		$creationQ=str_replace('<dbname>', $this->dbname, $creationQ);
		$creationQ=str_replace('<tableName>', $tableName, $creationQ);
			
		$creationQ=str_replace('<fields>', $colsStr, $creationQ);
		$creationQ=str_replace('<uniqueIndexes>', $this->getUniques($flatArray), $creationQ);
		$res=$this->connection->query($creationQ);
		$err=$this->connection->errorInfo();
		if(strcasecmp($err[0], '00000')!=0)	throw new GraphException('mySql driver TABLE CREATION error: '.$err[2].'________QUERY_:____'.$creationQ, ExceptionCodes::DRIVER_UPDATE, 500);
		if($res instanceof PDOStatement)$res->fetchAll();
	}
	private function columnsByStruct ($parsed_struct, &$path='', &$schema=null){
		if($schema==null) $schema=array();
		foreach ($parsed_struct as $key => $value) {
			if (strcmp($path, '') == 0) $tmpPath = $key;
			else $tmpPath = $path . '_' . $key;
			if (is_array($value) && $parsed_struct != NULL)
				$this->columnsByStruct($value, $tmpPath, $schema);
			else {$schema[$tmpPath] = $value;}
		}
		return $schema;
	}
	private function colsToJsonArr($row){
		$res=array();
		foreach ($row as $k=>$v){
			$expl=explode('_', $k);
			$tRes=&$res;
			if(count($expl)>1){
				//goto leaf
				foreach($expl as $e){
					if(!isset($tRes[$e]))$tRes[$e]=array();
					$tRes=&$tRes[$e];
				}
				//Popolate leaf
				$tRes=$v;
			}else{
				$tRes[$k]=$v;
			}
		}
		return $res;
	}
	private function convertTypes($flat){
		$ret=array();
		foreach($flat as $flatk=>$flatv){
			$ret[$flatk]=$this->convertType($flatv);
		}
		return $ret;
	}
	private function getUniques($cols){
		$colstr=' ';
		foreach($cols as $col=>$type){
			if(in_array(substr(Bean::UNIQUE, strlen(Bean::CHECK_SEP)), explode(Bean::CHECK_SEP, $type)))
				$colstr=$colstr.', UNIQUE INDEX `'.$col.'_UNIQUE` (`'.$col.'` ASC)';
		}
		//$colstr=substr($colstr, 0,-1);
		return $colstr;
	}
	private function convertType($type){
		$texpl =explode(Bean::CHECK_SEP, $type);
		unset($texpl[0]);
		array_values($texpl); 
		$ret='';
		foreach($texpl as $t){
			$test =Bean::CHECK_SEP.explode(Bean::CHECK_PAR, $t)[0];
			if (preg_match('/'.Bean::CHECK_PAR.'/', $type))$test .= Bean::CHECK_PAR;
			switch ($test) {
				/* Type checkers */
				case Bean::BOOLEAN		: {$ret=' INT(1)'; break;}
				case Bean::DECIMAL		: {$ret=' DOUBLE'; break;}
				case Bean::INTEGER		: {$ret=' INT(11)'; break;}
				case Bean::STRING		: {$ret=' VARCHAR(45)'; break;}
				case Bean::UID			: {$ret=' VARCHAR(32)'; break;}
				case Bean::DATE			: {$ret=' DATE'; break;}
				case Bean::DATETIME 	: {$ret=' DATETIME'; break;}
				case Bean::MATCH 		: {$ret=' VARCHAR(45)'; break;}
				case Bean::ENUM			: {
					$elems=explode(',',explode(Bean::CHECK_PAR, $t)[1]);
					$tp=' ENUM(';
					foreach($elems as $elem){
						$tp=$tp.'\''.$elem.'\',';
					}
					$tp=substr($tp, 0,-1);
					$tp=$tp.')';
					$ret=$tp;
					break;
				}
				/* Content checkers */
				case Bean::MAX_LEN		: {$ret=' VARCHAR('.explode(Bean::CHECK_PAR, $t)[1].')'; break;}
				case Bean::MIN_LEN		: {$ret=' VARCHAR('.(45+explode(Bean::CHECK_PAR, $t)[1]).')'; break;}
				case Bean::NOT_NULL     : {$ret=$ret.' NOT NULL'; break;}
				default 				: {}
			}
		}
		if(strcmp($ret,'')==0)$ret=' VARCHAR(200)';
		return $ret;
	}
	public function getSettings (){}
	public function getInfos (){return self::INFO;}
	private $connection;
	private $url, $username, $password, $dbname, $prefix;

	/*
	 * ---------------------
	 * COSTANTI
	 * --------------------
	 */
	const TBL_CREATION_PTT ='CREATE TABLE IF NOT EXISTS `<dbname>`.`<tableName>`( <fields> PRIMARY KEY(`id`) <uniqueIndexes> ) ENGINE = InnoDB DEFAULT CHARACTER SET = utf8;';
	const DELETE_PTT='DELETE FROM `<dbname>`.`<tableName>` WHERE `id`=\'<id>\';';
	const SELECT_PTT='SELECT * FROM `<dbname>`.`<tableName>` WHERE <cond>';
	const UPDATE_PTT='UPDATE `<dbname>`.`<tableName>` SET <kv>  WHERE `id`=\'<id>\'';
	const UNIQUE_PTT='UNIQUE INDEX `<field>_UNIQUE (`<field>`)';
	const LOG_NAME = '[CRUD mySql Driver v2] ';
	
	const INSERT_PTT='INSERT INTO `<dbname>`.`<tableName>` (<fields>) VALUES (<values>);';
	const INFO = 'mySql CRUD-JSON driver v.1b, for Graphene 0.1b';
}