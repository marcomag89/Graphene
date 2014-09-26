<?php
namespace Graphene\controllers;

class UrlProcessor{
	public function __construct( $pattern){
		$this->pattern=$pattern;
		$this->matchedPars=array();
	}
	public function matches($url){
		return 
				$this->checkEmpty($url)
			||	$this->checkUrl($url);
	}
	/*Empty or monopar matcher*/
	private function checkEmpty( $url){
		if(strcmp($url,'')==0 && ($this->pattern==null || strcmp($this->pattern,'')==0))return true;
		
		if(!str_contains($this->pattern, '/') && str_starts_with($this->pattern, ':')){
			$parName = substr($this->pattern, 1);
			$this->matchedPars[$parName]=$url;
			return true;
		}else{
			return false;
		}
	}
	
	/*Url matcher*/
	private function checkUrl($url){
		$rExpl = explode('/', $url);
		$qExpl = explode('/', $this->pattern);
		if ($qExpl[0] == null || str_starts_with($qExpl[0], ':'))return false;
		$havingScores = 0;
		$minScores = count($qExpl);
		$j = 0;
		$i = 0;
		$pars = array();
		for ($i = 0; $i < count($qExpl); $i ++) {
			$qElem = $qExpl[$i];
			/* Gestione Parametro */
			if (str_starts_with($qElem, ':')) {
				$parName = substr($qElem, 1);
				$parValue = '';
				if (isset($qExpl[$i + 1])) {
					$outOn = $qExpl[$i + 1];
					if (str_starts_with($outOn, ':'))
						$outOn = '/next/';
				} else
					$outOn = '/end/';
				if ($outOn == '/next/')
					$parValue = $rExpl[$j ++];
				else {
					while (isset($rExpl[$j]) && $outOn != $rExpl[$j]) {
						if ($parValue != '')
							$parValue = $parValue . '/' . $rExpl[$j];
						else
							$parValue = $rExpl[$j];
						$j ++;
					}
				}
				$havingScores ++;
				$pars[$parName] = $parValue;
			} else {
				/* Gestione Keyword */
				if (isset($rExpl[$j]) && strcasecmp($rExpl[$j], $qExpl[$i]) == 0) {
					$havingScores ++;
					$j ++;
				}
			}
		}
		if ($havingScores >= $minScores) {
			$this->matchedPars=$pars;
			return true;
		} else
			return false;
	}
	public function getPars(){
		return $this->matchedPars;
	}
	private $matchedPars;
	private $pattern;
}