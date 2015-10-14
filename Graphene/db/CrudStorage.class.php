<?php
namespace Graphene\db;

use Graphene\models\Model;
use Graphene\controllers\ExceptionsCodes;
use Graphene\controllers\model\ModelFactory;
use Graphene\controllers\exceptions\GraphException;
use Graphene\models\ModelCollection;
use \Exception;
use \Log;

class CrudStorage
{

    public function __construct(CrudDriver $driver)
    {
        $this->driver = $driver;
    }

    public function checkConnection()
    {
        try {
            $this->driver->getConnection();
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function getDriverInfos()
    {
        return $this->driver->getInfos();
    }

    /**
     * Crea un nuovo record con un model
     * assegnando a quest'ultimo un <b>ID</b> ed una <b>versione</b>
     *
     * @param  Model $model
     * @return Model nuovo model [senza id e versione]
     * @throws GraphException
     * @internal param $ <b>Model</b> $model
     */
    public function create(Model $model)
    {
        Log::debug('calling storage driver for create');
        $model->setLazy(false);
        if (! $model->isValid())
            throw new GraphException('Model, ' . $model->getModelName() . ' is not valid for storage: ' . $model->getLastTestErrors(), ExceptionsCodes::BEAN_STORAGE_CORRUPTED_BEAN,400);
        $model->setVersion(1);
        $model->setId(uniqid($model->getIdPrefix()));
        $created = $this->driver->create($this->serializeForDb($model));
        if (($retb = ModelFactory::createByDbSerialization($created)) == null)
            throw new GraphException('Error when create, Stored ' . $model->getModelName() . ' is corrupt', ExceptionsCodes::BEAN_STORAGE_CORRUPTED_BEAN,400);
        else{
        	return $retb[0];
        }
    }

    /**
     * Carica un model compilato parzialmente utilizzando
     * i campi compilati come criterio di ricerca in <b>AND<b> tra loro
     *
     * @param Model $model
     * @param bool $multiple
     * @param null $query
     * @param null $pageNo
     * @param null $pageElements
     * @return Model | ModelCollection Uno o piu model che corrispondono ai criteri di ricerca
     * @throws GraphException
     * @internal param $ <b>Model</b> modello parzialmente compilato*            <b>Model</b> modello parzialmente compilato
     */
    public function read(Model $model, $multiple = false, $query = null, $page = null, $pageSize = null)
    {
        Log::debug('calling storage driver for read');
        if(!$multiple) {
            $page     = 1;
            $pageSize = 2;}
        else{
            if($page === null)     $page     = 1;
            if($pageSize === null) $pageSize = self::DEFAULT_PAGE_SIZE;
        }

        $readed = $this->driver->read($this->serializeForDb($model, $page, $pageSize), $query);
        // echo "JSON Letto\n----\n";
        // echo ($readed);
        var_dump($readed);
        $result = ModelFactory::createByDbSerialization($readed);
        if (is_null($result)) {
            throw new GraphException('Error when read, Stored ' . $model->getModelName() . ' is corrupt' . ModelFactory::getModelParsingErrs(), ExceptionsCodes::BEAN_STORAGE_CORRUPTED_BEAN,400);
        } else {
            if (count($result) == 0) {return null;} else
                if ($multiple) {
                    $ret = new ModelCollection($model);
                    $ret->add($result);
                    $ret->setPage($page);
                    $ret->setPageSize($pageSize);
                    return $ret;
                } else {
                    if (count($result) == 1) {
                        return $result[0];
                    } else {
                        throw new GraphException("Unexpected result, loaded must be single model", 5002, 500);
                    }
                }
        }
    }

    /**
     * Sovrascrive un model fornendo id e versione
     *
     * @param  Model $model
     * @return Model Il model modificato
     * @throws GraphException
     * @internal param $ <b>Model</b> model da modificare [id e versione obbligatori]*            <b>Model</b> model da modificare [id e versione obbligatori]
     */
    public function update(Model $model)
    {
        Log::debug('calling storage driver for update');
        $model->setLazy(false);
        if ($model->getId() == null)
            throw new GraphException('Unavailable ' . $model->getModelName() . ' id', ExceptionsCodes::BEAN_STORAGE_ID_UNAVAILABLE,400);
        if ($model->getVersion() == null)
            throw new GraphException('Unavailable ' . $model->getModelName() . ' version', ExceptionsCodes::BEAN_STORAGE_VERSION_UNAVAILABLE,400);
        if (! $model->isValid())
            throw new GraphException('Error on storage, ' . $model->getModelName() . ' is corrupt: ' . $model->getLastTestErrors(), ExceptionsCodes::BEAN_STORAGE_CORRUPTED_BEAN,400);
        $bkpContent = $model->getContent();
        $model->setLazy(true);
        $model->setContent(array(
            'id' => $bkpContent['id']
        ));
        
        $readed = $this->read($model);
        if ($readed === null) throw new GraphException($model->getModelName() . ' not found', ExceptionsCodes::BEAN_STORAGE_ID_NOT_FOUND);
        if ($readed->getVersion() != $bkpContent['version']) throw new GraphException($model->getModelName() . ' version mismatch, reload your ' . $model->getModelName() . ' instance for updates', ExceptionsCodes::BEAN_STORAGE_VERSION_MISMATCH,400);
        $model   -> setContent($bkpContent);
        $model   -> setVersion($model->getVersion() + 1);
        $updated =  $this->driver->update($this->serializeForDb($model));
        $model   =  ModelFactory::createByDbSerialization($updated);
        if ($model  === null) {
            throw new GraphException('Updated model is corrupt', ExceptionsCodes::BEAN_STORAGE_CORRUPTED_BEAN,400);
        } else if(is_array($model)) {
            return $model[0];
        }else{
            return $model;
        }
    }

    /**
     * Elimina il Model dalla base di dati in base all' id fornendo la versione
     * corretta
     *
     * @param Model $model
     * @return bool <b>boolean</b> in base alla avvenuta cancellazione
     * @throws GraphException
     * @internal param $ <b>Model</b> model da eliminare [id e versione obbligatori]*            <b>Model</b> model da eliminare [id e versione obbligatori]
     */
    public function delete(Model $model)
    {
        Log::debug('calling storage driver for delete');
        if ($model->getId() == null)
            throw new GraphException('Unavailable ' . $model->getModelName() . ' id', ExceptionsCodes::BEAN_STORAGE_ID_UNAVAILABLE, 400);
        if ($model->getVersion() == null)
            throw new GraphException('Unavailable ' . $model->getModelName() . ' version', ExceptionsCodes::BEAN_STORAGE_VERSION_UNAVAILABLE, 500);
        if (! $model->isValid())
            throw new GraphException('Error on storage, ' . $model->getModelName() . ' is corrupt: ' . $model->getLastTestErrors(), ExceptionsCodes::BEAN_STORAGE_CORRUPTED_BEAN, 500);
        $bkpContent = $model->getContent();
        $model->setLazy(true);
        $model->setContent(array(
            'id' => $bkpContent['id']
        ));
        $readed = $this->read($model);
        if ($readed==null)
            throw new GraphException($model->getModelName() . ' not found', ExceptionsCodes::BEAN_STORAGE_ID_NOT_FOUND, 404);
        if ($readed->getVersion() != $bkpContent['version'])
            throw new GraphException($model->getModelName() . ' version Mismatch, reload model for updates', ExceptionsCodes::BEAN_STORAGE_VERSION_MISMATCH, 400);
        $model->setContent($bkpContent);
        $this->driver->delete($this->serializeForDb($model));
        return true;
    }

    /**
     * Esegue una patch del model nella base di dati
     * In base all' id fornendo la versione corretta
     *
     * @param Model $model
     * @return Boolean Il <b>boolean</b> in base alla avvenuta modifica
     * @throws GraphException
     * @internal param $ <b>Model</b> model da patchare [id e versione obbligatori]*
     * <b>Model</b> model da patchare [id e versione obbligatori]
     */
    public function patch(Model $model)
    {
        Log::debug('calling storage driver for patch');
        if ($model->getId() == null)
            throw new GraphException('Unavailable ' . $model->getModelName() . ' id', ExceptionsCodes::BEAN_STORAGE_ID_UNAVAILABLE,400);
        if ($model->getVersion() == null)
            throw new GraphException('Unavailable ' . $model->getModelName() . ' version', ExceptionsCodes::BEAN_STORAGE_VERSION_UNAVAILABLE,400);
        if (! $model->isValid())
            throw new GraphException('Error on storage, model is corrupt: ' . $model->getLastTestErrors(), ExceptionsCodes::BEAN_STORAGE_CORRUPTED_BEAN,400);
        $modelClass = get_class($model);
        $tModel = new $modelClass();
        $tModel->setContent(array(
            'id' => $model->getId(),
            'version' => $model->getVersion()
        ));
        $readed = $this->read($tModel);
        if ($readed==null)
            throw new GraphException($model->getModelName() . ' not found', ExceptionsCodes::BEAN_STORAGE_ID_NOT_FOUND);
        if ($readed->getVersion() != $model->getVersion())
            throw new GraphException($model->getModelName() . ' version Mismatch, reload model for updates', ExceptionsCodes::BEAN_STORAGE_VERSION_MISMATCH,400);
        $mainCnt = $readed[0]->getContent();
        $patched = array_replace_recursive($mainCnt, $model->getContent());
        $model->setContent($patched);
        return $this->update($model);
    }

    /**
     * Ritorna il driver CRUD caricato
     *
     * @return CrudDriver driver crud
     */
    public function getDriver()
    {
        return $this->driver;
    }

    private function serializeForDb(Model $model,$page=null,$pageSize=null)
    {
        $ret = array(
            'domain'       => $model->getDomain(),
            'type'         => 'model',
            'page'         => $page,
            'pageSize'     => $pageSize,
            'struct'       => $model->getStruct(),
            'content'      => $model->getContent()
        );
        $serialized = json_encode($ret);
        return $serialized;
    }

    public function setElementsPerPage($pageElements)
    {
        $this->pageElements = $pageElements;
    }

    public function setPageNumber($pageNo)
    {
        /** @noinspection PhpUndefinedFieldInspection */
        $this->pageNo = $pageNo;
    }

    const STORAGE_LOG_NAME = '[CRUD storage] ';

    /**
     *
     * @var CrudDriver;
     */
    private $pageSize = 10;

    private $page = 1;

    private $driver;

    private $pageElements;

    const DEFAULT_PAGE_SIZE = 20;
}