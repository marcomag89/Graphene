<?php
namespace Graphene\controllers;

use Graphene\controllers\http\GraphRequest;
use Graphene\controllers\http\GraphResponse;
use Graphene\controllers\Filter;
use Graphene\models\Module;

class FilterManager
{

    public function __construct()
    {
        $this->filters = array();
        $this->filterErrors = array();
        self::$ids = - 1;
    }

    public function addFilter(Filter $filter)
    {
        $this->filters[] = $filter;
    }

    public function execFilters(GraphRequest $req, Module $module, Action $action)
    {
        // $this->filterErrors=array();
        self::$ids ++;
        $errs = array();
        foreach ($this->filters as $filter) {
            if (! $filter->exec($req, $module, $action)) {
                $errs[] = array(
                    'ignored' => '0',
                    'name'    => $filter->getName(),
                    'status'  => $filter->getStatus(),
                    'message' => $filter->getMessage()
                );
            }
        }
        $this->filterErrors[self::$ids] = $errs;
        return ! $this->haveErrors();
    }

    public function getLastId()
    {
        return self::$ids;
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

    public function getFailedFilter()
    {
        if ($this->haveErrors())
            return end($this->filterErrors[$this->getLastId()]);
        else
            return false;
    }

    public function getFilterErrors()
    {
        return $this->filterErrors;
    }

    public function serializeErrors()
    {
        return json_encode(array(
            "filters" => $this->filterErrors
        ), JSON_PRETTY_PRINT);
    }

    private static $ids;

    private $filterErrors;

    private $filters;
}