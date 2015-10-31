<?php
/**
 * Created by IntelliJ IDEA.
 * User: Marco
 * Date: 20/09/15
 * Time: 20:59
 */
class Settings {
    private function __construct(){
        if(is_readable(absolute_from_script('settings.json'))){
            $this->settingsArray  = json_decode(file_get_contents(absolute_from_script('settings.json')), true);
        }else{
            $errRet=array("error"=>array("message"=>"settings file not found","code"=>"500"));
            http_response_code(500);
            echo json_encode($errRet);
        }
    }

    public function getPar($key){
        if(!!$this->settingsArray[$key])return $this->settingsArray[$key];
        else return null;
    }

    public function getSettingsArray(){
        return $this->settingsArray;
    }

    private $settingsArray;

    public static function getInstance(){
        if(self::$instance === null){self::$instance= new Settings();}
        return self::$instance;
    }

    private static $instance = null;
}