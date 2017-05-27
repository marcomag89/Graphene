<?php
$_SERVER['SCRIPT_FILENAME'] = getcwd().DIRECTORY_SEPARATOR.$_SERVER['SCRIPT_FILENAME'];

require 'Graphene/Graphene.class.php';

use Graphene\Graphene;

class Cli{
    public static function getInstance($argv=null){
        if(self::$instance === null)self::$instance = new Cli($argv);
        return self::$instance;
    }

    public function __construct($argv=null){
        $this->data    = null;
        $this->request = null;
        $this->context = null;
        $this->settings= null;
        $this->isEnabled=false;

        if(isset($argv[1])){
            $this->isEnabled= true;
            $this->data     = json_decode(base64_decode($argv[1]),true);
            $this->request  = $this->data['request'];
            $this->context  = $this->data['context'];
            $this->settings = $this->data['settings'];
        }
    }

    public function isEnabled(){
        return $this->isEnabled;
    }
    public function getData(){
        return $this->data;
    }
    public function getRequest(){
        return $this->request;
    }
    public function getContext(){
        return $this->context;
    }
    public function getSettings(){
        return $this->settings;
    }

    private $data,$request,$context,$settings,$isEnabled;
    public static $instance = null;
}

if(Cli::getInstance($argv)->isEnabled()){
    Graphene::getInstance()->start();
}
