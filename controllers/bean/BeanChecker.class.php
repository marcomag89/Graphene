<?php
namespace Graphene\controllers\bean;
use Graphene\models\Bean;

class BeanChecker
{

	public function __construct ()
	{
		$this->tests = array();
		$this->testIdx = - 1;
	}
	/*
	 -------------------------------
	 Fnzioni di controllo Generali  
	 -------------------------------
 	*/
	public function checkValidStruct($struct,&$node=null){
		if($node==null)$node=$struct;
		foreach($node as $nk=>$nv){
			if(!$this->isValidLabel($nk)){
				return false;
			}
			else if(is_array($nv) && !$this->checkValidStruct($node,$nv)) return false;
		}
		return true;
	}
	/**
	 * CONTROLLO CONTENUTO
	 * Controlla se il contenuto del bean e' conforme alla sua struttura.
	 * E' possibile ignorare o modificare il controllo su alcuni valori
	 * estendendo il metodo 'check($label,$value)'
	 */
	public function checkContent (Bean $bean,$struct)
	{
		return 	$this->checkExceededValues($bean,$bean->getContent(),$struct) && 
				$this->checkStructValues($bean,$bean->getContent(),$struct);
	}
	
	/**
	 * CONTROLLO VALORI SUPERFLUI
	 * Controlla se i valori del bean sono contemplati nella struttura e se sono
	 * validi
	 */
	private function checkExceededValues (Bean $bean, $content = null, $struct){
	
		foreach ($content as $ck => $cv) {
			if (is_array($cv) && !$this->checkExceededValues($bean, $cv, $struct[$ck])) return false;
			else if ($struct == null) return false;
			else if (!isset($struct[$ck])){ 
				$this->addError($ck, '(undefinied)', '(undefinied)', 'Undefinied field \''.$ck.'\' into a '.$bean->getName().' struct'); 
				return false;
			}
		}
		return true;
	}
	
	/**
	 * CONTROLLO VALORI DELLA STRUTTURA
	 * Controlla se i valori del bean previsti dalla struttura come not null o
	 * come not empty
	 * sono effettivamente inseriti
	 */
	private function checkStructValues ($bean, $content = null, $struct){
		
		if ($content == null)$content = $bean->getContent();
	
		foreach ($struct as $sk => $sv) {
			if (! isset($content[$sk])) $content[$sk] = null;
			if (is_array($sv)) {
				if ((isset($sv[Bean::NODE]) && !$this->isValidNode($content[$sk], $sv[Bean::NODE], $sk)) ||
						! $this->checkStructValues($bean, $content[$sk], $sv)) {
							return false;
						}
			} else
				if (strcmp($sk, Bean::NODE) != 0 && ! $this->isValidValue(
						$content[$sk], $sv, $sk)) {
							return false;
						}
		}
		return true;
	}
	
	/*
	 -------------------------------
	 Fnzioni di controllo Atomiche  
	 -------------------------------
	*/	
	public function isValidLabel($label){
		if(strcmp($label,Bean::NODE)==0)return true;
		if(!preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*/',$label) || str_contains($label, '_')){
			return false;
		}
		else 
			return true;
	}
	// Check value type
	public function isValidValue ($val, $type, $label = 'nd'){
		
		if (! is_string($type)) {
			$this->addError($label, $label . '-definition', Bean::STRING_VALUE, 'invalid field definition');
			return false;
		}
		$expl = explode('-', $type);
		unset($expl[0]);
		$errs = 0;
		foreach ($expl as $check) {
			$check = '-' . $check;
			$chResult = $this->check($check, $val);
			if ($chResult == false) {
				$errs ++;
				$this->addError($label, $check, $type, 
						'Field \'' . $label . '\':\'' . $val . '\' must be: ' .
								 $check);
			} else 
				if (strcasecmp($chResult, 'und') == 0) {
					$errs ++;
					$this->addError($label, $check, $type, 
							'invalid type: ' . $check . ', for label: ' . $label);
				}
		}
		$res = $errs == 0;
		return $res;
	}

