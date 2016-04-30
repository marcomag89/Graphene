<?php
namespace Graphene\controllers;

use Graphene\controllers\http\GraphRequest;
use Graphene\Graphene;
use Graphene\models\Module;

class FilterManager
{

    private static $ids;
    private $filterErrors;
    private $sortedFilters;
    /**
     * @var Filter[]
     */
    private $filters;

    public function __construct(){
        $this->filters      = array();
        $this->filterErrors = array();
        self::$ids = - 1;
    }

    public function addFilter(Filter $filter){
        $this->filters[] = $filter;
    }

    public function execFilters(GraphRequest $req, Module $module, Action $action)
    {
        if(
            $req->getHeader('system-token') !== null &&
            $req->getHeader('system-token') === Graphene::getInstance()->getSystemToken()
        ){
            return true;
        }
        $executed = array();
        self::$ids ++;
        $errs = array();
        do{
            $execs = 0;
            foreach ($this->filters as $filter) {
                if(array_search($filter->getName(),$executed) === false && $this->checkAfter($executed, $filter)){
                    $execs++;
                    $executed[] = $filter->getName();
                    //Log::debug('executing filter: '.$filter->getName());
                    if (! $filter->exec($req, $module, $action)) {
                        $errs[] = array(
                            'ignored' => $filter->errorIgnored(),
                            'name'    => $filter->getName(),
                            'status'  => $filter->getStatus(),
                            'message' => $filter->getMessage()
                        );
                    }
                }
            }
        } while($execs > 0);

        if(count($executed) !== count($this->filters)){
            //Log::err('Some filter was not executed');
            //TODO elenco filtri non eseguiti e dipendenze richieste
        }

        $this->filterErrors[self::$ids] = $errs;

        return ! $this->haveErrors();
    }

    /**
     * Ritorna true se sono stati eseguiti i filtri richiesti, altrimenti false
     *
     * @param Filter[] $executed
     * @param Filter   $filter
     *
     * @return boolean
     */
    private function checkAfter($executed, $filter){
        $after = $filter->getAfter();
        foreach($after as $afterEl){
            //Log::debug('checking after: '.$afterEl.' for '.$filter->getName()."\n". json_encode($executed,JSON_PRETTY_PRINT));
            if(array_search($afterEl, $executed, true) === false){
                return false;
            }
        }
        return true;
    }

    public function haveErrors()
    {
        foreach ($this->filterErrors as $reqId => $errs) {
            foreach ($errs as $err) {
                if ($err['ignored'] == '0') {
                    return true;
                }
            }
        }
        return false;
    }

    public function getFailedFilter(){
        if ($this->haveErrors())
            return end($this->filterErrors[$this->getLastId()]);
        else
            return false;
    }

    public function getLastId() {
        return self::$ids;
    }

    public function getFilterErrors(){
        return $this->filterErrors;
    }

    public function serializeErrors(){
        return json_encode(array(
            "filters" => $this->filterErrors
        ), JSON_PRETTY_PRINT);
    }

    private function sortFilters(){
        if($this->sortedFilters !== null){
            return $this->sortedFilters;
        }
        else{
            $this->sortedFilters = array();
            foreach($this->filters as $filter){
                var_dump($filter->getAfter());
            }
        }
    }
}