<?php
namespace customer;
use Graphene\controllers\Action;

class ReadAll extends Action
{

    public function run() {
        $cust = new Customer();
        $custs= $cust->read(TRUE);
        $this->sendModel($custs);
    }//end run

}//end class