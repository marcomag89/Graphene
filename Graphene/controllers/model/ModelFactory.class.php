<?php
namespace Graphene\controllers\model;

use Graphene\controllers\ExceptionsCodes;
use Graphene\controllers\http\GraphRequest;
use Graphene\models\Module;
use Graphene\Graphene;
use Graphene\models\Model;
use Graphene\controllers\exceptions\GraphException;

class ModelFactory
{

    /**
     * @param $json
     * @return Object[]|Boolean|null
     * @throws GraphException
     */
    public static function createByDbSerialization($json)
    {
        // echo $json;
        $modelArr = json_decode($json, true);
        if (isset($modelArr['content']))
            return self::createModel($modelArr['content'], $modelArr['domain']);
        else 
            if (isset($modelArr['collection'])) {
                $ret = array();
                foreach ($modelArr['collection'] as $content) {
                    if (($model = self::createModel($content, $modelArr['domain'])) !== false) {
                        $ret[] = $model;
                    } else {
                        self::$BEAN_PARSING_ERRS[] = self::$LAST_BEAN->getLastTestErrors();
                        echo self::$LAST_BEAN->getLastTestErrors();
                    }
                }
                // if(count($ret)==0)return null;
                return $ret;
            }
        return null;
    }

    public static function createByRequest(GraphRequest $request, Module $mod = null, $lazyChecks = false)
    {
        if ($mod == null)
            $mod = Graphene::getInstance()->getCurrentModule();
        if (($decoded = json_decode($request->getBody(), true)) === null)
            throw new GraphException('Malformed request check jsons structs on body', ExceptionsCodes::REQUEST_MALFORMED, 400);
        $return = array();
        foreach ($decoded as $ModelName => $modelContent) {
            $domain = Graphene::getInstance()->getApplicationName() . '.' . $mod->getNamespace() . '.' . $ModelName;
            if (($return[$ModelName] = self::createModel($modelContent, $domain, $lazyChecks)) === false) {
                self::$BEAN_PARSING_ERRS[] = self::$LAST_BEAN->getLastTestErrors();
                throw new GraphException(self::$LAST_BEAN->getLastTestErrors(), ExceptionsCodes::REQUEST_MALFORMED, 400);
            }
        }

        return $return;
    }

    private static function createModel($modelContent, $modelDomain, $lazyChecks = false)
    {
        $expl = explode('.', $modelDomain);
        $modelName = $expl[1] . '\\' . $expl[2];

        if (class_exists($modelName))
            $model = new $modelName();
        else
            throw new GraphException('Model ' . $modelName . ' is not handlend in this module', 5000, 500);
        if ($model instanceof Model) {
            $model->setLazy(true);
            $model->setContent($modelContent);
            self::$LAST_BEAN = $model;
            if ($model->isValid($lazyChecks)) {
                return $model;
            } else {
                return false;
            }
        } else
            throw new GraphException('Model ' . $modelName . ' is not handlend in this module', 5000, 500);
    }

    public static function getModelParsingErrs()
    {
        return self::$BEAN_PARSING_ERRS;
    }

    private static $BEAN_PARSING_ERRS = array();

    private static $LAST_BEAN;
}
