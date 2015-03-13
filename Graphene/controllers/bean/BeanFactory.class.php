<?php
namespace Graphene\controllers\bean;

use \Exception;
use Graphene\controllers\ExceptionsCodes;
use Graphene\controllers\http\GraphRequest;
use Graphene\models\Module;
use Graphene\Graphene;
use Graphene\models\Bean;
use Graphene\controllers\exceptions\GraphException;
use Graphene\controllers\http\GraphResponse;

class BeanFactory
{

    public static function createByDbSerialization($json)
    {
        // echo $json;
        $beanArr = json_decode($json, true);
        if (isset($beanArr['content']))
            return self::createBean($beanArr['content'], $beanArr['domain']);
        else 
            if (isset($beanArr['collection'])) {
                $ret = array();
                foreach ($beanArr['collection'] as $content) {
                    if (($bean = self::createBean($content, $beanArr['domain'])) !== false) {
                        $ret[] = $bean;
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

    public static function createByResponse(GraphResponse $response, Module $mod = null)
    {
        if ($mod == null)
            $mod = Graphene::getInstance()->getCurrentModule();
        if (($decoded = json_decode($response->getBody(), true)) == null)
            throw new GraphException('Malformed response check jsons structs on body', ExceptionsCodes::REQUEST_MALFORMED, 400);
        $return = array();
        foreach ($decoded as $BeanName => $beanContent) {
            $domain = Graphene::getInstance()->getApplicationName() . '.' . $mod->getNamespace() . '.' . $BeanName;
            if (($return[$BeanName] = self::createBean($beanContent, $domain)) == false) {
                self::$BEAN_PARSING_ERRS[] = self::$LAST_BEAN->getLastTestErrors();
                throw new GraphException(self::$LAST_BEAN->getLastTestErrors(), ExceptionsCodes::REQUEST_MALFORMED, 400);
            }
        }
    }

    public static function createByRequest(GraphRequest $request, Module $mod = null, $lazyChecks = false)
    {
        if ($mod == null)
            $mod = Graphene::getInstance()->getCurrentModule();
        if (($decoded = json_decode($request->getBody(), true)) == null)
            throw new GraphException('Malformed request check jsons structs on body', ExceptionsCodes::REQUEST_MALFORMED, 400);
        $return = array();
        foreach ($decoded as $BeanName => $beanContent) {
            $domain = Graphene::getInstance()->getApplicationName() . '.' . $mod->getNamespace() . '.' . $BeanName;
            if (($return[$BeanName] = self::createBean($beanContent, $domain, $lazyChecks)) == false) {
                self::$BEAN_PARSING_ERRS[] = self::$LAST_BEAN->getLastTestErrors();
                throw new GraphException(self::$LAST_BEAN->getLastTestErrors(), ExceptionsCodes::REQUEST_MALFORMED, 400);
            }
        }
        return $return;
    }

    private static function createBean($beanContent, $beanDomain, $lazyChecks = false)
    {
        $expl = explode('.', $beanDomain);
        $beanName = $expl[1] . '\\' . $expl[2];
        if (class_exists($beanName))
            $bean = new $beanName();
        else
            throw new GraphException('Bean ' . $beanName . ' is not handlend in this module', 5000, 500);
        if ($bean instanceof Bean) {
            $bean->setLazy(true);
            $bean->setContent($beanContent);
            self::$LAST_BEAN = $bean;
            if ($bean->isValid($lazyChecks)) {
                return $bean;
            } else {
                return false;
            }
        } else
            throw new GraphException('Bean ' . $beanName . ' is not handlend in this module', 5000, 500);
    }

    public static function getBeanParsingErrs()
    {
        return self::$BEAN_PARSING_ERRS;
    }

    private static $BEAN_PARSING_ERRS = array();

    private static $LAST_BEAN;
}
