<?php
namespace Graphene\controllers;


use Graphene\Graphene;
use \Log;


class ModuleManifest{
    public function __construct($modulePath=null){
        if($modulePath !== null){
            $this->read($modulePath);
        }
    }

    public function read($modulePath){
        $this->modulePath=$modulePath;
        $manifest = array();
        $rManifest = $this->loadJson($modulePath);
        if($rManifest === null){ $rManifest = $this->loadXml($modulePath); }

        //Exceptions
        if($rManifest === null)                                throw new GraphException('module manifest not valid in: '.$modulePath,     500);
        if(!array_key_exists('info',      $rManifest        )) throw new GraphException('module info node not found in: '.$modulePath,    500);
        if(!array_key_exists('namespace', $rManifest['info'])) throw new GraphException('module namespace is undefined in: '.$modulePath, 500);
        if(!array_key_exists('name',      $rManifest['info'])) throw new GraphException('module name is undefined in: '.$modulePath,      500);
        if(!array_key_exists('version',   $rManifest['info'])) throw new GraphException('module version is undefined in: '.$modulePath,   500);

        //Defaults
        if(!array_key_exists('support',      $rManifest['info'])) $rManifest['info']['support']      = Graphene::host().'/doc/'.$rManifest['info']['namespace'];
        if(!array_key_exists('domain',       $rManifest['info'])) $rManifest['info']['domain']       = $rManifest['info']['namespace'];
        if(!array_key_exists('models-path',  $rManifest['info'])) $rManifest['info']['models-path']  = 'models';
        if(!array_key_exists('actions-path', $rManifest['info'])) $rManifest['info']['actions-path'] = 'actions';
        if(!array_key_exists('filters-path', $rManifest['info'])) $rManifest['info']['filters-path'] = 'filters';

        if(!array_key_exists('depends',      $rManifest['info'])) $rManifest['info']['depends']      = '';
        if(!array_key_exists('actions',      $rManifest        )) $rManifest['actions']              = array();
        if(!array_key_exists('filters',      $rManifest        )) $rManifest['filters']              = array();

        $manifest['info']    = array();
        $manifest['actions'] = array();
        $manifest['filters'] = array();

        //Informations
        $manifest['info']['version']      = $rManifest['info']['version'];
        $manifest['info']['name']         = $rManifest['info']['name'];
        $manifest['info']['depends']      = $this->parseCommas($rManifest['info']['depends']);
        $manifest['info']['namespace']    = $rManifest['info']['namespace'];
        $manifest['info']['support']      = $rManifest['info']['support'];
        $manifest['info']['domain']       = $rManifest['info']['domain'];
        $manifest['info']['models-path']  = $rManifest['info']['models-path'];
        $manifest['info']['actions-path'] = $rManifest['info']['actions-path'];
        $manifest['info']['filters-path'] = $rManifest['info']['filters-path'];
        $manifest['info']['author']       = $rManifest['info']['author'];

        //Resolving imports
        $rManifest['actions'] = $this->resolveImports($rManifest['actions']);

        //Actions
        foreach($rManifest['actions'] as $k=>$action){
            if(array_key_exists('name', $action)){

                if(!array_key_exists('pars', $action))         $rManifest['actions'][$k]['pars']='';
                if(!array_key_exists('query-prefix', $action)) $rManifest['actions'][$k]['query-prefix']='';
                if(!array_key_exists('handler', $action)){
                    $rManifest['actions'][$k]['handler'] = $this->actionNameToCamel($action['name']).'@'.$rManifest['info']['actions-path'].DIRECTORY_SEPARATOR.$rManifest['info']['namespace'].'.'.$action['name'].'.php';
                }
                if(!array_key_exists('method', $action))         $rManifest['actions'][$k]['method'] = 'GET';
                if(!array_key_exists('query', $action))          $rManifest['actions'][$k]['query']  = '';
                if(!array_key_exists('pars', $action))           $rManifest['actions'][$k]['pars']   = '';
                if(!array_key_exists('query-prefix', $action))   $rManifest['actions'][$k]['query-prefix']='';
                if (!array_key_exists('name-prefix', $action))   $rManifest['actions']['name-prefix'] = '';
                if (!array_key_exists('name-postfix', $action))  $rManifest['actions']['name-postfix'] = '';

                $rManifest['actions'][$k]['name'] = strtoupper($rManifest['actions'][$k]['name-prefix'].$rManifest['actions'][$k]['name'].$rManifest['actions'][$k]['name-postfix']);

                $expl = explode('@',$rManifest['actions'][$k]['handler']);
                $class = $expl[0];
                $file  = $expl[1];
                if(!is_absolute_path($expl[1])){
                    $file = $modulePath.'/'.$expl[1];
                }

                $manifest['actions'][$k] = array();
                $manifest['actions'][$k]['name']        = $rManifest['actions'][$k]['name'];
                $manifest['actions'][$k]['unique-name'] = $manifest['info']['name'].'.'.$rManifest['actions'][$k]['name'];
                $manifest['actions'][$k]['method']      = strtoupper($rManifest['actions'][$k]['method']);
                $manifest['actions'][$k]['imported']    = $rManifest['actions'][$k]['imported'];
                $manifest['actions'][$k]['query']       = $rManifest['actions'][$k]['query-prefix'].$rManifest['actions'][$k]['query'];
                $manifest['actions'][$k]['handler']     = $rManifest['actions'][$k]['handler'];
                $manifest['actions'][$k]['file']        = $file;
                $manifest['actions'][$k]['class']       = $class;
                $manifest['actions'][$k]['pars']        = $this->parseCommas($rManifest['actions'][$k]['pars']);


            } else {
                Log::err('action '.$k.' name is not defined in: '.$modulePath);
            }
        }

        //Filters
        $manifest['filters'] = $rManifest['filters'];

        foreach ($rManifest['filters'] as $k=>$filter){
            if(array_key_exists('name',$filter)){
                $rManifest['filters'][$k]['name']=strtoupper($filter['name']);
                if(!array_key_exists('handler',$filter)){
                    $rManifest['filters'][$k]['handler'] = $this->filterNameToCamel($filter['name']).'@'.$rManifest['info']['filters-path'].'/'.$rManifest['info']['namespace'].'.'.$filter['name'].'.php';
                }
                if(!array_key_exists('scope',$filter)) $rManifest['filters'][$k]['scope']='MODULE';
                if(!array_key_exists('after',$filter)) $rManifest['filters'][$k]['after']='';
                $expl = explode('@',$rManifest['filters'][$k]['handler']);
                $class = $expl[0];
                $file  = $expl[1];
                if(!is_absolute_path($expl[1])){
                    $file = $modulePath.'/'.$expl[1];
                }
                $manifest['filters'][$k]['name']        = $rManifest['filters'][$k]['name'];
                $manifest['filters'][$k]['unique-name'] = $manifest['info']['name'].'.'.$rManifest['filters'][$k]['name'];
                $manifest['filters'][$k]['file']        = $file;
                $manifest['filters'][$k]['class']       = $class;
                $manifest['filters'][$k]['handler']     = $rManifest['filters'][$k]['handler'];
                $manifest['filters'][$k]['scope']       = strtoupper($rManifest['filters'][$k]['scope']);
                $manifest['filters'][$k]['after']       = $this->parseCommas($rManifest['filters'][$k]['after']);

            }else{
                Log::err('filter '.$k.' name is not defined in: '.$modulePath);
            }
        }
        //Log::debug("\n-------\nLOADED MANIFEST\n--------\n".json_encode($manifest,JSON_PRETTY_PRINT));
        $this->manifest = $manifest;
    }