	public function isValidNode ($node, $type, $label = 'nd'){
		/*Error handling*/
		if (! is_array($node) && $node != null) {$this->addError($label, $label . '-is-array', 'is not aray');return false;}
		if ($type == null) {return true;}
		if (! is_string($type)) {$this->addError($label, $label . '-definition', Bean::NODE, 'invalid node definition');return false;}
		
		$expl = explode('-', $type);
		unset($expl[0]);
		$errs = 0;
		foreach ($expl as $check) {
			$check = '-' . $check;
			$chResult=$this->check($check, $node);
			if (!$chResult) {
				$errs ++;
				$this->addError($label, $check, $type, 'Node \'' . $label . '\':\'' . json_encode($node) . '\' must be: ' . $check);
			} else 
				if (strcasecmp($chResult, 'und') == 0) {
					$errs ++;
					$this->addError($label, $check, $type, 'invalid type: ' . $check . ', for node: ' . $label);
				}
		}
		$res = $errs == 0;
		return $res;
	}

	/**
	 * Main check function
	 */
	private function check ($type, $val)
	{
		$test = explode(':', $type)[0];
		if (preg_match('/:/', $type))
			$test .= ':';
		
		if (! is_array($val)) {
			switch ($test) {
				/* Type checkers */
				case Bean::ALPHANUMERIC_VALUE : return $this->checkAlphanumeric($val, $type);
				case Bean::BOOLEAN_VALUE      : return $this->checkBoolean($val, $type);
				case Bean::DOUBLE_VALUE       : return $this->checkDouble($val, $type);
				case Bean::ENUM_VALUE         : return $this->checkEnum($val, $type);
				case Bean::FLOAT_VALUE        : return $this->checkFloat($val, $type);
				case Bean::INTEGER_VALUE      : return $this->checkInteger($val, $type);
				case Bean::LONG_VALUE         : return $this->checkLong($val, $type);
				case Bean::STRING_VALUE       : return $this->checkString($val, $type);
				case Bean::UID_VALUE          : return $this->checkUid($val, $type);
				case Bean::DATE_VALUE         : return $this->checkDate($val,$type);
				/* Content checkers */
				case Bean::NOT_EMPTY          : return $this->checkNotEmpty($val, $type);
				case Bean::NOT_NULL           : return $this->checkNotNull($val, $type);
				case Bean::MIN_LENGHT         : return $this->checkMinLenght($val, $type);
				case Bean::MAX_LENGHT         : return $this->checkMaxLenght($val, $type);
				default                       : return 'und';
			}
		} else {
			switch ($test) {
				/* Array checkers */
				case Bean::NUMERIC_KEYS       : return $this->checkNodeNumericKeys($val, $type);
				case Bean::NODE               : return $this->checkNode($val, $type);
				case Bean::STRING_KEYS        : return $this->checkNodeStringKeys($val, $type);
				case Bean::ENUM_KEYS          : return $this->checkNodeEnumKeys($val, $type);
				case Bean::MIN_ELEMS          : return $this->checkNodeMinElems($val, $type);
				case Bean::MAX_ELEMS          : return $this->checkNodeMaxElems($val, $type);
				case Bean::NOT_EMPTY          : return $this->checkNodeNotEmpty($val, $type);
				case Bean::NOT_NULL           : return $this->checkNodeNotNull($val, $type);
				default                       : return 'und';
			}
		}
	}
	
	/*
	 * ----------------------
	 * CHECKS FUNCTIONS
	 * ----------------------
	 */
	private function checkDate($val,$type){
		if($val==null || strcmp($val,'')==0)return true;
		else{
			sscanf($val,"%d-%d-%d",$y,$m,$d);
		 	return checkdate($m,$d,$y);
		}
	}
	private function checkInteger ($val, $type)
	{
		try {
			$v = intval($val);
			$res = is_int($v) || $v == null;
		} catch (Exception $e) {
			$res = $v == null;
		}
		return $res;
	}

	private function checkString ($val, $type)
	{
		$v = $val;
		$res = is_string($v) || $v == null;
		return $res;
	}

