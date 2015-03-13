<?php
namespace Graphene\db;

use Graphene\models\Bean;
use Graphene\controllers\ExceptionsCodes;
use Graphene\controllers\bean\BeanFactory;
use Graphene\db\CrudDriver;
use \Exception;
use Graphene\controllers\bean\BeanController;
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
     * Crea un nuovo record con un bean
     * assegnando a quest'ultimo un <b>ID</b> ed una <b>versione</b>
     *
     * @param <b>Bean</b> $bean            
     * @return Nuovo bean [senza id e versione]
     * @throws eccezione generica con messaggio se qualcosa va storto;
     */
    public function create(Bean $bean)
    {
        log_write(self::STORAGE_LOG_NAME . 'calling storage driver for create');
        $bean->setLazy(false);
        if (! $bean->isValid())
            throw new Exception('Bean, ' . $bean->getName() . ' is not valid for storage: ' . $bean->getLastTestErrors(), ExceptionsCodes::BEAN_STORAGE_CORRUPTED_BEAN);
        $bean->setVersion(1);
        $bean->setId(uniqid(strtoupper(substr($bean->getName(), 0, 3))));
        $created = $this->driver->create($this->serializeForDb($bean));
        if (($retb = BeanFactory::createByDbSerialization($created)) == null)
            throw new Exception('Error when create, Stored ' . $bean->getName() . ' is corrupt', ExceptionsCodes::BEAN_STORAGE_CORRUPTED_BEAN);
        else
            return $retb;
    }

    /**
     * Carica un bean compilato parzialmente utilizzando
     * i campi compilati come criterio di ricerca in <b>AND<b> tra loro
     *
     * @param
     *            <b>Bean</b> bean parzialmente compilato
     * @return Uno o piu bean che corrispondono ai criteri di ricerca
     * @throws eccezione generica con messaggio se qualcosa va storto;
     */
    public function read(Bean $bean)
    {
        log_write(self::STORAGE_LOG_NAME . 'calling storage driver for read');
        $readed = $this->driver->read($this->serializeForDb($bean));
        // echo 'JSON Letto';
        // echo($readed);
        $beans = BeanFactory::createByDbSerialization($readed);
        if (is_null($beans))
            throw new Exception('Error when read, Stored ' . $bean->getName() . ' is corrupt', ExceptionsCodes::BEAN_STORAGE_CORRUPTED_BEAN);
        else {
            if (! is_array($beans))
                $beans = array(
                    $beans
                ); // per tornare sempre un array di risultati
            return $beans;
        }
    }

    /**
     * Sovrascrive un bean fornendo id e versione
     *
     * @param
     *            <b>Bean</b> bean da modificare [id e versione obbligatori]
     * @return Il bean modificato
     * @throws eccezione generica con messaggio se qualcosa va storto;
     */
    public function update(Bean $bean)
    {
        log_write(self::STORAGE_LOG_NAME . 'calling storage driver for update');
        $bean->setLazy(false);
        if ($bean->getId() == null)
            throw new Exception('Unavailable ' . $bean->getName() . ' id', ExceptionsCodes::BEAN_STORAGE_ID_UNAVAILABLE);
        if ($bean->getVersion() == null)
            throw new Exception('Unavailable ' . $bean->getName() . ' version', ExceptionsCodes::BEAN_STORAGE_VERSION_UNAVAILABLE);
        if (! $bean->isValid())
            throw new Exception('Error on storage, ' . $bean->getName() . ' is corrupt: ' . $bean->getLastTestErrors(), ExceptionsCodes::BEAN_STORAGE_CORRUPTED_BEAN);
        $bkpContent = $bean->getContent();
        $bean->setLazy(true);
        $bean->setContent(array(
            'id' => $bkpContent['id']
        ));
        $readed = $this->read($bean);
        if (count($readed) == 0 || $readed[0]->isEmpty())
            throw new Exception($bean->getName() . ' not found', ExceptionsCodes::BEAN_STORAGE_ID_NOT_FOUND);
        if (count($readed) == 0 || $readed[0]->getVersion() != $bkpContent['version'])
            throw new Exception($bean->getName() . ' version mismatch, reload your ' . $bean->getName() . ' instance for updates', ExceptionsCodes::BEAN_STORAGE_VERSION_MISMATCH);
        $bean->setContent($bkpContent);
        $bean->setVersion($bean->getVersion() + 1);
        $updated = $this->driver->update($this->serializeForDb($bean));
        if (($bean = BeanFactory::createByDbSerialization($updated)) == null) {
            throw new Exception('Updated bean is corrupt', ExceptionsCodes::BEAN_STORAGE_CORRUPTED_BEAN);
        } else {
            return $bean;
        }
    }

    /**
     * Elimina il Bean dalla base di dati in base all' id fornendo la versione
     * corretta
     *
     * @param
     *            <b>Bean</b> bean da eliminare [id e versione obbligatori]
     * @return <b>boolean</b> in base alla avvenuta cancellazione
     * @throws eccezione generica con messaggio se qualcosa va storto;
     */
    public function delete(Bean $bean)
    {
        log_write(self::STORAGE_LOG_NAME . 'calling storage driver for delete');
        if ($bean->getId() == null)
            throw new GraphException('Unavailable ' . $bean->getName() . ' id', ExceptionsCodes::BEAN_STORAGE_ID_UNAVAILABLE, 400);
        if ($bean->getVersion() == null)
            throw new GraphException('Unavailable ' . $bean->getName() . ' version', ExceptionsCodes::BEAN_STORAGE_VERSION_UNAVAILABLE, 500);
        if (! $bean->isValid())
            throw new GraphException('Error on storage, ' . $bean->getName() . ' is corrupt: ' . $bean->getLastTestErrors(), ExceptionsCodes::BEAN_STORAGE_CORRUPTED_BEAN, 500);
        $bkpContent = $bean->getContent();
        $bean->setLazy(true);
        $bean->setContent(array(
            'id' => $bkpContent['id']
        ));
        $readed = $this->read($bean);
        if (! isset($readed[0]) || $readed[0]->isEmpty())
            throw new GraphException($bean->getName() . ' not found', ExceptionsCodes::BEAN_STORAGE_ID_NOT_FOUND, 404);
        if ($readed[0]->getVersion() != $bkpContent['version'])
            throw new GraphException($bean->getName() . ' version Mismatch, reload bean for updates', ExceptionsCodes::BEAN_STORAGE_VERSION_MISMATCH, 400);
        $bean->setContent($bkpContent);
        $this->driver->delete($this->serializeForDb($bean));
        return true;
    }

    /**
     * Esegue una patch del bean nella base di dati
     * In base all' id fornendo la versione corretta
     *
     * @param
     *            <b>Bean</b> bean da patchare [id e versione obbligatori]
     * @return <b>boolean</b> in base alla avvenuta modifica
     * @throws eccezione generica con messaggio se qualcosa va storto;
     */
    public function patch(Bean $bean)
    {
        log_write(self::STORAGE_LOG_NAME . 'calling storage driver for delete');
        if ($bean->getId() == null)
            throw new Exception('Unavailable ' . $bean->getName() . ' id', ExceptionsCodes::BEAN_STORAGE_ID_UNAVAILABLE);
        if ($bean->getVersion() == null)
            throw new Exception('Unavailable ' . $bean->getName() . ' version', ExceptionsCodes::BEAN_STORAGE_VERSION_UNAVAILABLE);
        if (! $bean->isValid())
            throw new Exception('Error on storage, bean is corrupt: ' . $bean->getLastTestErrors(), ExceptionsCodes::BEAN_STORAGE_CORRUPTED_BEAN);
        $beanClass = get_class($bean);
        $tBean = new $beanClass();
        $tBean->setContent(array(
            'id' => $bean->getId(),
            'version' => $bean->getVersion()
        ));
        $readed = $this->read($tBean);
        if (! isset($readed[0]) || $readed[0]->isEmpty())
            throw new Exception($bean->getName() . ' not found', ExceptionsCodes::BEAN_STORAGE_ID_NOT_FOUND);
        if ($readed[0]->getVersion() != $bean->getVersion())
            throw new Exception($bean->getName() . ' version Mismatch, reload bean for updates', ExceptionsCodes::BEAN_STORAGE_VERSION_MISMATCH);
        $mainCnt = $readed[0]->getContent();
        $patched = array_replace_recursive($mainCnt, $bean->getContent());
        $bean->setContent($patched);
        return $this->update($bean);
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

    private function serializeForDb(Bean $bean)
    {
        $ret = array(
            'domain' => $bean->getDomain(),
            'type' => 'bean',
            'pageNo' => $this->pageNo,
            'pageElements' => $this->pageElements,
            'struct' => $bean->getStruct(),
            'content' => $bean->getContent()
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