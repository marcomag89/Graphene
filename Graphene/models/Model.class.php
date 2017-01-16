<?php
namespace Graphene\models;

use Graphene\db\CrudDriver;
use Graphene\db\CrudStorage;
use Graphene\Graphene;
use Graphene\controllers\model\ModelController;
use Graphene\controllers\model\ModelFactory;
use Graphene\controllers\exceptions\GraphException;
use n\Agency;

/**
 * @method void setId(string $id)
 * @method string getId()
 *
 * @method void setVersion(int $version)
 * @method int getVersion()
 */
abstract class Model implements \Serializable {

    const CHECK_SEP = '--';
    const CHECK_PAR = '::';

    /*
     * -----
     * Getters
     * -----
     */
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
     * @example Model::ENUM_VALUE.'foo,bar,cont'
     */
    const ENUM = '--t_enum::';
    const NOT_NULL = '--c_not_null';
    const UNIQUE = '--c_unique';

    // Serialization
    const SEARCHABLE = '--c_searchable';
    const NOT_EMPTY = '--c_not_empty';
    /*
     * -----
     * Setters
     * -----
     */
    const MIN_LEN = '--c_min_len::';

    /*
     * -----------
     * CRUD Storage
     * -----------
     */
    const MAX_LEN = '--c_max_len::';
    /**
     * lunghezza del prefisso degli UID
     */
    const PREFIX_UID_LENGTH = 7;
    /**
     * filler per uid inferiori a prefix_uid_length
     */
    const PREFIX_UID_FILLER = '0';
    protected $content = [];
    private $structs;
    /**
     * @var ModelController
     */
    private $modelController;

    /*
     * -----------
     * Dynamic functions
     * -----------
     */
    private $domain;

    /* Extensible functions */
    private $name = null;

    public function __construct() {
        $this->structs = $this->defineStruct();
        $this->modelController = new ModelController($this->getCustomCrudDriver(), $this->structs, $this, func_get_args());
    }


    /**
     * @param      $id
     * @param      $model
     *
     * @return Model|null
     */
    public static function find($id, $model) {
        $result = self::findMatches(['id' => $id], $model, false, null, 1);
        if ($result != null) {
            return $result->current();
        } else {
            return null;
        }
    }

    /**
     * @param array $matchContent
     * @param Model $model
     * @param bool  $multiple
     * @param int   $page
     * @param int   $pageSize
     *
     * @return ModelCollection|null
     * @throws GraphException
     */
    public static function findMatches($matchContent, $model, $multiple = true, $page = null, $pageSize = -1) {
        $mod = new $model();
        if ($mod instanceof Model) {
            $mod->setContent($matchContent);

            return $mod->read($multiple, null, $page, $pageSize);
        } else {
            throw new GraphException($model . " does not extends model");
        }
    }


    /**
     * @return array
     */
    public abstract function defineStruct();

    /**
     * @return CrudDriver | null
     */
    public function getCustomCrudDriver() {
        return null;
    }

    /**
     * @param bool $lazyChecks
     *
     * @return Model | null
     * @throws GraphException
     */
    public static function getByRequest($lazyChecks = false) {
        $req = Graphene::getInstance()->getRequest();
        $requestModels = ModelFactory::createByRequest($req, null, $lazyChecks);
        if (isset($requestModels[self::stcName()])) {
            return $requestModels[self::stcName()];
        } else {
            throw new GraphException('Sent model is not valid ' . self::stcName(), 400, 400);
        }
    }

    /**
     * @return string
     */
    public static function stcName() {
        return explode('\\', get_called_class())[1];
    }

    /**
     * @return string
     */
    public function getDomain() {
        if ($this->domain == null) {
            $fw = Graphene::getInstance();
            $this->domain = $fw->getApplicationName() . "." . $fw->getCurrentModule()->getNamespace() . "." . $this->getModelName();
        }

        return $this->domain;
    }

    /**
     * @return string
     */
    public function getModelName() {
        if ($this->name === null) {
            if (!is_object($this) && !is_string($this)) {
                return false;
            }
            $class = explode('\\', get_class($this));
            /** @noinspection PhpUndefinedFieldInspection */
            $this->name = $class[count($class) - 1];
        }

        return $this->name;
    }

    public function setLazy($boolean) {
        $this->modelController->setLazy($boolean);
    }

    /**
     * @return array
     */
    public function getContent() {
        return $this->content;
    }

    public function setContent($content) {
        $this->content = $content;
    }

    /**
     * @return ModelController
     */
    public function getModelController() {
        return $this->modelController;
    }

    /**
     * @param bool $lazyCheck
     *
     * @return bool
     */
    public function isValid($lazyCheck = false) {
        return $this->modelController->checkContent($this, $lazyCheck);
    }