    private function parseCommas($parsString){
        if($parsString === '') return array();
        $p = explode(',',$parsString);
        foreach($p as $k=>$par){
            $p[$k] = trim($par);
        }
        return $p;
    }

    private function resolveImports($actions){
        $retActions = array();
        foreach($actions as $ak=>$action){
            if(!array_key_exists('imported',$action)){$actions[$ak]['imported']='false';}
            if(str_starts_with($action['name'],'$')){
                $injectionPath = strtoupper(substr($action['name'], 1));
                if(!str_contains($injectionPath,'/')){$injectionPath = G_path('imports/'.$injectionPath);}

                Log::debug('resolving import: '.$injectionPath);
                $stdActions = array();
                /*
                 *
                 * Loading and converting from files to stdAction syntax
                 *
                 * */
                //XML CASE
                if (file_exists($injectionPath. '/manifest.xml')) {
                    $impXml = json_decode(json_encode(simplexml_load_file($injectionPath. '/manifest.xml')), true);
                    if(array_key_exists('action',$impXml)){
                        foreach($impXml['action'] as $k=>$xmlAction){
                            $stdActions[]=$impXml['action'][$k]['@attributes'];
                        }
                    }
                }

                //JSON case
                elseif (file_exists($injectionPath. '/manifest.json')){
                    $jsonStr = file_get_contents($injectionPath. '/manifest.json');
                    $impJson = json_decode($jsonStr,true);
                    if(array_key_exists('actions',$impJson)){
                        $stdActions = $impJson['actions'];
                    }
                }else{
                    Log::err('manifest file not found in: '. $injectionPath);
                }

                //Finalizing import
                foreach($stdActions as $k=>$v){
                    if(!str_starts_with($v['name'],'$')){
                        $expl = explode('@',$v['handler']);
                        $stdActions[$k]['handler']    = $expl[0].'@'.$injectionPath.'/'.$expl[1];
                        $stdActions[$k]['imported']   = 'true';

                        if(array_key_exists('pars',$action))         $stdActions[$k]['pars']          = $action['pars'];
                        if(array_key_exists('query-prefix',$action)) $stdActions[$k]['query-prefix']  = $action['query-prefix'];
                        if(array_key_exists('name-prefix',$action))  $stdActions[$k]['name-prefix']   = $action['name-prefix'];
                        if(array_key_exists('name-postfix',$action)) $stdActions[$k]['name-postfix']  = $action['name-postfix'];
                    }
                }
                $retActions = array_merge($retActions,$this->resolveImports($stdActions));
            }else{
                $retActions[] = $actions[$ak];
            }
        }
        return $retActions;
    }

