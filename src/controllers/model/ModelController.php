<?php
namespace Graphene\controllers\model;

use Graphene\models\Model;
use Graphene\Graphene;
use Graphene\db\CrudDriver;
use Graphene\db\CrudStorage;
use Graphene\utils\Strings;

use Graphene\controllers\http\GraphRequest;
use Graphene\controllers\exceptions\GraphException;

class ModelController
{

    public function __construct($crudDriver, $structs,$model, $args)
    {
        $this->structs = $structs;
        $this->args = $args;
        $this->corrupt = false;
        $this->modelChecker = new ModelChecker();
        $this->modelInit($model);
        // Controlla se e stato settato un driver personalizzato
        if   ($crudDriver != null) $this->setCrudDriver($crudDriver);
        else $this->storage = Graphene::getInstance()->getStorage();
            // Controlla gli argomenti passati da API
        if ($args == null) $this->emptyInit();
        else
            foreach ($this->args as $arg) {
                if     ($arg instanceof GraphRequest) $this->requestInit($arg);
                elseif ($arg instanceof Model)        $this->modelInit($arg);
                elseif (is_string($arg))              $this->parInit($arg);
            }
    }

    /* Initialization */
    private function emptyInit() {}
    private function modelInit($model) {
        $this->modelName=$model->getModelName();
    }
    private function requestInit(GraphRequest $request)
    {}

    /**
     * @param $par
     */
    private function parInit($par)
    {
        $this->settings[$par] = true;
    }

    private function getBasicStruct()
    {
        return array(
            'id'      => Model::UID . Model::NOT_EMPTY,
            'version' => Model::INTEGER . Model::NOT_EMPTY
        );
    }

    /*
     * --------
     * Struct management
     * --------
     */
    public function getStruct()
    {
        //$struct = array();
        $basic = $this->getBasicStruct();
        $ret = array_merge_recursive($this->structs,$basic);
        if (! $this->modelChecker->checkValidStruct($ret))
            throw new GraphException('Invalid '.$this->modelName.'\'s model struct', 500, 500);
        return $ret;
    }

    /*
     * --------
     * Getters and setters
     * --------
     */
    public function call($funct, $pars, Model $model)
    {
        $splitted = explode('_', substr($funct, 3));
        $splitted[0] = lcfirst($splitted[0]);
        if     (Strings::startsWith($funct, 'get')) return $this->serveGet($splitted, $model);
        elseif (Strings::startsWith($funct, 'set')) return $this->serveSet($splitted, $pars[0], $model);
        else   return null;
    }

    /* Auto Generated getters */
    public function serveGet($funct, Model $model)
    {
        $content = $model->getContent();
        $struct = $this->getStruct();
        
        $tmps = &$struct;
        $data = $content;
        $temp = &$data;
        foreach ($funct as $k) {
            $tmps = &$tmps[$k];
            $temp = &$temp[$k];
        }
        if (isset($tmps)) {
            return $temp;
        } else
            return null;
    }

    /* Auto Generated setters */
    public function serveSet($funct, $par, Model $model)
    {
        $content = $model->getContent();
        $struct = $this->getStruct();
        
        $tmps = &$struct;
        $data = &$content;
        $temp = &$data;
        foreach ($funct as $k) {
            $tmps = &$tmps[$k];
            $temp = &$temp[$k];
        }
        $this->modelChecker->newTest();
        if ((isset($tmps) && ! is_array($tmps) && $this->modelChecker->isValidValue($par, $tmps, implode('_', $funct)))) {
            $temp = $par;
            $model->setContent($data);
            return true;
        } else {
            $this->corrupt = true;
            return false;
        }
    }

    public function isCorrupt()
    {
        return $this->corrupt;
    }

    public function getCurrentAction()
    {
        $action = Graphene::getInstance()->getCurrentModule()->getCurrentAction();
        return $action;
    }

    /*
     * --------
     * Storage management
     * --------
     */
    public function setCrudDriver(CrudDriver $driver)
    {
        $this->storage = new CrudStorage($driver);
    }

    public function getCrudDriver()
    {
        return $this->storage->getDriver();
    }

    public function getStorage()
    {
        return $this->storage;
    }

    private function getSetting($setting)
    {
        if (isset($this->settings[$setting]))
            return true;
        else
            return false;
    }

    /*
     * --------
     * Serializzation
     * --------
     */
    public function serialize(Model $model)
    {
        return json_encode($this->getData($model),JSON_PRETTY_PRINT);
    }

    public function getData(Model $model){
        return [$model->getModelName() => $model->getContent()];
    }

    /*
     * CRUD-P
     * Create Read Update Delete and Patch routines
     */
    public function create($model)
    {
        return $this->storage->create($model);
    }

    public function read($model,$multiple,$query,$page=null,$pageSize=null)
    {
        return $this->storage->read($model,$multiple,$query,$page,$pageSize);
    }

    public function update($model)
    {
        return $this->storage->update($model);
    }

    public function delete($model)
    {
        return $this->storage->delete($model);
    }

    public function patch($model)
    {
        return $this->storage->patch($model);
    }

    /*
     * --------
     * Utilities
     * --------
     */
    public function setLazy($boolean)
    {
        $this->lazy = $boolean;
    }

    /*
     * --------
     * Struct and content checking
     * --------
     */
    public function checkContent(Model $model, $lazyCheck = false)
    {
        return $this->modelChecker->checkContent($model, $this->getStruct(), $lazyCheck || $this->lazy);
    }

    /**
     * @return bool
     */
    public function haveErrors(){
        $errs = $this->modelChecker->getLastTestErrors();
        return (count($errs) > 0);
    }

    /**
     * @return array
     */
    public function getLastTestErrors()
    {
        if ($this->exceeded != null)
            return 'unexpected ' . $this->exceeded . ' field';
        else
            $ret = $this->modelChecker->getLastTestErrors();
        return $ret;
    }

    /**
     *
     * @var ModelChecker
     */
    private $modelName;

    private $modelChecker;

    private $corrupt;

    private $ready;

    private $args;

    private $lazy = false;

    private $settings = array();

    private $storage;

    private $exceeded = null;

    private $structs;
 // struttura in base all'azione _DEFAULT e la struttura di
                      // default
    
    /* Costanti */
    const BASIC_STRUCT = '_basic';

    const LAZY_STRUCT = '_lazy';

    const LOG_NAME = '[Model Controller] ';

    const FLAG_LAZY = '-lzm';
}