<?php
namespace Graphene\models;

use Graphene\Graphene;
use Graphene\controllers\model\ModelController;
use Graphene\controllers\model\ModelFactory;
use \Exception;
use Graphene\controllers\http\GraphResponse;
use Graphene\controllers\exceptions\GraphException;

abstract class Model implements \Serializable
{

    public function __construct()
    {
        $this->structs = $this->defineStruct();
        $this->modelController = new ModelController($this->getCustomCrudDriver(), $this->structs, $this, func_get_args());
    }

    public static function getByRequest($lazyChecks = false)
    {
        $req = Graphene::getInstance()->getRequest();
        $requestModels = ModelFactory::createByRequest($req, null, $lazyChecks);
        if (isset($requestModels[self::stcName()]))
            return $requestModels[self::stcName()];
        else
            throw new Exception('Bad request', 400);
    }

    /*
     * -----
     * Getters
     * -----
     */
    public static function stcName()
    {
        return explode('\\', get_called_class())[1];
    }

    public function getName()
    {
        if ($this->name == null) {
            if (! is_object($this) && ! is_string($this))
                return false;
            $class = explode('\\', (is_string($this) ? $object : get_class($this)));
            $this->name = $class[count($class) - 1];
        }
        return $this->name;
    }

    public function getDomain()
    {
        if ($this->domain == null) {
            $fw = Graphene::getInstance();
            $this->domain = $fw->getApplicationName() . "." . $fw->getCurrentModule()->getNamespace() . "." . $this->getName();
        }
        return $this->domain;
    }

    public function setLazy($boolean)
    {
        $this->modelController->setLazy($boolean);
    }

    public function getContent()
    {
        return $this->content;
    }

    public function getModelController()
    {
        return $this->modelController;
    }

    final public function getStruct($asString = false, $prettyPrint = false)
    {
        $str = $this->modelController->getStruct();
        if ($asString && ! $prettyPrint)
            return json_encode($str);
        if ($asString && $prettyPrint)
            return json_encode($str, JSON_PRETTY_PRINT);
        else
            return $str;
    }

    final public function isValid($lazyCheck = false)
    {
        return $this->modelController->checkContent($this, $lazyCheck);
    }

    public function isEmpty()
    {
        return count($this->content) == 0;
    }

    public function getLastTestErrors()
    {
        $errs = $this->modelController->getLastTestErrors();
        $ret = '';
        foreach ($errs as $errField) {
            foreach ($errField as $msm) {
                $ret .= $msm['message'] . ', and ';
            }
        }
        return substr($ret, 0, - 6);
    }
    
    // Serializzation
    public function serialize()
    {
        return $this->modelController->serialize($this);
    }
    public function unserialize($serialized){
        throw new GraphException("You can't unserialize model (yet)", 5009, 500);
    }
    /*
     * -----
     * Setters
     * -----
     */
    public function setContent($content)
    {
        $this->content = $content;
    }

    /*
     * -----------
     * CRUD Storage
     * -----------
     */
    public function getStorage()
    {
        return $this->modelController->getStorage();
    }

    public function create()
    {
        if ($this->canCreate())
            $this->onCreate();
        return $this->modelController->create($this);
    }

    public function read($multiple=false,$query=null,$page=null,$pageSize=null)
    {
        if ($this->canRead()) $this->onRead();
        return $this->modelController->read($this,$multiple,$query,$page,$pageSize);
    }

    public function update()
    {
        if ($this->canUpdate())
            $this->onUpdate();
        return $this->modelController->update($this);
    }

    public function delete()
    {
        if ($this->canDelete())
            $this->onDelete();
        return $this->modelController->delete($this);
    }

    public function patch()
    {
        if ($this->canPatch())
            $this->onPatch();
        return $this->modelController->patch($this);
    }

    /*
     * -----------
     * Dynamic functions
     * -----------
     */
    function __call($funct, $pars)
    {
        return $this->modelController->call($funct, $pars, $this);
    }

    /* Extensible functions */
    public abstract function defineStruct();

    public function getCustomCrudDriver()
    {
        return null;
    }

    public function canCreate()
    {
        return true;
    }

    public function onCreate()
    {}

    public function canRead()
    {
        return true;
    }

    public function onRead()
    {}

    public function canUpdate()
    {
        return true;
    }

    public function onUpdate()
    {}

    public function canDelete()
    {
        return true;
    }

    public function onDelete()
    {}

    public function canPatch()
    {
        return true;
    }

    public function onPatch()
    {}

    public function onSend()
    {}

    public function onSerialize()
    {}

    private $structs;

    private $modelController;

    private $domain = null;

    private $name = null;

    protected $content = array();

    const CHECK_SEP = '--';

    const CHECK_PAR = '::';
    // COSTANTI TIPO
    // value types
    /**
     * Check integer value
     */
    const INTEGER = '--t_integer';

    /**
     * Date field checker (format 'yyyy-mm-dd')
     */
    const DATE = '--t_date';

    /**
     * Date field checker (format 'yyyy-mm-dd hh:mm:ss')
     */
    const DATETIME = '--t_datetime';

    /**
     * String field checker
     */
    const STRING = '--t_string';

    /**
     * boolean field checker
     */
    const BOOLEAN = '--t_boolean';

    /**
     * double field checker
     */
    const DECIMAL = '--t_decimal';

    /**
     * Uid field checker 0-9 A-Z
     */
    const UID = '--t_uid::';

    /**
     * String that matches a regex
     */
    const MATCH = '--t_match::';

    /**
     * Enum field checker
     *
     * @param
     *            list of enum values with commas
     * @example Model::ENUM_VALUE.'foo,bar,cont'
     *         
     *         
     */
    const ENUM = '--t_enum::';
 // controlla se e' uno dei valori argomento
                               
    // CHECKS
    const NOT_NULL = '--c_not_null';
 // vero se il contenuto non e' null
    const UNIQUE = '--c_unique';
 // Controllo effettuato dallo storage
    const NOT_EMPTY = '--c_not_empty';
 // vero se il contenuto o l'array non e' vuoto
    const MIN_LEN = '--c_min_len::';
 // lunghezza minima del campo
    const MAX_LEN = '--c_max_len::'; // lunghezza massima del campo
}