    private function loadJson($modulePath){
        $manifestDir = $modulePath . '/manifest.json';
        if (! file_exists($manifestDir)){return null;}
        $jsonStr = file_get_contents($manifestDir);
        $json = json_decode($jsonStr,true);
        return $json;
    }

    private function loadXml($modulePath){
        if (! file_exists($modulePath . "/manifest.xml")) return null;

        $xml = json_decode(json_encode(simplexml_load_file($modulePath . "/manifest.xml")), true);
        $xml['v']    = $xml['@attributes']['v'];
        $xml['info'] = $xml['info']['@attributes'];
        $xml['actions'] = array();
        $xml['filters'] = array();
        if(array_key_exists('action',$xml)){
            if(array_key_exists('@attributes',$xml['action'])){
                $xml['actions'][] = $xml['action']['@attributes'];
            }else{
                foreach($xml['action'] as $action) {
                    if (array_key_exists('@attributes', $action)) {
                        $xml['actions'][] = $action['@attributes'];
                    }
                }
            }
        }
        if(array_key_exists('filter',$xml)){
            if(array_key_exists('@attributes',$xml['filter'])){
                $xml['filters'][] = $xml['filter']['@attributes'];
            }else{
                foreach($xml['filter'] as $filter) {
                    if (array_key_exists('@attributes', $filter)) {
                        $xml['filters'][] = $filter['@attributes'];
                    }
                }
            }
        }
        $ret = array(
            'info'    => $xml['info'],
            'actions' => $xml['actions'],
            'filters' => $xml['filters']
        );
        //Log::debug("\n--------\nXML\n-------\n".json_encode($ret,JSON_PRETTY_PRINT));
        return $ret;
    }
    private function filterNameToCamel($filterName){
        return $this->actionNameToCamel($filterName);
    }
    private function actionNameToCamel($actionName){
        $expl = explode('_',strtolower($actionName));
        $ret='';
        foreach($expl as $lit){$ret.=ucfirst($lit);}
        return $ret;
    }

    public function getManifest(){
       return $this->manifest;
    }
    private $modulePath;
    private $manifest;
}