    /**
     * @return bool
     */
    public function isEmpty() {
        return count($this->content) == 0;
    }

    /**
     * @return string
     */
    public function getLastTestErrors() {
        $errs = $this->modelController->getLastTestErrors();
        $ret = '';
        foreach ($errs as $errField) {
            foreach ($errField as $msm) {
                $ret .= $msm['message'] . ', and ';
            }
        }

        return substr($ret, 0, -6);
    }

    public function getData() {
        return $this->modelController->getData($this);
    }

    public function serialize() {
        return $this->modelController->serialize($this);
    }

    public function unserialize($serialized) {
        throw new GraphException("You can't unserialize model (yet)", 5009, 500);
    }

    /**
     * @return CrudStorage
     */
    public function getStorage() {
        return $this->modelController->getStorage();
    }

    /**
     * @return Model | null
     * @throws GraphException
     */
    public function create() {
        if ($this->canCreate()) {
            $this->onCreate();

            return $this->modelController->create($this);
        } else {
            throw new GraphException('cannot CREATE ' . $this->getModelName() . ' model', 500, 500);
        }
    }

    /**
     * @return bool
     */
    public function canCreate() {
        return true;
    }

    public function onCreate() {
    }

    /**
     * @param bool $multiple
     * @param null $query
     * @param null $page
     * @param null $pageSize
     *
     * @return Model | ModelCollection | null
     * @throws GraphException
     */
    public function read($multiple = false, $query = null, $page = null, $pageSize = null) {
        if ($this->canRead()) {
            $this->onRead();

            return $this->modelController->read($this, $multiple, $query, $page, $pageSize);
        } else {
            throw new GraphException('cannot READ ' . $this->getModelName() . ' model', 500, 500);
        }
    }

    /**
     * @return bool
     */
    public function canRead() {
        return true;
    }

    public function onRead() {
    }

    /**
     * @return Model | null
     * @throws GraphException
     */
    public function update() {
        if ($this->canUpdate()) {
            $this->onUpdate();

            return $this->modelController->update($this);
        } else {
            throw new GraphException('cannot UPDATE ' . $this->getModelName() . ' model', 500, 500);
        }
    }

    /**
     * @return bool
     */
    public function canUpdate() {
        return true;
    }

    public function onUpdate() {
    }
    // COSTANTI TIPO
    // value types

    /**
     * @return Model | null
     * @throws GraphException
     */
    public function delete() {
        if ($this->canDelete()) {
            $this->onDelete();

            return $this->modelController->delete($this);
        } else {
            throw new GraphException('cannot DELETE ' . $this->getModelName() . ' model', 500, 500);
        }
    }

    /**
     * @return bool
     */
    public function canDelete() {
        return true;
    }

    public function onDelete() {
    }

    /**
     * @return Model | null
     * @throws GraphException
     */
    public function patch() {
        if ($this->canPatch()) {
            $this->onPatch();

            return $this->modelController->patch($this);
        } else {
            throw new GraphException('cannot PATCH ' . $this->getModelName() . ' model', 500, 500);
        }
    }

    public function canPatch() {
        return true;
    }

    public function onPatch() {
    }

    /**
     * @param $funct
     * @param $pars
     *
     * @return array|bool|null
     */
    function __call($funct, $pars) {
        return $this->modelController->call($funct, $pars, $this);
    }

    public function onSend() {
    }

    public function onSerialize() {
    }  // controlla se e' uno dei valori argomento


    // CHECKS

    public function getCreateActionStruct() {
        return $this->defineStruct();
    }     // vero se il contenuto non e' null

    public function getReadActionStruct() {
        return $this->getStruct();
    }       // controllo su DB se l'elemento risulta univoco

    /**
     * @param bool $asString
     * @param bool $prettyPrint
     *
     * @return array|string
     * @throws GraphException
     */
    final public function getStruct($asString = false, $prettyPrint = false) {
        $str = $this->modelController->getStruct();
        if ($asString && !$prettyPrint) {
            return json_encode($str);
        }
        if ($asString && $prettyPrint) {
            return json_encode($str, JSON_PRETTY_PRINT);
        } else {
            return $str;
        }
    }   // campo rilevante per le ricerche

    public function getUpdateActionStruct() {
        return $this->getStruct();
    }    // controllo stringa non vuota

    public function getDeleteActionStruct() {
        return $this->getStruct();
    }    // lunghezza minima del campo

    public function getCollectionActionStruct() {
        return $this->getStruct();
    }    // lunghezza massima del campo

    public function getIdPrefix() {
        $prefix = strtoupper(str_pad($this->getCustomPrefix(), self::PREFIX_UID_LENGTH, self::PREFIX_UID_FILLER));

        return $prefix;
    }

    public function getCustomPrefix() {
        return substr($this->getModelName(), 0, self::PREFIX_UID_LENGTH);
    }

}