	private function checkBoolean ($val, $type)
	{
		$v = $val != 0;
		$res = is_bool($v) || $v == null;
		return $res;
	}

	private function checkFloat ($val, $type)
	{
		try {
			$v = floatval($val);
			$res = is_float($v) || $v == null;
		} catch (Exception $e) {
			$res = $val == null;
		}
		return $res;
	}

	private function checkDouble ($val, $type)
	{
		try {
			$v = floatval($val);
			$res = is_double($v) || $v == null;
		} catch (Exception $e) {
			$res = $val == null;
		}
		return $res;
	}

	private function checkLong ($val, $type)
	{
		try {
			$res = (is_numeric($val) && is_long(intval($val))) || $val == null;
		} catch (Exception $e) {
			$res = $val == null;
		}
		return $res;
	}

	private function checkAlphanumeric ($val, $type)
	{
		try {
			preg_match('/[A-Za-z0-9]*/', $val, $matches);
			$res = $val == null ||
					 (strcmp($matches[0], $val) == 0 && is_string($val));
		} catch (Exception $e) {
			
			$res = $val == null;
		}
		return $res;
	}

	private function checkUid ($val, $type)
	{
		$res = true;
		return $res;
	}

	private function checkEnum ($val, $type)
	{
		if ($val == null)
			return true;
		$res = false;
		$args = explode(',', explode(':', $type)[1]);
		foreach ($args as $arg) {
			if (strcmp($arg, $val) == 0)
				$res = true;
		}
		return $res;
	}

	private function checkNotNull ($val, $type)
	{
		$res = $val != null;
		return $res;
	}

	private function checkNotEmpty ($val, $type)
	{	
		if($val===null)return true;
		else return (strlen($val.'')!=0);
	}

	private function checkMinLenght ($val, $type)
	{
		$arg = explode(':', $type)[1];
		$res = $val == null || strlen($val . '') >= $arg;
		return $res;
	}

	private function checkMaxLenght ($val, $type)
	{
		$arg = explode(':', $type)[1];
		$res = $val == null || strlen($val . '') <= $arg;
		return $res;
	}
	/* Node tests */
	private function checkNodeNumericKeys ($node, $ype)
	{
		if ($node == null)
			return true;
		$res = true;
		foreach ($node as $nk => $nv) {
			if (! is_int($nk))
				$res = false;
		}
		return $res;
	}

	private function checkNodeStringKeys ($node, $type)
	{
		if ($node == null)
			return true;
		$res = true;
		foreach ($node as $nk => $nv) {
			if (! is_string($nk))
				$res = false;
		}
		return $res;
	}

	private function checkNodeMinElems ($node, $type)
	{
		if ($node == null)
			return true;
		$arg = explode(':', $type)[1];
		$res = count($node) >= $arg;
		return $res;
	}

	private function checkNodeMaxElems ($node, $type)
	{
		if ($node == null)
			return true;
		$arg = explode(':', $type)[1];
		$res = count($node) <= $arg;
		return $res;
	}

	private function checkNodeNotEmpty ($node, $type)
	{
		if ($node == null)
			return true;
		$res = count($node) > 0;
		return $res;
	}

	private function checkNodeEnumKeys ($node, $type)
	{
		if ($node == null)
			return true;
		$args = explode(',', explode(':', $type)[1]);
		$res = false;
		foreach ($args as $arg)
			foreach ($node as $nk => $nv)
				if (strcmp($arg, $nk) == 0)
					$res = true;
		return $res;
	}

	private function checkNodeNotNull ($node, $type)
	{
		$res = $node != null;
		return $res;
	}

	private function addError ($field, $type, $tGlobal, $message)
	{
		$errArr = array(
			'mismatchType' => $type,
			'globalType' => $tGlobal,
			'message' => $message
		);
		$this->tests[$this->testIdx][$field][$type] = $errArr;
	}

	public function getLastTestErrors (){
		return $this->tests[$this->testIdx];
	}

	public function newTest (){
		$this->testIdx ++;
		$this->tests[$this->testIdx] = array();
	}

	private $tests;
	private $testIdx;
}

?>