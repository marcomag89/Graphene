<?php
namespace grSystem;

use Graphene\models\Bean;
use Graphene\controllers\bean\BeanController;

class BenchmarkBean extends Bean{
	public function __construct($maxn=20,$maxt=100){
		$this->maxFieldText=$maxt;
		$this->maxNodes=$maxn;
		parent::__construct();
	}
	public function getStructs(){
		if(self::$GEN!=null)return self::$GEN;
		$ret=array();
		for($i=0;$i<$this->maxNodes;$i++){
			$field=$this->generateString($this->validFieldChars, 3,10);
			$ret[$field]=Bean::STRING_VALUE.Bean::NOT_EMPTY;
			$this->content[$field]=$this->generateString($this->validTextChars, 3,$this->maxFieldText);
		}
		self::$GEN=array(BeanController::LAZY_STRUCT=>$ret);
		return self::$GEN;
	}
	private function generateString($alphabet,$minChars,$maxChars=null){
		if($maxChars==null)$maxChars=$minChars;
		$nChars=rand($minChars,$maxChars);
		$str='';
		for($i=0;$i<$maxChars;$i++){
			$str=$str.$alphabet[rand(0,strlen($alphabet)-1)];
		}
		return $str;
	}
	private $validTextChars = "abcdefghijklmnopqrstuxyvwzABCDEFGHIJKLMNOPQRSTU XYVWZ#*+-%!!\$%&/()=?=^\"";
	private $validFieldChars = "abcdefghijklmnopqrstuxyvwzABCDEFGHIJKLMNOPQRSTUXYVWZ";
	
	private static $GEN=null;
	private $maxNodes;
	private $maxFieldText;
}