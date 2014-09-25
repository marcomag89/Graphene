<?php
use Graphene\controllers\Action;
use grSystem\BenchmarkBean;
use grSystem\FooBarBean;

class Benchmark extends Action{
	public function run(){
	//	$bb=new BenchmarkBean();
	//	$created=$bb->create();
	//	$this->sendBean($created);
		$fb=new FooBarBean();
		$fb->setFoo_barOne('Ciaooo');
		echo $fb->getFoo_barOne();
		$this->sendBean($fb);
		/*
		$created=$bb->create();
		$created->delete();*/
	}
}