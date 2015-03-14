<?php
namespace Graphene\db;

use Graphene\models\Model;
use Graphene\controllers\ExceptionsCodes;
use Graphene\controllers\model\ModelFactory;
use Graphene\db\CrudDriver;
use \Exception;
use Graphene\controllers\model\ModelController;
use Graphene\controllers\exceptions\GraphException;

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
     * @param <b>Model</b> $model            
     * @return Nuovo model [senza id e versione]
     * @throws eccezione generica con messaggio se qualcosa va storto;
     */
    public function create(Model $model)
    {
        log_write(self::STORAGE_LOG_NAME . 'calling storage driver for create');
        $model->setLazy(false);
        if (! $model->isValid())
            throw new Exception('Model, ' . $model->getName() . ' is not valid for storage: ' . $model->getLastTestErrors(), ExceptionsCodes::BEAN_STORAGE_CORRUPTED_BEAN);
        $model->setVersion(1);
        $model->setId(uniqid(strtoupper(substr($model->getName(), 0, 3))));
        $created = $this->driver->create($this->serializeForDb($model));
        if (($retb = ModelFactory::createByDbSerialization($created)) == null)
            throw new Exception('Error when create, Stored ' . $model->getName() . ' is corrupt', ExceptionsCodes::BEAN_STORAGE_CORRUPTED_BEAN);
        else
            return $retb;
    }

    /**
     * Carica un model compilato parzialmente utilizzando
     * i campi compilati come criterio di ricerca in <b>AND<b> tra loro
     *
     * @param
     *            <b>Model</b> model parzialmente compilato
     * @return Uno o piu model che corrispondono ai criteri di ricerca
     * @throws eccezione generica con messaggio se qualcosa va storto;
     */
    public function read(Model $model)
    {
        log_write(self::STORAGE_LOG_NAME . 'calling storage driver for read');
        $readed = $this->driver->read($this->serializeForDb($model));
        // echo 'JSON Letto';
        // echo($readed);
        $models = ModelFactory::createByDbSerialization($readed);
        if (is_null($models))
            throw new Exception('Error when read, Stored ' . $model->getName() . ' is corrupt', ExceptionsCodes::BEAN_STORAGE_CORRUPTED_BEAN);
        else {
            if (! is_array($models))
                $models = array(
                    $models
                ); // per tornare sempre un array di risultati
            return $models;
        }
    }

    /**
     * Sovrascrive un model fornendo id e versione
     *
     * @param
     *            <b>Model</b> model da modificare [id e versione obbligatori]
     * @return Il model modificato
     * @throws eccezione generica con messaggio se qualcosa va storto;
     */
    public function update(Model $model)
    {
        log_write(self::STORAGE_LOG_NAME . 'calling storage driver for update');
        $model->setLazy(false);
        if ($model->getId() == null)
            throw new Exception('Unavailable ' . $model->getName() . ' id', ExceptionsCodes::BEAN_STORAGE_ID_UNAVAILABLE);
        if ($model->getVersion() == null)
            throw new Exception('Unavailable ' . $model->getName() . ' version', ExceptionsCodes::BEAN_STORAGE_VERSION_UNAVAILABLE);
        if (! $model->isValid())
            throw new Exception('Error on storage, ' . $model->getName() . ' is corrupt: ' . $model->getLastTestErrors(), ExceptionsCodes::BEAN_STORAGE_CORRUPTED_BEAN);
        $bkpContent = $model->getContent();
        $model->setLazy(true);
        $model->setContent(array(
            'id' => $bkpContent['id']
        ));
        $readed = $this->read($model);
        if (count($readed) == 0 || $readed[0]->isEmpty())
            throw new Exception($model->getName() . ' not found', ExceptionsCodes::BEAN_STORAGE_ID_NOT_FOUND);
        if (count($readed) == 0 || $readed[0]->getVersion() != $bkpContent['version'])
            throw new Exception($model->getName() . ' version mismatch, reload your ' . $model->getName() . ' instance for updates', ExceptionsCodes::BEAN_STORAGE_VERSION_MISMATCH);
        $model->setContent($bkpContent);
        $model->setVersion($model->getVersion() + 1);
        $updated = $this->driver->update($this->serializeForDb($model));
        if (($model = ModelFactory::createByDbSerialization($updated)) == null) {
            throw new Exception('Updated model is corrupt', ExceptionsCodes::BEAN_STORAGE_CORRUPTED_BEAN);
        } else {
            return $model;
        }
    }

    /**
     * Elimina il Model dalla base di dati in base all' id fornendo la versione
     * corretta
     *
     * @param
     *            <b>Model</b> model da eliminare [id e versione obbligatori]
     * @return <b>boolean</b> in base alla avvenuta cancellazione
     * @throws eccezione generica con messaggio se qualcosa va storto;
     */
    public function delete(Model $model)
    {
        log_write(self::STORAGE_LOG_NAME . 'calling storage driver for delete');
        if ($model->getId() == null)
            throw new GraphException('Unavailable ' . $model->getName() . ' id', ExceptionsCodes::BEAN_STORAGE_ID_UNAVAILABLE, 400);
        if ($model->getVersion() == null)
            throw new GraphException('Unavailable ' . $model->getName() . ' version', ExceptionsCodes::BEAN_STORAGE_VERSION_UNAVAILABLE, 500);
        if (! $model->isValid())
            throw new GraphException('Error on storage, ' . $model->getName() . ' is corrupt: ' . $model->getLastTestErrors(), ExceptionsCodes::BEAN_STORAGE_CORRUPTED_BEAN, 500);
        $bkpContent = $model->getContent();
        $model->setLazy(true);
        $model->setContent(array(
            'id' => $bkpContent['id']
        ));
        $readed = $this->read($model);
        if (! isset($readed[0]) || $readed[0]->isEmpty())
            throw new GraphException($model->getName() . ' not found', ExceptionsCodes::BEAN_STORAGE_ID_NOT_FOUND, 404);
        if ($readed[0]->getVersion() != $bkpContent['version'])
            throw new GraphException($model->getName() . ' version Mismatch, reload model for updates', ExceptionsCodes::BEAN_STORAGE_VERSION_MISMATCH, 400);
        $model->setContent($bkpContent);
        $this->driver->delete($this->serializeForDb($model));
        return true;
    }

    /**
     * Esegue una patch del model nella base di dati
     * In base all' id fornendo la versione corretta
     *
     * @param
     *            <b>Model</b> model da patchare [id e versione obbligatori]
     * @return <b>boolean</b> in base alla avvenuta modifica
     * @throws eccezione generica con messaggio se qualcosa va storto;
     */
    public function patch(Model $model)
    {
        log_write(self::STORAGE_LOG_NAME . 'calling storage driver for delete');
        if ($model->getId() == null)
            throw new Exception('Unavailable ' . $model->getName() . ' id', ExceptionsCodes::BEAN_STORAGE_ID_UNAVAILABLE);
        if ($model->getVersion() == null)
            throw new Exception('Unavailable ' . $model->getName() . ' version', ExceptionsCodes::BEAN_STORAGE_VERSION_UNAVAILABLE);
        if (! $model->isValid())
            throw new Exception('Error on storage, model is corrupt: ' . $model->getLastTestErrors(), ExceptionsCodes::BEAN_STORAGE_CORRUPTED_BEAN);
        $modelClass = get_class($model);
        $tModel = new $modelClass();
        $tModel->setContent(array(
            'id' => $model->getId(),
            'version' => $model->getVersion()
        ));
        $readed = $this->read($tModel);
        if (! isset($readed[0]) || $readed[0]->isEmpty())
            throw new Exception($model->getName() . ' not found', ExceptionsCodes::BEAN_STORAGE_ID_NOT_FOUND);
        if ($readed[0]->getVersion() != $model->getVersion())
            throw new Exception($model->getName() . ' version Mismatch, reload model for updates', ExceptionsCodes::BEAN_STORAGE_VERSION_MISMATCH);
        $mainCnt = $readed[0]->getContent();
        $patched = array_replace_recursive($mainCnt, $model->getContent());
        $model->setContent($patched);
        return $this->update($model);
    }

    /**
     * Ritorna il driver CRUD caricato
     *
     * @param
     *            void
     * @return CrudDriver driver crud
     */
    public function getDriver()
    {
        return $this->driver;
    }

    private function serializeForDb(Model $model)
    {
        $ret = array(
            'domain' => $model->getDomain(),
            'type' => 'model',
            'pageNo' => $this->pageNo,
            'pageElements' => $this->pageElements,
            'struct' => $model->getStruct(),
            'content' => $model->getContent()
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
        $this->pageNo = $pageNo;
    }

    const STORAGE_LOG_NAME = '[CRUD storage] ';

    /**
     *
     * @var CrudDriver;
     */
    private $pageElements = 10;

    private $pageNo = 0;

    private $driver;

    /**
     * loaded if stored property is less than model property
     * CrudStorage::LESS_THEN.'property'
     * CrudStorage::LESS_THEN.'foo_bar_property'
     */
    const LESS_THAN = '-LT:';

    /**
     * loaded if stored property is greather than model property
     * CrudStorage::GREATHER_THEN.'property'
     * CrudStorage::GREATHER_THEN.'foo_bar_property'
     */
    const GREATHER_THAN = '-GT:';

    /**
     * loaded if stored property/ies matches regex
     * CrudStorage::LIKE.'regex,property1,property2'
     * CrudStorage::LIKE.'regex,foo_bar_property'
     */
    const LIKE = '-LK:';

    /**
     * loads k element of page n
     * CrudStorage::PAGE.'6,1'; //Loads page 1
     * CrudStorage::PAGE.'6,2'; //Loads page 2
     */
    const PAGE = '-PG:';

    /**
     * loads k element of page n
     * CrudStorage::PAGE.'6,1'; //Loads page 1
     * CrudStorage::PAGE.'6,2'; //Loads page 2
     */
    const OR_ = '-OR-';
}